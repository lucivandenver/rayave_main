// Raymundo Avenue Admin — Automatic AI Background Removal to Clean Transparent PNG
const IMGLY_VERSION = '1.5.1';
const IMGLY_PUBLIC_PATH = `https://staticimgly.com/@imgly/background-removal-data/${IMGLY_VERSION}/dist/`;

let imglyModulePromise = null;

async function loadImglyModule() {
    if (!imglyModulePromise) {
        imglyModulePromise = (async () => {
            const urls = [
                `https://esm.sh/@imgly/background-removal@${IMGLY_VERSION}`,
                `https://cdn.jsdelivr.net/npm/@imgly/background-removal@${IMGLY_VERSION}/+esm`
            ];
            let lastErr = null;
            for (const url of urls) {
                try {
                    const mod = await import(url);
                    const fn = mod.removeBackground || (mod.default && mod.default.removeBackground) || mod.default;
                    if (typeof fn === 'function') {
                        return fn;
                    }
                } catch (err) {
                    lastErr = err;
                }
            }
            throw lastErr || new Error('Unable to load AI background removal library.');
        })();
    }
    return imglyModulePromise;
}

function loadImageFromBlob(blob) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(blob);
        const img = new Image();
        img.onload = () => {
            URL.revokeObjectURL(url);
            resolve(img);
        };
        img.onerror = (err) => {
            URL.revokeObjectURL(url);
            reject(err);
        };
        img.src = url;
    });
}

async function resizeBlobIfLarge(blob, maxSide = 1024) {
    const img = await loadImageFromBlob(blob);
    const w = img.naturalWidth || img.width;
    const h = img.naturalHeight || img.height;
    if (w <= maxSide && h <= maxSide) {
        return { blob, width: w, height: h, img };
    }
    const scale = maxSide / Math.max(w, h);
    const nw = Math.max(1, Math.round(w * scale));
    const nh = Math.max(1, Math.round(h * scale));
    const canvas = document.createElement('canvas');
    canvas.width = nw;
    canvas.height = nh;
    canvas.getContext('2d').drawImage(img, 0, 0, nw, nh);
    const resizedBlob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    return { blob: resizedBlob, width: nw, height: nh, img };
}

function checkImageHasTransparency(img) {
    const w = Math.min(256, img.naturalWidth || img.width || 1);
    const h = Math.min(256, img.naturalHeight || img.height || 1);
    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(img, 0, 0, w, h);
    const data = ctx.getImageData(0, 0, w, h).data;
    const corners = [
        [2, 2],
        [w - 3, 2],
        [2, h - 3],
        [w - 3, h - 3]
    ];
    let transparentCorners = 0;
    for (const [cx, cy] of corners) {
        const x = Math.max(0, Math.min(w - 1, cx));
        const y = Math.max(0, Math.min(h - 1, cy));
        if (data[(y * w + x) * 4 + 3] < 240) {
            transparentCorners++;
        }
    }
    return transparentCorners >= 2;
}

async function tightCropTransparentBlob(blob, maxOutSide = 900) {
    const img = await loadImageFromBlob(blob);
    const w = img.naturalWidth || img.width;
    const h = img.naturalHeight || img.height;
    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(img, 0, 0, w, h);
    const imgData = ctx.getImageData(0, 0, w, h);
    const data = imgData.data;

    // Clean faint ghost alpha noise (< 40) so stray studio dust disappears
    for (let i = 3; i < data.length; i += 4) {
        if (data[i] < 40) {
            data[i] = 0;
        }
    }
    ctx.putImageData(imgData, 0, 0);

    let minX = w, minY = h, maxX = 0, maxY = 0;
    let found = false;
    for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
            if (data[(y * w + x) * 4 + 3] >= 90) {
                if (x < minX) minX = x;
                if (x > maxX) maxX = x;
                if (y < minY) minY = y;
                if (y > maxY) maxY = y;
                found = true;
            }
        }
    }

    if (!found || maxX <= minX || maxY <= minY) {
        return blob;
    }

    const pad = 8;
    const cx = Math.max(0, minX - pad);
    const cy = Math.max(0, minY - pad);
    const cw = Math.min(w - cx, maxX - minX + 1 + pad * 2);
    const ch = Math.min(h - cy, maxY - minY + 1 + pad * 2);

    const scale = Math.min(1, maxOutSide / Math.max(cw, ch));
    const outW = Math.max(1, Math.round(cw * scale));
    const outH = Math.max(1, Math.round(ch * scale));

    const out = document.createElement('canvas');
    out.width = outW;
    out.height = outH;
    out.getContext('2d').drawImage(canvas, cx, cy, cw, ch, 0, 0, outW, outH);

    return await new Promise((resolve) => out.toBlob(resolve, 'image/png'));
}

// Offline/fallback background remover for solid or light studio backgrounds
async function fallbackFloodFillRemove(blob) {
    const img = await loadImageFromBlob(blob);
    const scale = Math.min(1, 900 / Math.max(img.naturalWidth || 1, img.naturalHeight || 1));
    const w = Math.max(1, Math.round((img.naturalWidth || 200) * scale));
    const h = Math.max(1, Math.round((img.naturalHeight || 200) * scale));

    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(img, 0, 0, w, h);

    const imgData = ctx.getImageData(0, 0, w, h);
    const data = imgData.data;

    // Sample corner colors
    const cornerSeeds = [
        [2, 2],
        [w - 3, 2],
        [2, h - 3],
        [w - 3, h - 3]
    ];
    let bgR = 0, bgG = 0, bgB = 0;
    for (const [cx, cy] of cornerSeeds) {
        const idx = (Math.max(0, cy) * w + Math.max(0, cx)) * 4;
        bgR += data[idx];
        bgG += data[idx + 1];
        bgB += data[idx + 2];
    }
    bgR = Math.round(bgR / 4);
    bgG = Math.round(bgG / 4);
    bgB = Math.round(bgB / 4);

    const visited = new Uint8Array(w * h);
    const removed = new Uint8Array(w * h);
    const queue = new Int32Array(w * h);
    let head = 0, tail = 0;

    const isBackground = (p) => {
        const i = p * 4;
        const r = data[i], g = data[i + 1], b = data[i + 2];
        const dr = r - bgR, dg = g - bgG, db = b - bgB;
        const dist = Math.sqrt(dr * dr + dg * dg + db * db);
        const chroma = Math.max(r, g, b) - Math.min(r, g, b);
        const lum = (r + g + b) / 3;
        // Also matches neutral gray/white Pinterest checkerboard squares along edges
        const isNeutralChecker = chroma <= 18 && lum >= 185;
        return dist <= 46 || isNeutralChecker;
    };

    const enqueue = (x, y) => {
        const p = y * w + x;
        if (visited[p]) return;
        visited[p] = 1;
        if (isBackground(p)) {
            removed[p] = 1;
            queue[tail++] = p;
        }
    };

    for (let x = 0; x < w; x++) {
        enqueue(x, 0);
        enqueue(x, h - 1);
    }
    for (let y = 1; y < h - 1; y++) {
        enqueue(0, y);
        enqueue(w - 1, y);
    }

    while (head < tail) {
        const p = queue[head++];
        const x = p % w;
        const y = (p - x) / w;
        if (x > 0) enqueue(x - 1, y);
        if (x < w - 1) enqueue(x + 1, y);
        if (y > 0) enqueue(x, y - 1);
        if (y < h - 1) enqueue(x, y + 1);
    }

    for (let p = 0; p < w * h; p++) {
        if (removed[p]) {
            data[p * 4 + 3] = 0;
        }
    }

    ctx.putImageData(imgData, 0, 0);
    const rawBlob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
    return await tightCropTransparentBlob(rawBlob);
}

function assignFileToInput(fileInput, pngBlob, baseName) {
    let cleanBase = (baseName || 'cutout')
        .replace(/\.[^.]+$/, '')
        .replace(/[^a-zA-Z0-9-]+/g, '_')
        .replace(/_+/g, '_')
        .replace(/^_|_$/g, '');
    if (!cleanBase) {
        cleanBase = 'item';
    }
    cleanBase = cleanBase.slice(0, 40);
    const file = new File([pngBlob], `${cleanBase}_cutout.png`, { type: 'image/png' });
    const dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;
}

function attachCutoutWidget(widget) {
    const fileInput = widget.querySelector('[data-canvas-cutout-input]');
    const autoToggle = widget.querySelector('[data-auto-bg-toggle]');
    const statusBox = widget.querySelector('[data-cutout-status]');
    const statusText = widget.querySelector('[data-cutout-status-text]');
    const progressBar = widget.querySelector('[data-cutout-progress]');
    const previewWrap = widget.querySelector('[data-cutout-preview-wrap]');
    const previewImg = widget.querySelector('[data-cutout-preview-img]');
    const fromCurrentBtn = widget.querySelector('[data-cutout-from-current]');
    const fromMainUploadBtn = widget.querySelector('[data-cutout-from-main-upload]');
    const removeCheckbox = widget.querySelector('input[name="remove_canvas_image"]');
    const form = widget.closest('form');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

    if (!fileInput) return;

    let isProcessing = false;
    let lastPreviewUrl = null;

    function setSubmitBusy(busy) {
        if (!submitBtn) return;
        if (busy) {
            submitBtn.dataset.origHtml = submitBtn.dataset.origHtml || submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Removing Background...';
        } else {
            submitBtn.disabled = false;
            if (submitBtn.dataset.origHtml) {
                submitBtn.innerHTML = submitBtn.dataset.origHtml;
            }
        }
    }

    function setStatus(message, variant = 'info', pct = null) {
        if (!statusBox || !statusText) return;
        statusBox.className = `alert alert-${variant} py-2 px-3 small mt-2 mb-0`;
        statusText.textContent = message;
        if (progressBar) {
            if (pct === null) {
                progressBar.parentElement.classList.add('d-none');
            } else {
                progressBar.parentElement.classList.remove('d-none');
                progressBar.style.width = `${Math.max(5, Math.min(100, Math.round(pct)))}%`;
            }
        }
    }

    function showPreview(blob) {
        if (!previewWrap || !previewImg) return;
        if (lastPreviewUrl) {
            URL.revokeObjectURL(lastPreviewUrl);
        }
        lastPreviewUrl = URL.createObjectURL(blob);
        previewImg.src = lastPreviewUrl;
        previewWrap.classList.remove('d-none');
    }

    async function processSourceBlob(sourceBlob, fileName) {
        if (isProcessing) return;
        isProcessing = true;
        setSubmitBusy(true);

        if (removeCheckbox) {
            removeCheckbox.checked = false;
        }

        try {
            const { blob: workBlob, img } = await resizeBlobIfLarge(sourceBlob, 1024);

            // Skip AI if image already has transparent background
            if (checkImageHasTransparency(img)) {
                setStatus('Image is already a transparent PNG! Tight-cropping edges...', 'info', 80);
                const cropped = await tightCropTransparentBlob(workBlob);
                assignFileToInput(fileInput, cropped, fileName);
                showPreview(cropped);
                setStatus('Clean transparent PNG ready! Click Save to apply.', 'success', null);
                return;
            }

            setStatus('Loading AI background remover (cached after first use)...', 'info', 15);
            let cutoutBlob = null;
            let usedFallback = false;

            try {
                const removeBackground = await loadImglyModule();
                cutoutBlob = await removeBackground(workBlob, {
                    publicPath: IMGLY_PUBLIC_PATH,
                    model: 'isnet_quint8',
                    output: { format: 'image/png' },
                    progress: (key, current, total) => {
                        if (total && total > 0) {
                            const pct = Math.min(92, Math.round((current / total) * 85) + 10);
                            const label = String(key || '').includes('fetch')
                                ? 'Downloading AI model (one-time cache)'
                                : 'AI removing background';
                            setStatus(`${label}... ${pct}%`, 'info', pct);
                        } else {
                            setStatus('AI cutting out subject...', 'info', 75);
                        }
                    }
                });
                setStatus('Refining & cropping transparent cutout...', 'info', 95);
                cutoutBlob = await tightCropTransparentBlob(cutoutBlob);
            } catch (aiErr) {
                console.warn('AI background removal unavailable, falling back to edge removal:', aiErr);
                usedFallback = true;
                setStatus('Running built-in background removal...', 'warning', 70);
                cutoutBlob = await fallbackFloodFillRemove(workBlob);
            }

            assignFileToInput(fileInput, cutoutBlob, fileName);
            showPreview(cutoutBlob);

            if (usedFallback) {
                setStatus('Background removed using offline fallback. Check the preview below, then Save!', 'warning', null);
            } else {
                setStatus('Background removed automatically! Clean transparent PNG is ready—click Save.', 'success', null);
            }
        } catch (err) {
            console.error('Cutout conversion failed:', err);
            setStatus('Could not convert image automatically. You can still upload a PNG manually.', 'danger', null);
        } finally {
            isProcessing = false;
            setSubmitBusy(false);
        }
    }

    fileInput.addEventListener('change', function () {
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;
        const autoEnabled = !autoToggle || autoToggle.checked;
        if (!autoEnabled) {
            showPreview(file);
            setStatus('Auto-remove is off. Original file will be uploaded.', 'secondary', null);
            return;
        }
        processSourceBlob(file, file.name);
    });

    if (fromCurrentBtn) {
        fromCurrentBtn.addEventListener('click', async function () {
            const imgUrl = fromCurrentBtn.getAttribute('data-current-image-url');
            const prodName = fromCurrentBtn.getAttribute('data-product-name') || 'product';
            if (!imgUrl) return;
            try {
                setStatus('Loading current product image...', 'info', 10);
                const resp = await fetch(imgUrl, { credentials: 'same-origin' });
                if (!resp.ok) throw new Error('Could not read current product image.');
                const blob = await resp.blob();
                await processSourceBlob(blob, prodName);
            } catch (err) {
                setStatus(err.message || 'Unable to fetch current product image.', 'danger', null);
            }
        });
    }

    if (fromMainUploadBtn && form) {
        fromMainUploadBtn.addEventListener('click', function () {
            const mainInput = form.querySelector('input[name="image"]');
            const file = mainInput && mainInput.files && mainInput.files[0];
            if (!file) {
                setStatus('Choose a Product Image above first, then click this button to auto-cutout!', 'warning', null);
                return;
            }
            processSourceBlob(file, file.name);
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-canvas-cutout-widget]').forEach(attachCutoutWidget);
});

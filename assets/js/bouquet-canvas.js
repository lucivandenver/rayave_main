(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const studio = document.querySelector('[data-bouquet-studio]');
        if (!studio || typeof fabric === 'undefined') {
            return;
        }

        const STAGE_W = 480;
        const STAGE_H = 500;
        const MAX_HISTORY = 35;

        const isAuthenticated = studio.getAttribute('data-is-auth') === '1';
        const allowPreorderGlobal = studio.getAttribute('data-allow-preorder') === '1';
        const requiredSteps = (studio.getAttribute('data-required-steps') || 'container,flowers,fillers')
            .split(',')
            .map((s) => s.trim())
            .filter(Boolean);

        // DOM references
        const stageWrap = studio.querySelector('[data-stage-wrap]');
        const stageEmpty = studio.querySelector('[data-stage-empty]');
        const canvasEl = document.getElementById('bouquetLiveCanvas');
        const stepTabs = studio.querySelectorAll('[data-step-tab]');
        const stepPanes = studio.querySelectorAll('[data-step-pane]');
        const filterButtons = studio.querySelectorAll('[data-filter-step]');
        const itemCards = studio.querySelectorAll('[data-bouquet-item-card]');
        const bgSwatches = studio.querySelectorAll('[data-bg-swatch]');
        const summaryListEl = studio.querySelector('[data-canvas-summary-list]');
        const totalEl = studio.querySelector('[data-canvas-total]');
        const itemCountLabelEl = studio.querySelector('[data-canvas-item-count]');
        const notesInput = studio.querySelector('[data-design-notes]');
        const feedbackEl = studio.querySelector('[data-studio-feedback]');
        const submitBtn = studio.querySelector('[data-studio-submit]');
        const resetAllBtn = studio.querySelector('[data-studio-reset]');

        // Toolbar buttons
        const btnForward = studio.querySelector('[data-tool="forward"]');
        const btnBackward = studio.querySelector('[data-tool="backward"]');
        const btnFlip = studio.querySelector('[data-tool="flip"]');
        const btnDuplicate = studio.querySelector('[data-tool="duplicate"]');
        const btnDelete = studio.querySelector('[data-tool="delete"]');
        const btnUndo = studio.querySelector('[data-tool="undo"]');
        const btnRedo = studio.querySelector('[data-tool="redo"]');
        const btnAutoArrange = studio.querySelector('[data-tool="auto-arrange"]');
        const btnClear = studio.querySelector('[data-tool="clear"]');

        // Build catalog dictionary from rendered item cards
        const catalog = new Map();
        itemCards.forEach((card) => {
            const id = String(card.getAttribute('data-product-id') || '');
            if (!id) return;
            catalog.set(id, {
                id: id,
                step: card.getAttribute('data-step') || 'flowers',
                name: card.getAttribute('data-name') || 'Item',
                price: parseFloat(card.getAttribute('data-price') || '0'),
                image: card.getAttribute('data-image-src') || '',
                canvasImage: card.getAttribute('data-canvas-image-src') || '',
                hasExplicitCutout: card.getAttribute('data-has-cutout') === '1',
                stock: parseInt(card.getAttribute('data-stock') || '0', 10),
                unavailable: card.getAttribute('data-unavailable') === '1',
                cardEl: card
            });
        });

        // Initialize Fabric.js canvas
        const canvas = new fabric.Canvas(canvasEl, {
            width: STAGE_W,
            height: STAGE_H,
            backgroundColor: '#ffffff',
            preserveObjectStacking: true,
            selection: true
        });

        // Style selection handles to match botanical emerald theme
        fabric.Object.prototype.set({
            transparentCorners: false,
            cornerColor: '#198754',
            cornerStrokeColor: '#ffffff',
            borderColor: '#198754',
            cornerSize: 11,
            cornerStyle: 'circle',
            padding: 4,
            rotatingPointOffset: 26
        });

        // In-memory cache of processed transparent sticker data URLs
        const cutoutCache = new Map();
        let activeBackgroundColor = '#ffffff';
        let historyStack = [];
        let historyIndex = -1;
        let isRestoringHistory = false;
        let isBusyAdding = false;

        // ---------- Responsive stage sizing ----------
        function resizeStageDisplay() {
            if (!stageWrap) return;
            const availableWidth = Math.max(260, stageWrap.clientWidth - 4);
            const scale = Math.min(1, availableWidth / STAGE_W);
            const cssW = Math.round(STAGE_W * scale);
            const cssH = Math.round(STAGE_H * scale);
            canvas.setDimensions({ width: cssW + 'px', height: cssH + 'px' }, { cssOnly: true });
        }

        resizeStageDisplay();
        window.addEventListener('resize', resizeStageDisplay);

        // ---------- Helper utilities ----------
        function formatPeso(amount) {
            return '₱' + Number(amount || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function showFeedback(message, type, extraHtml) {
            if (!feedbackEl) return;
            const variant = type || 'warning';
            feedbackEl.className = 'alert mt-3 mb-0 alert-' + variant;
            feedbackEl.innerHTML = '';
            const span = document.createElement('div');
            span.textContent = message;
            feedbackEl.appendChild(span);
            if (extraHtml) {
                const extra = document.createElement('div');
                extra.className = 'mt-2';
                extra.innerHTML = extraHtml;
                feedbackEl.appendChild(extra);
            }
        }

        function hideFeedback() {
            if (!feedbackEl) return;
            feedbackEl.className = 'alert d-none mt-3 mb-0';
            feedbackEl.innerHTML = '';
        }

        function isWrapperItem(name) {
            return /(wrapper|wrap|sheet|paper|cellophane)/i.test(String(name || ''));
        }

        function getLayerCategory(meta) {
            if (!meta) return 'flowers';
            if (meta.step === 'container') {
                return isWrapperItem(meta.name) ? 'wrapper' : 'vessel';
            }
            return meta.step || 'flowers';
        }

        function getBouquetObjects() {
            return canvas.getObjects().filter((obj) => obj && obj.bouquetMeta && obj.bouquetMeta.id);
        }

        function countItemsOnCanvas() {
            const byProduct = {};
            const byStep = { container: 0, flowers: 0, fillers: 0, ribbon: 0 };
            let totalCount = 0;
            let totalPrice = 0;

            getBouquetObjects().forEach((obj) => {
                const meta = obj.bouquetMeta;
                const pid = String(meta.id);
                const item = catalog.get(pid);
                const price = item ? item.price : Number(meta.price || 0);
                const step = item ? item.step : (meta.step || 'flowers');

                byProduct[pid] = (byProduct[pid] || 0) + 1;
                if (typeof byStep[step] !== 'number') {
                    byStep[step] = 0;
                }
                byStep[step] += 1;
                totalCount += 1;
                totalPrice += price;
            });

            return { byProduct, byStep, totalCount, totalPrice };
        }

        // ---------- Client-side Smart White-Background Removal ----------
        function loadHtmlImage(src) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => resolve(img);
                img.onerror = () => reject(new Error('Image failed to load: ' + src));
                img.src = src;
            });
        }

        async function prepareCutoutSource(item) {
            const sourceUrl = item.canvasImage || item.image;
            if (!sourceUrl) {
                return 'assets/images/products/wrapper.png';
            }

            // If admin uploaded an explicit transparent PNG cutout, use it directly
            if (item.hasExplicitCutout) {
                return sourceUrl;
            }

            if (cutoutCache.has(sourceUrl)) {
                return cutoutCache.get(sourceUrl);
            }

            try {
                const img = await loadHtmlImage(sourceUrl);
                const maxDim = 560;
                const ratio = Math.min(1, maxDim / Math.max(img.naturalWidth || 1, img.naturalHeight || 1));
                const w = Math.max(1, Math.round((img.naturalWidth || 200) * ratio));
                const h = Math.max(1, Math.round((img.naturalHeight || 200) * ratio));

                const off = document.createElement('canvas');
                off.width = w;
                off.height = h;
                const ctx = off.getContext('2d', { willReadFrequently: true });
                ctx.drawImage(img, 0, 0, w, h);

                const imgData = ctx.getImageData(0, 0, w, h);
                const data = imgData.data;

                // Check if image already has transparent corners
                const cornerCoords = [
                    [2, 2],
                    [w - 3, 2],
                    [2, h - 3],
                    [w - 3, h - 3]
                ];
                let transparentCorners = 0;
                let bgR = 0, bgG = 0, bgB = 0, opaqueCorners = 0;

                cornerCoords.forEach(([cx, cy]) => {
                    const x = Math.max(0, Math.min(w - 1, cx));
                    const y = Math.max(0, Math.min(h - 1, cy));
                    const idx = (y * w + x) * 4;
                    if (data[idx + 3] < 240) {
                        transparentCorners++;
                    } else {
                        bgR += data[idx];
                        bgG += data[idx + 1];
                        bgB += data[idx + 2];
                        opaqueCorners++;
                    }
                });

                // Only run edge-seeded white/light studio backdrop removal on opaque photos with light corners
                if (transparentCorners < 2 && opaqueCorners > 0) {
                    bgR = Math.round(bgR / opaqueCorners);
                    bgG = Math.round(bgG / opaqueCorners);
                    bgB = Math.round(bgB / opaqueCorners);

                    const bgMin = Math.min(bgR, bgG, bgB);
                    const bgMax = Math.max(bgR, bgG, bgB);
                    const bgBrightness = (bgR + bgG + bgB) / 3;
                    const bgChroma = bgMax - bgMin;

                    if (bgBrightness >= 200 && bgChroma <= 42) {
                        const visited = new Uint8Array(w * h);
                        const removed = new Uint8Array(w * h);
                        const queue = new Int32Array(w * h);
                        let head = 0;
                        let tail = 0;

                        const isBgPixel = (pixelIndex) => {
                            const i = pixelIndex * 4;
                            const a = data[i + 3];
                            if (a < 20) return true;
                            const r = data[i];
                            const g = data[i + 1];
                            const b = data[i + 2];
                            const dr = r - bgR;
                            const dg = g - bgG;
                            const db = b - bgB;
                            const distSq = dr * dr + dg * dg + db * db;
                            const maxC = Math.max(r, g, b);
                            const minC = Math.min(r, g, b);
                            const chroma = maxC - minC;
                            const lum = (r + g + b) / 3;
                            return distSq <= 44 * 44 && lum >= 188 && chroma <= 44;
                        };

                        const tryEnqueue = (x, y) => {
                            const p = y * w + x;
                            if (visited[p]) return;
                            visited[p] = 1;
                            if (isBgPixel(p)) {
                                removed[p] = 1;
                                queue[tail++] = p;
                            }
                        };

                        // Seed BFS from all 4 outer edges so white flower petals inside are protected
                        for (let x = 0; x < w; x++) {
                            tryEnqueue(x, 0);
                            tryEnqueue(x, h - 1);
                        }
                        for (let y = 1; y < h - 1; y++) {
                            tryEnqueue(0, y);
                            tryEnqueue(w - 1, y);
                        }

                        while (head < tail) {
                            const p = queue[head++];
                            const x = p % w;
                            const y = (p - x) / w;
                            if (x > 0) tryEnqueue(x - 1, y);
                            if (x < w - 1) tryEnqueue(x + 1, y);
                            if (y > 0) tryEnqueue(x, y - 1);
                            if (y < h - 1) tryEnqueue(x, y + 1);
                        }

                        // Apply alpha removal + gentle 1px edge feathering to prevent white halo
                        for (let p = 0; p < w * h; p++) {
                            if (removed[p]) {
                                data[p * 4 + 3] = 0;
                            }
                        }

                        for (let y = 1; y < h - 1; y++) {
                            for (let x = 1; x < w - 1; x++) {
                                const p = y * w + x;
                                if (removed[p]) continue;
                                const adjRemoved =
                                    removed[p - 1] || removed[p + 1] || removed[p - w] || removed[p + w];
                                if (adjRemoved) {
                                    const i = p * 4;
                                    const r = data[i], g = data[i + 1], b = data[i + 2];
                                    const lum = (r + g + b) / 3;
                                    const chroma = Math.max(r, g, b) - Math.min(r, g, b);
                                    if (lum >= 180 && chroma <= 48) {
                                        data[i + 3] = Math.round(data[i + 3] * 0.35);
                                    }
                                }
                            }
                        }

                        ctx.putImageData(imgData, 0, 0);
                    }
                }

                // Tightly crop transparent margins so selection box hugs the item cleanly
                let minX = w, minY = h, maxX = 0, maxY = 0;
                let foundOpaque = false;
                for (let y = 0; y < h; y++) {
                    for (let x = 0; x < w; x++) {
                        if (data[(y * w + x) * 4 + 3] > 20) {
                            if (x < minX) minX = x;
                            if (x > maxX) maxX = x;
                            if (y < minY) minY = y;
                            if (y > maxY) maxY = y;
                            foundOpaque = true;
                        }
                    }
                }

                let resultUrl;
                if (foundOpaque && maxX > minX && maxY > minY) {
                    const pad = 4;
                    const cx = Math.max(0, minX - pad);
                    const cy = Math.max(0, minY - pad);
                    const cw = Math.min(w - cx, maxX - minX + 1 + pad * 2);
                    const ch = Math.min(h - cy, maxY - minY + 1 + pad * 2);
                    const cropped = document.createElement('canvas');
                    cropped.width = cw;
                    cropped.height = ch;
                    cropped.getContext('2d').drawImage(off, cx, cy, cw, ch, 0, 0, cw, ch);
                    resultUrl = cropped.toDataURL('image/png');
                } else {
                    resultUrl = off.toDataURL('image/png');
                }

                cutoutCache.set(sourceUrl, resultUrl);
                return resultUrl;
            } catch (err) {
                cutoutCache.set(sourceUrl, sourceUrl);
                return sourceUrl;
            }
        }

        // ---------- Smart Placement & Auto-Arrange ----------
        function computeSmartPlacement(item) {
            const objs = getBouquetObjects();
            const layerType = getLayerCategory({ step: item.step, name: item.name });
            const sameTypeCount = objs.filter((o) => getLayerCategory(o.bouquetMeta) === layerType).length;

            if (layerType === 'wrapper') {
                return {
                    left: STAGE_W / 2 + (sameTypeCount % 2 === 0 ? 0 : (sameTypeCount % 4 === 1 ? -18 : 18)),
                    top: STAGE_H * 0.61,
                    targetWidth: 315,
                    angle: sameTypeCount === 0 ? 0 : (sameTypeCount % 2 === 1 ? -8 : 8)
                };
            }

            if (layerType === 'vessel') {
                return {
                    left: STAGE_W / 2,
                    top: STAGE_H * 0.74,
                    targetWidth: 220,
                    angle: 0
                };
            }

            if (layerType === 'ribbon') {
                return {
                    left: STAGE_W / 2 + (sameTypeCount * 14) % 40 - (sameTypeCount > 0 ? 12 : 0),
                    top: STAGE_H * 0.69,
                    targetWidth: 145,
                    angle: 0
                };
            }

            if (layerType === 'fillers') {
                const slots = [
                    { x: -92, y: -42, a: -26 },
                    { x: 92, y: -42, a: 26 },
                    { x: -125, y: 12, a: -38 },
                    { x: 125, y: 12, a: 38 },
                    { x: 0, y: -88, a: 0 },
                    { x: -62, y: -80, a: -16 },
                    { x: 62, y: -80, a: 16 }
                ];
                const slot = slots[sameTypeCount % slots.length];
                return {
                    left: STAGE_W / 2 + slot.x,
                    top: STAGE_H * 0.44 + slot.y,
                    targetWidth: 165,
                    angle: slot.a
                };
            }

            // Default: hero flowers arranged in a lush bouquet fan arc
            const flowerSlots = [
                { x: 0, y: -38, a: 0 },
                { x: -68, y: -18, a: -14 },
                { x: 68, y: -18, a: 14 },
                { x: -38, y: -74, a: -8 },
                { x: 38, y: -74, a: 8 },
                { x: -104, y: 20, a: -24 },
                { x: 104, y: 20, a: 24 },
                { x: 0, y: 16, a: 0 },
                { x: -56, y: 34, a: -12 },
                { x: 56, y: 34, a: 12 }
            ];
            const fSlot = flowerSlots[sameTypeCount % flowerSlots.length];
            const ringOffset = Math.floor(sameTypeCount / flowerSlots.length) * 14;
            return {
                left: STAGE_W / 2 + fSlot.x,
                top: STAGE_H * 0.42 + fSlot.y + ringOffset,
                targetWidth: 155,
                angle: fSlot.a
            };
        }

        function sortCanvasZOrder() {
            const rankMap = {
                wrapper: 10,
                fillers: 20,
                flowers: 30,
                vessel: 35,
                ribbon: 45
            };
            const objs = getBouquetObjects();
            const sorted = objs.slice().sort((a, b) => {
                const rA = rankMap[getLayerCategory(a.bouquetMeta)] || 30;
                const rB = rankMap[getLayerCategory(b.bouquetMeta)] || 30;
                return rA - rB;
            });
            sorted.forEach((obj, idx) => {
                canvas.moveTo(obj, idx);
            });
            canvas.renderAll();
        }

        function autoArrangeBouquet() {
            const objs = getBouquetObjects();
            if (!objs.length) return;

            const buckets = {
                wrapper: [],
                fillers: [],
                flowers: [],
                vessel: [],
                ribbon: []
            };

            objs.forEach((obj) => {
                const cat = getLayerCategory(obj.bouquetMeta);
                if (buckets[cat]) {
                    buckets[cat].push(obj);
                } else {
                    buckets.flowers.push(obj);
                }
            });

            const fitObject = (obj, x, y, targetW, angle) => {
                const naturalW = obj.width || 200;
                const scale = targetW / naturalW;
                obj.set({
                    originX: 'center',
                    originY: 'center',
                    left: x,
                    top: y,
                    scaleX: scale,
                    scaleY: scale,
                    angle: angle,
                    flipX: false
                });
                obj.setCoords();
            };

            // 1. Wrappers behind everything
            buckets.wrapper.forEach((obj, idx) => {
                const offset = buckets.wrapper.length === 1 ? 0 : (idx - (buckets.wrapper.length - 1) / 2) * 28;
                const angle = buckets.wrapper.length === 1 ? 0 : (idx - (buckets.wrapper.length - 1) / 2) * 10;
                fitObject(obj, STAGE_W / 2 + offset, STAGE_H * 0.59, 320, angle);
            });

            // 2. Fillers halo behind hero flowers
            buckets.fillers.forEach((obj, idx) => {
                const count = buckets.fillers.length;
                const spread = count === 1 ? 0 : ((idx / (count - 1)) * 2 - 1);
                const x = STAGE_W / 2 + spread * 120;
                const y = STAGE_H * 0.38 + Math.abs(spread) * 46;
                const angle = spread * 34;
                fitObject(obj, x, y, 165, angle);
            });

            // 3. Hero flowers arranged in a lush dome
            buckets.flowers.forEach((obj, idx) => {
                const count = buckets.flowers.length;
                if (count === 1) {
                    fitObject(obj, STAGE_W / 2, STAGE_H * 0.39, 175, 0);
                    return;
                }
                const row = idx < 5 ? 0 : 1;
                const rowItems = row === 0 ? Math.min(count, 5) : count - 5;
                const posInRow = row === 0 ? idx : idx - 5;
                const t = rowItems === 1 ? 0 : ((posInRow / (rowItems - 1)) * 2 - 1);
                const maxSpread = row === 0 ? 102 : 76;
                const baseY = row === 0 ? STAGE_H * 0.36 : STAGE_H * 0.45;
                const x = STAGE_W / 2 + t * maxSpread;
                const y = baseY + (t * t) * 34;
                const angle = t * 22;
                const targetW = count > 6 ? 132 : 150;
                fitObject(obj, x, y, targetW, angle);
            });

            // 4. Vessels (Basket / Pot) at the base
            buckets.vessel.forEach((obj, idx) => {
                const offset = buckets.vessel.length === 1 ? 0 : (idx - (buckets.vessel.length - 1) / 2) * 30;
                fitObject(obj, STAGE_W / 2 + offset, STAGE_H * 0.74, 225, 0);
            });

            // 5. Ribbon at the tie point on top
            buckets.ribbon.forEach((obj, idx) => {
                const offset = idx * 14;
                fitObject(obj, STAGE_W / 2 + offset, STAGE_H * 0.69, 145, 0);
            });

            sortCanvasZOrder();
            canvas.discardActiveObject();
            canvas.renderAll();
            pushHistory();
            updateToolbarState();
        }

        // ---------- Adding / Removing Items ----------
        async function addItemToCanvas(productId) {
            const item = catalog.get(String(productId));
            if (!item || item.unavailable) {
                return;
            }
            if (isBusyAdding) {
                return;
            }

            const counts = countItemsOnCanvas();
            const currentQty = counts.byProduct[item.id] || 0;
            if (!allowPreorderGlobal && item.stock > 0 && currentQty >= item.stock) {
                showFeedback('Maximum available stock (' + item.stock + ') reached for ' + item.name + '.', 'warning');
                return;
            }
            if (!allowPreorderGlobal && item.stock <= 0) {
                showFeedback(item.name + ' is currently out of stock.', 'warning');
                return;
            }

            isBusyAdding = true;
            hideFeedback();

            try {
                const preparedSrc = await prepareCutoutSource(item);
                const placement = computeSmartPlacement(item);

                await new Promise((resolve, reject) => {
                    fabric.Image.fromURL(
                        preparedSrc,
                        function (imgObj) {
                            if (!imgObj) {
                                reject(new Error('Could not place image on canvas.'));
                                return;
                            }
                            const naturalW = imgObj.width || 200;
                            const scale = placement.targetWidth / naturalW;
                            imgObj.set({
                                originX: 'center',
                                originY: 'center',
                                left: placement.left,
                                top: placement.top,
                                scaleX: scale,
                                scaleY: scale,
                                angle: placement.angle,
                                bouquetMeta: {
                                    id: item.id,
                                    step: item.step,
                                    name: item.name,
                                    price: item.price
                                }
                            });

                            canvas.add(imgObj);
                            sortCanvasZOrder();
                            canvas.setActiveObject(imgObj);
                            canvas.renderAll();
                            resolve();
                        },
                        { crossOrigin: 'anonymous' }
                    );
                });

                pushHistory();
                syncAllUI();
            } catch (err) {
                console.error(err);
                showFeedback('Unable to load image for ' + item.name + '.', 'danger');
            } finally {
                isBusyAdding = false;
            }
        }

        function removeOneItemFromCanvas(productId) {
            const pid = String(productId);
            const matching = getBouquetObjects().filter((obj) => String(obj.bouquetMeta.id) === pid);
            if (!matching.length) return;
            const last = matching[matching.length - 1];
            canvas.remove(last);
            canvas.discardActiveObject();
            canvas.renderAll();
            pushHistory();
            syncAllUI();
        }

        function duplicateSelectedObject() {
            const active = canvas.getActiveObject();
            if (!active || !active.bouquetMeta) return;

            const pid = String(active.bouquetMeta.id);
            const item = catalog.get(pid);
            const counts = countItemsOnCanvas();
            const currentQty = counts.byProduct[pid] || 0;

            if (item && !allowPreorderGlobal && item.stock > 0 && currentQty >= item.stock) {
                showFeedback('Maximum available stock (' + item.stock + ') reached for ' + item.name + '.', 'warning');
                return;
            }

            active.clone(function (cloned) {
                cloned.set({
                    left: Math.min(STAGE_W - 40, (active.left || STAGE_W / 2) + 26),
                    top: Math.min(STAGE_H - 40, (active.top || STAGE_H / 2) + 18),
                    bouquetMeta: Object.assign({}, active.bouquetMeta)
                });
                canvas.add(cloned);
                canvas.setActiveObject(cloned);
                canvas.renderAll();
                pushHistory();
                syncAllUI();
            }, ['bouquetMeta']);
        }

        function deleteSelectedObject() {
            const activeObjects = canvas.getActiveObjects();
            if (!activeObjects || !activeObjects.length) return;
            activeObjects.forEach((obj) => canvas.remove(obj));
            canvas.discardActiveObject();
            canvas.renderAll();
            pushHistory();
            syncAllUI();
        }

        function clearCanvas() {
            const objs = getBouquetObjects();
            if (!objs.length) return;
            objs.forEach((obj) => canvas.remove(obj));
            canvas.discardActiveObject();
            canvas.renderAll();
            pushHistory();
            syncAllUI();
            hideFeedback();
        }

        // ---------- Undo / Redo History ----------
        function pushHistory() {
            if (isRestoringHistory) return;
            const snapshot = JSON.stringify(canvas.toJSON(['bouquetMeta']));
            if (historyIndex >= 0 && historyStack[historyIndex] === snapshot) {
                return;
            }
            historyStack = historyStack.slice(0, historyIndex + 1);
            historyStack.push(snapshot);
            if (historyStack.length > MAX_HISTORY) {
                historyStack.shift();
            }
            historyIndex = historyStack.length - 1;
            updateHistoryButtons();
        }

        function restoreHistory(targetIndex) {
            if (targetIndex < 0 || targetIndex >= historyStack.length) return;
            isRestoringHistory = true;
            historyIndex = targetIndex;
            const json = historyStack[historyIndex];
            canvas.loadFromJSON(json, function () {
                canvas.setBackgroundColor(activeBackgroundColor, function () {
                    canvas.renderAll();
                    isRestoringHistory = false;
                    syncAllUI();
                    updateHistoryButtons();
                });
            });
        }

        function updateHistoryButtons() {
            if (btnUndo) btnUndo.disabled = historyIndex <= 0;
            if (btnRedo) btnRedo.disabled = historyIndex >= historyStack.length - 1;
        }

        function updateToolbarState() {
            const active = canvas.getActiveObject();
            const hasSelection = Boolean(active);
            const hasItems = getBouquetObjects().length > 0;

            if (btnForward) btnForward.disabled = !hasSelection;
            if (btnBackward) btnBackward.disabled = !hasSelection;
            if (btnFlip) btnFlip.disabled = !hasSelection;
            if (btnDuplicate) btnDuplicate.disabled = !hasSelection || !active.bouquetMeta;
            if (btnDelete) btnDelete.disabled = !hasSelection;
            if (btnAutoArrange) btnAutoArrange.disabled = !hasItems;
            if (btnClear) btnClear.disabled = !hasItems;
        }

        // ---------- Sync UI (cards, tabs, checklist, summary, total) ----------
        function syncAllUI() {
            const { byProduct, byStep, totalCount, totalPrice } = countItemsOnCanvas();

            // Stage empty overlay
            if (stageEmpty) {
                stageEmpty.classList.toggle('is-hidden', totalCount > 0);
            }
            if (stageWrap) {
                stageWrap.classList.toggle('has-items', totalCount > 0);
            }
            if (itemCountLabelEl) {
                itemCountLabelEl.textContent = totalCount === 1 ? '1 piece on canvas' : totalCount + ' pieces on canvas';
            }

            // Update left panel cards
            catalog.forEach((item, pid) => {
                const qty = byProduct[pid] || 0;
                const card = item.cardEl;
                card.classList.toggle('is-on-canvas', qty > 0);

                const badge = card.querySelector('[data-item-canvas-badge]');
                if (badge) {
                    badge.classList.toggle('d-none', qty <= 0);
                    badge.textContent = '×' + qty + ' on canvas';
                }

                const addBtn = card.querySelector('[data-item-add-btn]');
                const stepper = card.querySelector('[data-item-stepper]');
                const countEl = card.querySelector('[data-item-stepper-count]');
                const plusBtn = card.querySelector('[data-item-plus]');

                if (addBtn && stepper) {
                    addBtn.classList.toggle('d-none', qty > 0);
                    stepper.classList.toggle('d-none', qty <= 0);
                }
                if (countEl) {
                    countEl.textContent = String(qty);
                }
                if (plusBtn) {
                    const atMaxStock = !allowPreorderGlobal && item.stock > 0 && qty >= item.stock;
                    plusBtn.disabled = atMaxStock || item.unavailable;
                }
            });

            // Update step tab count badges
            stepTabs.forEach((tab) => {
                const stepKey = tab.getAttribute('data-step-tab');
                const countBadge = tab.querySelector('[data-step-tab-count]');
                const c = byStep[stepKey] || 0;
                if (countBadge) {
                    countBadge.textContent = String(c);
                    countBadge.classList.toggle('is-empty', c <= 0);
                }
            });

            // Update checklist pills
            studio.querySelectorAll('[data-check-step]').forEach((pill) => {
                const stepKey = pill.getAttribute('data-check-step');
                const isDone = (byStep[stepKey] || 0) > 0;
                pill.classList.toggle('is-done', isDone);
                const icon = pill.querySelector('i');
                if (icon) {
                    icon.className = isDone ? 'fas fa-check-circle' : 'far fa-circle';
                }
            });

            // Update live itemized summary
            if (summaryListEl) {
                summaryListEl.innerHTML = '';
                const productIds = Object.keys(byProduct);
                if (!productIds.length) {
                    const emptyRow = document.createElement('div');
                    emptyRow.className = 'text-muted small py-1';
                    emptyRow.textContent = 'No items on your canvas yet. Pick a container, flowers, and fillers on the left to start designing!';
                    summaryListEl.appendChild(emptyRow);
                } else {
                    productIds.forEach((pid) => {
                        const qty = byProduct[pid];
                        const item = catalog.get(pid);
                        const name = item ? item.name : 'Component #' + pid;
                        const unitPrice = item ? item.price : 0;

                        const row = document.createElement('div');
                        row.className = 'bouquet-summary-row';

                        const leftSpan = document.createElement('span');
                        leftSpan.innerHTML = '<strong>' + qty + ' ×</strong> ';
                        leftSpan.appendChild(document.createTextNode(name));

                        const rightSpan = document.createElement('span');
                        rightSpan.className = 'fw-semibold text-success';
                        rightSpan.textContent = formatPeso(unitPrice * qty);

                        row.appendChild(leftSpan);
                        row.appendChild(rightSpan);
                        summaryListEl.appendChild(row);
                    });
                }
            }

            if (totalEl) {
                totalEl.textContent = formatPeso(totalPrice);
            }

            updateToolbarState();
        }

        // ---------- Free Tab Switching & Filters ----------
        function switchToTab(stepKey) {
            stepTabs.forEach((tab) => {
                tab.classList.toggle('is-active', tab.getAttribute('data-step-tab') === stepKey);
            });
            stepPanes.forEach((pane) => {
                pane.classList.toggle('is-active', pane.getAttribute('data-step-pane') === stepKey);
            });
        }

        stepTabs.forEach((tab) => {
            tab.addEventListener('click', function () {
                switchToTab(tab.getAttribute('data-step-tab'));
            });
        });

        studio.querySelectorAll('[data-check-step]').forEach((pill) => {
            pill.addEventListener('click', function () {
                switchToTab(pill.getAttribute('data-check-step'));
            });
        });

        filterButtons.forEach((btn) => {
            btn.addEventListener('click', function () {
                const stepKey = btn.getAttribute('data-filter-step');
                const value = btn.getAttribute('data-filter-value');
                studio.querySelectorAll('[data-filter-step="' + stepKey + '"]').forEach((b) => {
                    b.classList.toggle('active', b === btn);
                });
                studio.querySelectorAll('[data-bouquet-item-card][data-step="' + stepKey + '"]').forEach((card) => {
                    const cardFilter = card.getAttribute('data-filter-value') || '';
                    const show = value === 'all' || cardFilter === value;
                    card.classList.toggle('d-none', !show);
                });
            });
        });

        // ---------- Picker Item Actions ----------
        studio.addEventListener('click', function (event) {
            const addBtn = event.target.closest('[data-item-add-btn]');
            if (addBtn && !addBtn.disabled) {
                const card = addBtn.closest('[data-bouquet-item-card]');
                if (card) addItemToCanvas(card.getAttribute('data-product-id'));
                return;
            }

            const plusBtn = event.target.closest('[data-item-plus]');
            if (plusBtn && !plusBtn.disabled) {
                const card = plusBtn.closest('[data-bouquet-item-card]');
                if (card) addItemToCanvas(card.getAttribute('data-product-id'));
                return;
            }

            const minusBtn = event.target.closest('[data-item-minus]');
            if (minusBtn && !minusBtn.disabled) {
                const card = minusBtn.closest('[data-bouquet-item-card]');
                if (card) removeOneItemFromCanvas(card.getAttribute('data-product-id'));
                return;
            }
        });

        // ---------- Canvas Background Swatches ----------
        bgSwatches.forEach((swatch) => {
            swatch.addEventListener('click', function () {
                const color = swatch.getAttribute('data-bg-swatch') || '#ffffff';
                activeBackgroundColor = color;
                bgSwatches.forEach((s) => s.classList.toggle('is-active', s === swatch));
                canvas.setBackgroundColor(color, function () {
                    canvas.renderAll();
                });
            });
        });

        // ---------- Toolbar Handlers ----------
        if (btnForward) {
            btnForward.addEventListener('click', function () {
                const active = canvas.getActiveObject();
                if (!active) return;
                canvas.bringForward(active);
                canvas.renderAll();
                pushHistory();
            });
        }

        if (btnBackward) {
            btnBackward.addEventListener('click', function () {
                const active = canvas.getActiveObject();
                if (!active) return;
                canvas.sendBackwards(active);
                canvas.renderAll();
                pushHistory();
            });
        }

        if (btnFlip) {
            btnFlip.addEventListener('click', function () {
                const active = canvas.getActiveObject();
                if (!active) return;
                active.set('flipX', !active.flipX);
                canvas.renderAll();
                pushHistory();
            });
        }

        if (btnDuplicate) {
            btnDuplicate.addEventListener('click', duplicateSelectedObject);
        }

        if (btnDelete) {
            btnDelete.addEventListener('click', deleteSelectedObject);
        }

        if (btnUndo) {
            btnUndo.addEventListener('click', function () {
                restoreHistory(historyIndex - 1);
            });
        }

        if (btnRedo) {
            btnRedo.addEventListener('click', function () {
                restoreHistory(historyIndex + 1);
            });
        }

        if (btnAutoArrange) {
            btnAutoArrange.addEventListener('click', autoArrangeBouquet);
        }

        if (btnClear) {
            btnClear.addEventListener('click', clearCanvas);
        }

        if (resetAllBtn) {
            resetAllBtn.addEventListener('click', function () {
                clearCanvas();
                if (notesInput) notesInput.value = '';
                switchToTab('container');
            });
        }

        // ---------- Fabric Events & Keyboard Shortcuts ----------
        canvas.on('selection:created', updateToolbarState);
        canvas.on('selection:updated', updateToolbarState);
        canvas.on('selection:cleared', updateToolbarState);
        canvas.on('object:modified', function () {
            pushHistory();
        });

        document.addEventListener('keydown', function (event) {
            const tag = (event.target && event.target.tagName) ? event.target.tagName.toLowerCase() : '';
            if (tag === 'input' || tag === 'textarea' || tag === 'select' || (event.target && event.target.isContentEditable)) {
                return;
            }

            if ((event.key === 'Delete' || event.key === 'Backspace') && canvas.getActiveObject()) {
                event.preventDefault();
                deleteSelectedObject();
                return;
            }

            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') {
                if (event.shiftKey) {
                    event.preventDefault();
                    restoreHistory(historyIndex + 1);
                } else {
                    event.preventDefault();
                    restoreHistory(historyIndex - 1);
                }
                return;
            }

            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'y') {
                event.preventDefault();
                restoreHistory(historyIndex + 1);
            }
        });

        // ---------- Submit Custom Bouquet to Cart ----------
        if (submitBtn) {
            submitBtn.addEventListener('click', async function () {
                hideFeedback();

                if (!isAuthenticated) {
                    window.location.href = 'login.php';
                    return;
                }

                const { byProduct, byStep } = countItemsOnCanvas();
                const stepLabels = {
                    container: 'Container',
                    flowers: 'Flowers',
                    fillers: 'Fillers',
                    ribbon: 'Ribbon'
                };

                const missingSteps = requiredSteps.filter((step) => (byStep[step] || 0) <= 0);
                if (missingSteps.length) {
                    const labels = missingSteps.map((s) => stepLabels[s] || s);
                    showFeedback('Please add at least one item from: ' + labels.join(', ') + ' before adding to cart.', 'warning');
                    switchToTab(missingSteps[0]);
                    return;
                }

                const itemsPayload = Object.keys(byProduct).map((pid) => {
                    const item = catalog.get(pid);
                    return {
                        product_id: parseInt(pid, 10),
                        quantity: byProduct[pid],
                        step: item ? item.step : 'flowers'
                    };
                });

                if (!itemsPayload.length) {
                    showFeedback('Please place items on the bouquet canvas first.', 'warning');
                    return;
                }

                const originalHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving Design...';

                try {
                    // Clear active selection box before capturing clean snapshot
                    canvas.discardActiveObject();
                    canvas.renderAll();

                    const previewDataUrl = canvas.toDataURL({
                        format: 'png',
                        multiplier: 2
                    });
                    const canvasJson = canvas.toJSON(['bouquetMeta']);
                    const notes = notesInput ? notesInput.value.trim() : '';

                    const response = await fetch('save-custom-bouquet.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            items: itemsPayload,
                            preview_data_url: previewDataUrl,
                            canvas_json: canvasJson,
                            notes: notes
                        })
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error((data && data.error) ? data.error : 'Failed to save your custom bouquet.');
                    }

                    // Update or create navbar cart badge if present
                    if (typeof data.cart_count === 'number' && data.cart_count > 0) {
                        const existingBadges = document.querySelectorAll('#cart-count, .cart-count-badge, [data-cart-count]');
                        if (existingBadges.length) {
                            existingBadges.forEach((badge) => {
                                badge.textContent = String(data.cart_count);
                            });
                        } else {
                            const cartNavLink = document.querySelector('a.nav-link[href="cart.php"]');
                            if (cartNavLink) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'badge bg-danger cart-count-badge ms-1';
                                newBadge.textContent = String(data.cart_count);
                                cartNavLink.appendChild(newBadge);
                            }
                        }
                    }

                    showFeedback(
                        data.message || 'Your custom bouquet design has been added to your cart!',
                        'success',
                        '<a href="cart.php" class="btn btn-success btn-sm"><i class="fas fa-shopping-cart me-1"></i>View Cart &amp; Checkout</a>'
                    );
                } catch (err) {
                    showFeedback(err.message || 'Unable to save custom bouquet. Please try again.', 'danger');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                }
            });
        }

        // Initial snapshot & UI sync
        pushHistory();
        syncAllUI();
    });
})();

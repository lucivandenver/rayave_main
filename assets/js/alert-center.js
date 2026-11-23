(function () {
    var TYPE_META = {
        success: {
            title: 'Success',
            icon: 'fas fa-circle-check text-success',
            button: 'btn-success'
        },
        danger: {
            title: 'Something Went Wrong',
            icon: 'fas fa-circle-exclamation text-danger',
            button: 'btn-danger'
        },
        warning: {
            title: 'Heads Up',
            icon: 'fas fa-triangle-exclamation text-warning',
            button: 'btn-warning'
        },
        info: {
            title: 'Notice',
            icon: 'fas fa-circle-info text-primary',
            button: 'btn-primary'
        }
    };

    var queue = Array.isArray(window.__queuedAlerts) ? window.__queuedAlerts : [];
    window.__queuedAlerts = queue;
    var modalEl = null;
    var modalInstance = null;
    var iconEl = null;
    var titleEl = null;
    var messageEl = null;
    var actionBtn = null;
    var actionBaseClass = '';
    var isModalVisible = false;
    var isReady = false;

    function inferTypeFromElement(el) {
        if (!el || !el.classList) {
            return 'info';
        }
        if (el.classList.contains('alert-success')) {
            return 'success';
        }
        if (el.classList.contains('alert-danger')) {
            return 'danger';
        }
        if (el.classList.contains('alert-warning')) {
            return 'warning';
        }
        if (el.classList.contains('alert-info')) {
            return 'info';
        }
        return 'info';
    }

    function normalizePayload(payload) {
        if (!payload) {
            return null;
        }
        var type = (payload.type || 'info').toLowerCase();
        if (!TYPE_META[type]) {
            type = 'info';
        }
        var normalized = {
            type: type,
            title: payload.title || TYPE_META[type].title,
            message: (payload.message || '').toString().trim(),
            html: payload.html === true,
            htmlMessage: payload.htmlMessage || '',
            dismissText: payload.dismissText || 'Close'
        };

        if (!normalized.message && !normalized.htmlMessage) {
            return null;
        }
        return normalized;
    }

    function enqueueAlert(payload) {
        var normalized = normalizePayload(payload);
        if (!normalized) {
            return;
        }
        queue.push(normalized);
        if (isReady) {
            processQueue();
        }
    }

    function hydrateDomAlerts(root) {
        var scope = root || document;
        var nodes = scope.querySelectorAll('[data-modal-alert]:not([data-alert-processed="1"])');
        nodes.forEach(function (el) {
            var type = el.getAttribute('data-alert-type') || inferTypeFromElement(el);
            var title = el.getAttribute('data-alert-title');
            var dismissText = el.getAttribute('data-alert-dismiss');
            var payload = {
                type: type,
                title: title,
                dismissText: dismissText,
                html: true,
                htmlMessage: el.innerHTML.trim()
            };
            enqueueAlert(payload);
            el.setAttribute('data-alert-processed', '1');
            el.classList.add('d-none');
        });
    }

    function renderAlert(alertData) {
        if (!modalEl) {
            return;
        }
        var meta = TYPE_META[alertData.type] || TYPE_META.info;
        if (titleEl) {
            titleEl.textContent = alertData.title || meta.title;
        }
        if (iconEl) {
            iconEl.className = meta.icon;
        }
        if (messageEl) {
            if (alertData.html || alertData.htmlMessage) {
                messageEl.innerHTML = alertData.htmlMessage || alertData.message;
            } else {
                messageEl.textContent = alertData.message;
            }
        }
        if (actionBtn) {
            actionBtn.textContent = alertData.dismissText || 'Close';
            var base = actionBaseClass ? actionBaseClass + ' ' : '';
            actionBtn.className = base + meta.button;
        }
    }

    function processQueue() {
        if (!isReady || isModalVisible || !modalInstance) {
            return;
        }
        if (!queue.length) {
            return;
        }
        var next = queue.shift();
        renderAlert(next);
        modalInstance.show();
        isModalVisible = true;
    }

    function initializeModal() {
        modalEl = document.getElementById('globalAlertModal');
        if (!modalEl) {
            return;
        }
        iconEl = modalEl.querySelector('[data-alert-icon]');
        titleEl = modalEl.querySelector('[data-alert-title]');
        messageEl = modalEl.querySelector('[data-alert-message]');
        actionBtn = modalEl.querySelector('[data-alert-accept]');
        actionBaseClass = actionBtn ? actionBtn.className.replace(/\s*(btn-(success|danger|warning|primary))\b/g, '').trim() : '';
        if (actionBaseClass && actionBaseClass.indexOf('btn') === -1) {
            actionBaseClass = 'btn ' + actionBaseClass;
        }
        if (!actionBaseClass && actionBtn) {
            actionBaseClass = 'btn';
        }
        modalInstance = new bootstrap.Modal(modalEl);
        modalEl.addEventListener('hidden.bs.modal', function () {
            isModalVisible = false;
            processQueue();
        });
        isReady = true;
        hydrateDomAlerts(document);
        processQueue();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeModal();
    });

    window.showGlobalAlert = enqueueAlert;
    window.AlertCenter = {
        enqueue: enqueueAlert,
        hydrate: hydrateDomAlerts
    };
})();

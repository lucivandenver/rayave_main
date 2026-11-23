(function () {
    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    ready(function () {
        var modalEl = document.getElementById('globalConfirmModal');
        if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return;
        }

        var modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
        var titleEl = modalEl.querySelector('[data-confirm-title]');
        var messageEl = modalEl.querySelector('[data-confirm-message]');
        var acceptBtn = modalEl.querySelector('[data-confirm-accept]');
        var cancelBtn = modalEl.querySelector('[data-confirm-cancel]');
        var defaultTitle = 'Please Confirm';
        var defaultMessage = 'Are you sure you want to continue?';
        var defaultAccept = 'Yes, continue';
        var pendingAction = null;

        function setButtonVariant(variant) {
            var variants = ['btn-primary', 'btn-success', 'btn-warning', 'btn-secondary', 'btn-danger'];
            variants.forEach(function (cls) { acceptBtn.classList.remove(cls); });

            switch (variant) {
                case 'success':
                    acceptBtn.classList.add('btn-success');
                    break;
                case 'warning':
                    acceptBtn.classList.add('btn-warning');
                    break;
                case 'secondary':
                    acceptBtn.classList.add('btn-secondary');
                    break;
                case 'primary':
                    acceptBtn.classList.add('btn-primary');
                    break;
                default:
                    acceptBtn.classList.add('btn-danger');
            }
        }

        function triggerAction(trigger) {
            var tag = trigger.tagName.toLowerCase();
            var form = tag === 'form' ? trigger : trigger.closest('form');

            if (tag === 'a') {
                var href = trigger.getAttribute('href');
                var target = trigger.getAttribute('target');
                return function () {
                    if (!href) {
                        return;
                    }
                    if (target === '_blank') {
                        window.open(href, '_blank');
                    } else {
                        window.location.href = href;
                    }
                };
            }

            if (tag === 'form') {
                return function () {
                    trigger.setAttribute('data-skip-confirm', '1');
                    if (typeof trigger.requestSubmit === 'function') {
                        trigger.requestSubmit();
                    } else {
                        trigger.submit();
                    }
                };
            }

            if (form) {
                return function () {
                    form.setAttribute('data-skip-confirm', '1');
                    var isSubmitControl = trigger.tagName === 'BUTTON' || (trigger.tagName === 'INPUT' && trigger.type === 'submit');

                    if (typeof form.requestSubmit === 'function') {
                        if (isSubmitControl) {
                            form.requestSubmit(trigger);
                        } else {
                            form.requestSubmit();
                        }
                    } else {
                        form.submit();
                    }
                };
            }

            return function () {};
        }

        function openConfirmModal(event, trigger) {
            event.preventDefault();

            var title = trigger.getAttribute('data-confirm-title') || defaultTitle;
            var message = trigger.getAttribute('data-confirm') || defaultMessage;
            var acceptLabel = trigger.getAttribute('data-confirm-accept') || defaultAccept;
            var variant = trigger.getAttribute('data-confirm-variant') || 'danger';

            titleEl.textContent = title;
            messageEl.textContent = message;
            acceptBtn.textContent = acceptLabel;
            setButtonVariant(variant);

            pendingAction = triggerAction(trigger);
            modal.show();
        }

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-confirm]');
            if (!trigger || trigger.tagName === 'FORM') {
                return;
            }

            openConfirmModal(event, trigger);
        }, true);

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form.matches('[data-confirm]')) {
                return;
            }

            if (form.getAttribute('data-skip-confirm') === '1') {
                form.removeAttribute('data-skip-confirm');
                return;
            }

            openConfirmModal(event, form);
        }, true);

        acceptBtn.addEventListener('click', function () {
            if (typeof pendingAction === 'function') {
                var action = pendingAction;
                pendingAction = null;
                modal.hide();
                action();
            }
        });

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                pendingAction = null;
            });
        }

        modalEl.addEventListener('hidden.bs.modal', function () {
            pendingAction = null;
        });
    });
})();

</main>
</div>
</div>

<div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-labelledby="adminConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adminConfirmTitle" data-confirm-title>Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" data-confirm-message>Please confirm this action.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-confirm-cancel>Cancel</button>
                <button type="button" class="btn btn-danger" data-confirm-accept>Yes, continue</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="globalAlertModal" tabindex="-1" aria-labelledby="adminAlertTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <span class="fs-1" data-alert-icon></span>
                    <div>
                        <h5 class="modal-title mb-1" id="adminAlertTitle" data-alert-title>Alert</h5>
                        <p class="text-muted small mb-0">System updates and form feedback appear here.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="lead mb-0" data-alert-message></div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-success w-100 shadow-sm" data-alert-accept data-bs-dismiss="modal">Continue</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js for dashboard -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/alert-center.js"></script>
<script src="../assets/js/confirm-modal.js"></script>

<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>
<?php
if (!empty($pageScripts)) {
    echo $pageScripts;
}
?>
</body>

</html>
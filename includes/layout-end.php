</div> <!-- Close container-fluid -->
        </div> <!-- Close main-content -->
    </div> <!-- Close app-container -->

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom sidebar toggle script for responsive screens -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toggleBtn = document.getElementById("sidebarToggle");
            const sidebar = document.querySelector(".sidebar");
            const mainContent = document.querySelector(".main-content");

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener("click", function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle("show");
                });

                if (mainContent) {
                    mainContent.addEventListener("click", function() {
                        if (sidebar.classList.contains("show")) {
                            sidebar.classList.remove("show");
                        }
                    });
                }
            }
        });
    </script>

    <?php if (isset($_SESSION['flash_msg'])): ?>
    <!-- Premium Glassmorphic Toast Notification -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
        <div id="liveToast" class="toast align-items-center text-white border-0 shadow-lg rounded-3" role="alert" aria-live="assertive" aria-atomic="true" style="background-color: rgba(15, 23, 42, 0.95); border: 1px solid rgba(255, 255, 255, 0.08) !important; backdrop-filter: blur(8px); min-width: 250px;">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2.5 py-3">
                    <?php
                    $type = $_SESSION['flash_type'] ?? 'success';
                    $icon = 'bi-check-circle-fill text-success';
                    if ($type === 'danger') $icon = 'bi-exclamation-octagon-fill text-danger';
                    if ($type === 'warning') $icon = 'bi-exclamation-triangle-fill text-warning';
                    if ($type === 'info') $icon = 'bi-info-circle-fill text-info';
                    ?>
                    <i class="bi <?php echo $icon; ?> fs-5"></i>
                    <span class="fw-medium text-light" style="font-size: 13.5px;"><?php echo htmlspecialchars($_SESSION['flash_msg']); ?></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2.5 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toastEl = document.getElementById('liveToast');
            if (toastEl) {
                const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
                toast.show();
            }
        });
    </script>
    <?php
    unset($_SESSION['flash_msg']);
    unset($_SESSION['flash_type']);
    ?>
    <?php endif; ?>

    <!-- Global Delete Confirmation Modal -->
    <div class="modal fade" id="globalDeleteConfirmModal" tabindex="-1" aria-labelledby="globalDeleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
            <div class="modal-content border-0 shadow rounded-4 p-3">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold d-flex align-items-center gap-2" id="globalDeleteConfirmModalLabel">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i> Confirm Deletion
                    </h6>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p id="globalDeleteModalBodyText" class="text-secondary mb-0" style="font-size: 14px; line-height: 1.6;">Are you sure you want to delete this item? This action is permanent and cannot be undone.</p>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light border flex-grow-1 py-2.5 rounded-3 fw-semibold" data-bs-dismiss="modal" style="font-size: 13px;">Cancel</button>
                    <a id="globalDeleteModalBtn" href="#" class="btn btn-sm btn-danger flex-grow-1 py-2.5 rounded-3 fw-bold" style="font-size: 13px;">Delete Item</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        function triggerGlobalDeleteModal(deleteUrl, messageText = 'Are you sure you want to delete this item? This action is permanent and cannot be undone.') {
            const modalEl = document.getElementById('globalDeleteConfirmModal');
            if (modalEl) {
                const modal = new bootstrap.Modal(modalEl);
                document.getElementById('globalDeleteModalBodyText').innerHTML = messageText;
                document.getElementById('globalDeleteModalBtn').href = deleteUrl;
                modal.show();
            }
        }
    </script>
</body>
</html>
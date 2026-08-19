<!-- Toast Notification System -->
<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
</div>

<?php if (method_exists($meetlog, 'getToasts')): ?>
    <?php $toasts = $meetlog->getToasts(); ?>
    <?php if (!empty($toasts)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                <?php foreach ($toasts as $toast): ?>
                    showToast(<?= json_encode($toast['message']) ?>, <?= json_encode($toast['type']) ?>);
                <?php endforeach; ?>
            });
        </script>
    <?php endif; ?>
<?php endif; ?>

<script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');

        // Toast-Element erstellen
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0 show`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.style.marginBottom = '10px';
        toast.style.minWidth = '300px';

        toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

        container.appendChild(toast);

        // Auto-hide nach 5 Sekunden
        setTimeout(function () {
            if (toast.parentNode) {
                toast.classList.add('fade');
                setTimeout(function () {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 150);
            }
        }, 5000);

        // Manual close
        const closeBtn = toast.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                if (toast.parentNode) {
                    toast.classList.add('fade');
                    setTimeout(function () {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 150);
                }
            });
        }
    }
</script>
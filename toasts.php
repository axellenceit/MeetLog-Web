<!-- Toast Notification System -->
<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
</div>

<?php if (isset($flash)): ?>
    <?php $toasts = $flash->getToasts(); ?>
    <?php if (!empty($toasts)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php foreach ($toasts as $toast): ?>
                    showToast(
                        <?= json_encode($toast['message'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        <?= json_encode($toast['type'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
                    );
                <?php endforeach; ?>
            });
        </script>
    <?php endif; ?>
<?php endif; ?>

<script>
    function showToast(message, type = 'success') {
        if (typeof message === 'string') {
            message = message.replace(/\\r\\n|\\n|\\r/g, '\n');
        }
        const container = document.getElementById('toast-container');

        // Toast-Element erstellen
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0 show`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.style.marginBottom = '10px';
        toast.style.minWidth = '300px';

        const layout = document.createElement('div');
        layout.className = 'd-flex';

        const body = document.createElement('div');
        body.className = 'toast-body';
        body.style.whiteSpace = 'pre-line';
        body.textContent = message;

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close btn-close-white me-2 m-auto';
        closeBtn.setAttribute('aria-label', 'Close');

        layout.appendChild(body);
        layout.appendChild(closeBtn);
        toast.appendChild(layout);

        container.appendChild(toast);

        // Auto-hide nach 5 Sekunden
        setTimeout(function() {
            if (toast.parentNode) {
                toast.classList.add('fade');
                setTimeout(function() {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 150);
            }
        }, 5000);

        // Manual close
        closeBtn.addEventListener('click', function() {
            if (toast.parentNode) {
                toast.classList.add('fade');
                setTimeout(function() {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 150);
            }
        });
    }
</script>
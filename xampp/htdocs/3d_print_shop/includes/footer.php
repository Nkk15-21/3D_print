</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> 3D Print Shop. Сервис услуг 3D-печати.</p>
    </div>
</footer>

<div class="modal-overlay" id="confirmModal">
    <div class="confirm-modal">
        <div class="confirm-modal__icon">!</div>
        <h3 class="confirm-modal__title" id="confirmModalTitle">Подтверждение</h3>
        <p class="confirm-modal__text" id="confirmModalText">Вы уверены, что хотите продолжить?</p>

        <div class="confirm-modal__actions">
            <button type="button" class="btn btn-secondary" id="confirmModalCancel">Отмена</button>
            <button type="button" class="btn btn-danger" id="confirmModalOk">Подтвердить</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('confirmModal');
        const modalTitle = document.getElementById('confirmModalTitle');
        const modalText = document.getElementById('confirmModalText');
        const modalOk = document.getElementById('confirmModalOk');
        const modalCancel = document.getElementById('confirmModalCancel');

        let targetUrl = null;

        document.querySelectorAll('[data-confirm="true"]').forEach(link => {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                targetUrl = this.getAttribute('href');

                const title = this.dataset.confirmTitle || 'Подтверждение';
                const text = this.dataset.confirmText || 'Вы уверены, что хотите продолжить?';
                const buttonText = this.dataset.confirmButton || 'Подтвердить';

                modalTitle.textContent = title;
                modalText.textContent = text;
                modalOk.textContent = buttonText;

                modal.classList.add('active');
                document.body.classList.add('modal-open');
            });
        });

        function closeModal() {
            modal.classList.remove('active');
            document.body.classList.remove('modal-open');
            targetUrl = null;
        }

        modalCancel.addEventListener('click', closeModal);

        modalOk.addEventListener('click', function () {
            if (targetUrl) {
                window.location.href = targetUrl;
            }
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('active')) {
                closeModal();
            }
        });
    })();
</script>
</body>
</html>

window.PWQB = window.PWQB || {};

window.PWQB.contact = {
    init() {
        // Активация кнопки после согласия.
        document.addEventListener('change', (event) => {
            if (!event.target.matches('[data-pwqb-contact-consent]')) {
                return;
            }

            const form = event.target.closest('[data-pwqb-contact-form]');
            const submitButton = form.querySelector('[data-pwqb-contact-submit]');

            submitButton.disabled = !event.target.checked;
        });

        // Отправка открытой заявки.
        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('[data-pwqb-contact-form]');

            if (!form) {
                return;
            }

            event.preventDefault();

            const quiz = form.closest('.pwqb-quiz');
            const submitButton = form.querySelector('[data-pwqb-contact-submit]');
            const phoneInput = form.querySelector('[data-pwqb-contact-phone]');
            const consent = form.querySelector('[data-pwqb-contact-consent]');

            const message = form.querySelector('[data-pwqb-contact-message]');

            if (!consent.checked) {
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Отправка...';

            const formData = new FormData();

            formData.append('action', 'pwqb_submit_open_quiz');
            formData.append('pwqb_submission_nonce', PWQBQuiz.openSubmissionNonce);
            formData.append('quiz_id', quiz.dataset.quizId);
            formData.append('pwqb_started_at', quiz.dataset.startedAt);
            formData.append('pwqb_start_token', quiz.dataset.startToken);
            formData.append('phone', phoneInput.value);
            formData.append('consent', '1');
            formData.append('product_ids', quiz.dataset.productIds || '[]');
            formData.append('attributes', quiz.dataset.attributes || '{}');
            formData.append('answers', quiz.dataset.answers || '{}');

            try {
                const response = await fetch(PWQBQuiz.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.data?.message || 'Не удалось отправить заявку.');
                }

                if (message) {
                    message.textContent = data.data.message;
                    message.hidden = false;
                }

                submitButton.textContent = 'Отправлено';
                phoneInput.disabled = true;
                consent.disabled = true;

            } catch (error) {
                if (message) {
                    message.textContent = error.message;
                    message.hidden = false;
                }

                submitButton.disabled = false;
                submitButton.textContent = 'Отправить';
            }
        });
    },
};

window.PWQB.contact.init();

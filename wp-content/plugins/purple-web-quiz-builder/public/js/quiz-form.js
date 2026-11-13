window.PWQB = window.PWQB || {};

window.PWQB.form = {
    /**
     * Переключение полей мессенджеров.
     */
    updateMessengerFields(config) {
        const needsMessenger = ['messenger', 'both'].includes(
            config.sendToSelect.value
        );

        config.messengerFields.hidden = !needsMessenger;

        config.messengerCheckboxes.forEach((checkbox) => {
            checkbox.required = false;
        });

        config.phoneInput.required = needsMessenger;

        if (!needsMessenger) {
            config.messengerCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });

            config.phoneInput.value = '';
        }
    },

    /**
     * Обработка отправки формы.
     */
    bindSubmit(config) {
        config.submissionForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const needsMessenger = ['messenger', 'both'].includes(
                config.sendToSelect.value
            );

            if (needsMessenger) {
                const hasMessenger = [...config.messengerCheckboxes].some(
                    (checkbox) => checkbox.checked
                );

                if (!hasMessenger) {
                    alert('Выберите хотя бы один мессенджер.');
                    return;
                }

                if (!config.phoneInput.value.trim()) {
                    config.phoneInput.focus();
                    return;
                }
            }

            const submitButton = config.submissionForm.querySelector('[type="submit"]');
            const message = config.submissionForm.querySelector('[data-pwqb-form-message]');

            const originalButtonText = submitButton.textContent;

            submitButton.disabled = true;
            submitButton.textContent = 'Отправка...';

            message.hidden = true;
            message.textContent = '';

            const formData = new FormData(config.submissionForm);

            formData.append('action', 'pwqb_submit_quiz');
            formData.append('quiz_id', config.quiz.dataset.quizId);
            formData.append('answers', JSON.stringify(config.answers));
            formData.append('quiz_started_at', config.quizStartedAt);

            try {
                const response = await fetch(PWQBQuiz.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(
                        data.data?.message || 'Не удалось отправить заявку.'
                    );
                }

                config.submissionForm.parentNode.insertBefore(
                    message,
                    config.submissionForm
                );

                message.textContent = data.data.message;
                message.hidden = false;
                message.classList.add('is-success');

                const resultDescription = config.quiz.querySelector(
                    '.pwqb-quiz__result-description'
                );

                if (resultDescription) {
                    resultDescription.hidden = true;
                }

                config.submissionForm.hidden = true;

                config.submissionForm.querySelectorAll(
                    'input, select, textarea, button'
                ).forEach((field) => {
                    field.disabled = true;
                });

            } catch (error) {
                message.textContent = error.message;
                message.hidden = false;

                message.classList.remove('is-success');

                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            }
        });
    },
};
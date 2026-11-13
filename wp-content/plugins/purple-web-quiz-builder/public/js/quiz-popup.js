
window.PWQB = window.PWQB || {};

/**
 * Попап с динамической загрузкой квизов.
 */
window.PWQB.popup = {
    init() {
        const popup = document.querySelector('[data-pwqb-popup]');

        if (!popup) {
            return;
        }

        const content = popup.querySelector('[data-pwqb-popup-content]');

        /**
         * Закрытие попапа.
         */
        const closePopup = () => {
            popup.hidden = true;
            document.body.style.overflow = '';
            content.innerHTML = '';
        };

        /**
         * Открытие квиза.
         */
        const openQuiz = async (quizId) => {
            popup.hidden = false;
            document.body.style.overflow = 'hidden';

            content.innerHTML = `
                <span class="loading-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            `;

            const formData = new FormData();

            formData.append('action', PWQBQuiz.loadQuizAction);
            formData.append('quiz_id', quizId);

            try {
                const response = await fetch(PWQBQuiz.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(
                        data.data?.message || 'Не удалось загрузить квиз.'
                    );
                }

                content.innerHTML = data.data.html;

                const quiz = content.querySelector('.pwqb-quiz');

                if (quiz) {
                    window.PWQBInitQuiz(quiz);
                }

            } catch (error) {
                content.textContent = error.message;
            }
        };

        /**
         * Обработка ссылок вида #quiz-123.
         */
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href^="#quiz-"]');

            if (!link) {
                return;
            }

            const match = link.getAttribute('href').match(/^#quiz-(\d+)$/);

            if (!match) {
                return;
            }

            event.preventDefault();

            openQuiz(match[1]);
        });

        /**
         * Закрытие по кнопке или клику на затемнение.
         */
        popup.addEventListener('click', (event) => {
            if (event.target.closest('[data-pwqb-popup-close]')) {
                closePopup();
            }
        });

        /**
         * Закрытие клавишей Escape.
         */
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !popup.hidden) {
                closePopup();
            }
        });
    },
};

document.addEventListener('DOMContentLoaded', () => {
    PWQB.popup.init();
});

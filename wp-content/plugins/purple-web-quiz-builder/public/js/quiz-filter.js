
window.PWQB = window.PWQB || {};

window.PWQB.filter = {
    /**
     * Получение доступных значений атрибутов по выбранным категориям.
     */
    async getAvailableAttributes(categories) {
        const formData = new FormData();

        formData.append('action', 'pwqb_get_available_attributes');
        formData.append('nonce', PWQBQuiz.productsNonce);
        formData.append('categories', JSON.stringify(categories));

        const response = await fetch(PWQBQuiz.ajaxUrl, {
            method: 'POST',
            body: formData,
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(
                data.data?.message || 'Не удалось получить доступные атрибуты.'
            );
        }

        return data.data.attributes;
    },

    /**
     * Проверка, содержит ли ответ доступные значения атрибутов.
     */
    isAnswerAvailable(answer, availableAttributes) {
        let attributes = [];

        try {
            attributes = JSON.parse(answer.dataset.attributes || '[]');
        } catch (error) {
            console.error('PWQB: Ошибка чтения атрибутов ответа.', error);
            return false;
        }

        // Ответ без атрибутов не ограничиваем.
        if (!attributes.length) {
            return true;
        }

        return attributes.every((item) => {
            const available = availableAttributes[item.attribute] || [];

            return Array.isArray(item.values) &&
                item.values.some((value) => available.includes(value));
        });
    },

    /**
     * Фильтрация вариантов ответов.
     */
    apply(questions, currentQuestion, availableAttributes) {
        const currentIndex = questions.indexOf(currentQuestion);

        questions.forEach((question, index) => {
            if (index <= currentIndex) {
                return;
            }

            question.querySelectorAll('[data-pwqb-frontend-answer]').forEach((answer) => {
                const available = this.isAnswerAvailable(
                    answer,
                    availableAttributes
                );

                answer.hidden = !available;

                const input = answer.querySelector('[data-pwqb-answer-input]');

                if (!available) {
                    input.checked = false;
                }
            });
        });
    },

    /**
     * Сброс фильтрации.
     */
    reset(questions) {
        questions.forEach((question) => {
            question.querySelectorAll('[data-pwqb-frontend-answer]').forEach((answer) => {
                answer.hidden = false;
            });
        });
    },
};

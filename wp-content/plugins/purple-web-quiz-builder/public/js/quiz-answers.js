window.PWQB = window.PWQB || {};

window.PWQB.answers = {
    /**
     * Получение выбранных ответов.
     */
    getSelected(question) {
        return [
            ...question.querySelectorAll('[data-pwqb-answer-input]:checked')
        ].map((input) => input.value);
    },

    /**
     * Сравнение двух наборов ответов.
     */
    areEqual(first, second) {
        if (first.length !== second.length) {
            return false;
        }

        return first.every((value, index) => value === second[index]);
    },

    /**
     * Восстановление выбранных ответов.
     */
    restore(question, answers) {
        const saved = answers[question.dataset.questionId] || [];

        question.querySelectorAll('[data-pwqb-answer-input]').forEach((input) => {
            input.checked = saved.includes(input.value);
        });
    },
};
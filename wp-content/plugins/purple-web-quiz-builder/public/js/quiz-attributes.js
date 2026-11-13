window.PWQB = window.PWQB || {};

window.PWQB.attributes = {
    /**
     * Собирает атрибуты выбранных ответов.
     */
    collect(quiz, answers, path) {
        const collected = {};

        path.forEach((questionId) => {
            const question = quiz.querySelector(
                `[data-question-id="${questionId}"]`
            );

            if (!question) {
                return;
            }

            const selectedAnswers = answers[questionId] || [];

            selectedAnswers.forEach((answerId) => {
                const answer = [
                    ...question.querySelectorAll('[data-pwqb-frontend-answer]')
                ].find((element) => {
                    return element.dataset.answerId === answerId;
                });

                if (!answer) {
                    return;
                }

                let attributes = [];

                try {
                    attributes = JSON.parse(
                        answer.dataset.attributes || '[]'
                    );
                } catch (error) {
                    console.error('PWQB: Ошибка чтения атрибутов ответа.', error);
                }

                attributes.forEach((item) => {
                    if (!item.attribute || !Array.isArray(item.values)) {
                        return;
                    }

                    if (!collected[item.attribute]) {
                        collected[item.attribute] = [];
                    }

                    item.values.forEach((value) => {
                        if (!collected[item.attribute].includes(value)) {
                            collected[item.attribute].push(value);
                        }
                    });
                });
            });
        });

        return collected;
    },
};

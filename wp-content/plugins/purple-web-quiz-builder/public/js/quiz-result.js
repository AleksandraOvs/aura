window.PWQB = window.PWQB || {};

window.PWQB.result = {
    /**
     * Завершение квиза.
     */
    finish(config) {
        config.setFinished(true);

        config.questions.forEach((question) => {
            question.hidden = true;

            const controls = config.getQuestionControls(question);

            if (controls.navigation) {
                controls.navigation.hidden = true;
            }
        });

        config.result.hidden = false;

        const selectedAttributes = PWQB.attributes.collect(
            config.quiz,
            config.answers,
            config.path
        );

        config.quiz.dispatchEvent(
            new CustomEvent('pwqb:completed', {
                bubbles: true,
                detail: {
                    quizId: config.quiz.dataset.quizId,
                    answers: { ...config.answers },
                    attributes: selectedAttributes,
                }
            })
        );
    },
};
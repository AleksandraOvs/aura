
window.PWQB = window.PWQB || {};

window.PWQB.navigation = {
    /**
     * Создание навигации для конкретного квиза.
     */
    create(config) {
        let currentIndex = 0;
        let path = [config.questions[0].dataset.questionId];

        const getCurrentQuestion = () => {
            return config.questionsMap.get(path[currentIndex]) || null;
        };

        /**
         * Очистка старой ветки.
         */
        const clearFuturePath = () => {
            const abandonedQuestions = path.slice(currentIndex + 1);

            abandonedQuestions.forEach((questionId) => {
                delete config.answers[questionId];

                const question = config.questionsMap.get(questionId);

                if (!question) {
                    return;
                }

                question.querySelectorAll('[data-pwqb-answer-input]').forEach((input) => {
                    input.checked = false;
                });
            });

            path = path.slice(0, currentIndex + 1);
        };

        /**
         * Обновление кнопки Далее.
         */
        const updateNextButton = () => {
            const question = getCurrentQuestion();

            if (!question) {
                return;
            }

            const { nextButton } = config.getQuestionControls(question);

            if (!nextButton) {
                return;
            }

            const selected = PWQB.answers.getSelected(question);

            nextButton.disabled = selected.length === 0;
            nextButton.textContent = 'Далее';
        };

        /**
         * Обновление прогресса.
         */
        const updateProgress = (question) => {
            const controls = config.getQuestionControls(question);

            const total = config.questions.length;
            const completed = currentIndex + 1;

            const percent = Math.min(
                100,
                Math.round((completed / total) * 100)
            );

            if (controls.progressLabel) {
                controls.progressLabel.textContent = `${completed} из ${total}`;
            }

            if (controls.progressBar) {
                controls.progressBar.setAttribute('aria-valuenow', percent);
            }

            if (controls.progressFill) {
                controls.progressFill.style.width = `${percent}%`;
            }
        };

        /**
         * Показ вопроса.
         */
        const showQuestion = (questionId) => {
            const question = config.questionsMap.get(questionId);

            if (!question) {
                return;
            }

            config.questions.forEach((item) => {
                item.hidden = true;
            });

            config.intro.hidden = true;
            config.questionsContainer.hidden = false;
            question.hidden = false;

            PWQB.answers.restore(question, config.answers);

            const {
                navigation,
                nextButton,
                progress,
            } = config.getQuestionControls(question);

            const questionIndex = config.questions.indexOf(question);
            const isFirstQuestion = questionIndex === 0;

            if (navigation) navigation.hidden = isFirstQuestion;
            if (progress) progress.hidden = isFirstQuestion;
            if (nextButton) nextButton.hidden = isFirstQuestion;

            config.result.hidden = true;

            updateNextButton();

            if (!isFirstQuestion) {
                updateProgress(question);
            }
        };

        /**
         * Определение следующего вопроса.
         */
        const getNextQuestion = (question) => {
            const selectedInputs = [
                ...question.querySelectorAll('[data-pwqb-answer-input]:checked')
            ];

            if (!selectedInputs.length) {
                return null;
            }

            const selectedAnswer = selectedInputs[0].closest(
                '[data-pwqb-frontend-answer]'
            );

            const explicitNext = selectedAnswer?.dataset.nextQuestion;

            if (explicitNext && config.questionsMap.has(explicitNext)) {
                return explicitNext;
            }

            const currentQuestionIndex = config.questions.indexOf(question);

            for (
                let i = currentQuestionIndex + 1;
                i < config.questions.length;
                i++
            ) {
                return config.questions[i].dataset.questionId;
            }

            return null;
        };

        /**
         * Переход вперёд.
         */
        const goNext = () => {
            if (config.isFinished()) {
                return;
            }

            const question = getCurrentQuestion();

            if (!question) {
                return;
            }

            const selected = PWQB.answers.getSelected(question);

            if (!selected.length) {
                return;
            }

            config.answers[question.dataset.questionId] = selected;

            let nextQuestionId = path[currentIndex + 1];

            if (!nextQuestionId) {
                nextQuestionId = getNextQuestion(question);
            }

            if (!nextQuestionId) {
                config.finishQuiz();
                return;
            }

            const existingIndex = path.indexOf(nextQuestionId);

            if (existingIndex !== -1) {
                path = path.slice(0, existingIndex + 1);
                currentIndex = existingIndex;
            } else {
                path.push(nextQuestionId);
                currentIndex++;
            }

            showQuestion(nextQuestionId);
        };

        return {
            getCurrentIndex: () => currentIndex,
            getPath: () => path,
            getCurrentQuestion,
            clearFuturePath,
            updateNextButton,
            showQuestion,
            goNext,

            reset() {
                currentIndex = 0;
                path = [config.questions[0].dataset.questionId];
            },
        };
    },
};

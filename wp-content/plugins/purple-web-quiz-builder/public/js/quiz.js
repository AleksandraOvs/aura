window.PWQB = window.PWQB || {};

window.PWQBInitQuiz = (quiz) => {
    if (!quiz || quiz.dataset.pwqbInitialized === 'true') {
        return;
    }

    quiz.dataset.pwqbInitialized = 'true';

    // Время начала прохождения квиза.
    const quizStartedAt = Math.floor(Date.now() / 1000);
    const questions = [
        ...quiz.querySelectorAll('[data-pwqb-frontend-question]')
    ];
    const questionsContainer = quiz.querySelector('[data-pwqb-frontend-questions]');

    const getQuestionControls = (question) => ({
        navigation: question.querySelector('[data-pwqb-navigation]'),
        nextButton: question.querySelector('[data-pwqb-next]'),
        progress: question.querySelector('[data-pwqb-progress]'),
        progressLabel: question.querySelector('[data-pwqb-progress-label]'),
        progressBar: question.querySelector('[data-pwqb-progress-bar]'),
        progressFill: question.querySelector('.pwqb-quiz__progress-fill'),
    });

    const intro = quiz.querySelector('[data-pwqb-intro]');
    const startButton = quiz.querySelector('[data-pwqb-start]');

    const result = quiz.querySelector('[data-pwqb-result]');
    const restartButton = quiz.querySelector('[data-pwqb-restart]');

    const submissionForm = quiz.querySelector('[data-pwqb-submission-form]');

    // Ответы пользователя по ID вопросов.
    const answers = {};

    if (submissionForm) {
        const sendToSelect = submissionForm.querySelector('[data-pwqb-send-to]');
        const messengerFields = submissionForm.querySelector('[data-pwqb-messenger-fields]');
        const messengerCheckboxes = submissionForm.querySelectorAll('[name="messengers[]"]');
        const phoneInput = submissionForm.querySelector('[data-pwqb-phone]');

        /**
         * Переключение полей мессенджеров.
         */
        const updateMessengerFields = () => {
            PWQB.form.updateMessengerFields({
                sendToSelect,
                messengerFields,
                messengerCheckboxes,
                phoneInput,
            });
        };

        if (sendToSelect) {
            sendToSelect.addEventListener('change', updateMessengerFields);

            updateMessengerFields();
        }

        PWQB.form.bindSubmit({
            submissionForm,
            sendToSelect,
            messengerCheckboxes,
            phoneInput,
            quiz,
            answers,
            quizStartedAt,
        });
    }

    if (!questions.length) {
        return;
    }

    const questionsMap = new Map(
        questions.map((question) => [
            question.dataset.questionId,
            question
        ])
    );



    let finished = false;

    /**
 * Завершение квиза.
 */
    const finishQuiz = () => {
        PWQB.result.finish({
            quiz,
            questions,
            answers,
            path: navigation.getPath(),
            result,
            getQuestionControls,
            setFinished: (value) => {
                finished = value;
            },
        });
    };

    // Навигация по вопросам.
    const navigation = PWQB.navigation.create({
        questions,
        questionsMap,
        answers,
        intro,
        questionsContainer,
        result,
        getQuestionControls,
        isFinished: () => finished,
        finishQuiz,
    });


    /**
     * Повторное прохождение.
     */
    const restart = () => {
        Object.keys(answers).forEach((key) => {
            delete answers[key];
        });

        questions.forEach((question) => {
            question.querySelectorAll('[data-pwqb-answer-input]').forEach((input) => {
                input.checked = false;
            });
        });

        finished = false;

        navigation.reset();
        navigation.showQuestion(navigation.getPath()[0]);
    };

    /**
     * Обработка выбора ответа.
     */
    quiz.addEventListener('change', (event) => {
        if (!event.target.matches('[data-pwqb-answer-input]')) {
            return;
        }

        const question = event.target.closest(
            '[data-pwqb-frontend-question]'
        );

        if (
            !question ||
            question.dataset.questionId !== navigation.getCurrentQuestion()?.dataset.questionId
        ) {
            return;
        }

        const selected = PWQB.answers.getSelected(question)
        const previous = answers[question.dataset.questionId] || [];

        if (!PWQB.answers.areEqual(previous, selected)) {
            navigation.clearFuturePath();
        }

        answers[question.dataset.questionId] = selected;

        navigation.updateNextButton();

        if (question.dataset.questionType === 'single') {
            navigation.goNext();
        }
    });

    questions.forEach((question) => {
        const { nextButton } = getQuestionControls(question);

        nextButton?.addEventListener('click', navigation.goNext);
    });

    startButton?.addEventListener('click', () => {
        navigation.reset();
        navigation.showQuestion(navigation.getPath()[0]);
    });

    restartButton?.addEventListener('click', restart);

    // При открытии показываем только вступительный экран.
    if (intro) {
        intro.hidden = false;
    }
    questions.forEach((question) => {
        question.hidden = true;
    });

    questions.forEach((question) => {
        const controls = getQuestionControls(question);

        if (controls.navigation) controls.navigation.hidden = true;
        if (controls.progress) controls.progress.hidden = true;
    });

    if (result) {
        result.hidden = true;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.pwqb-quiz').forEach((quiz) => {
        window.PWQBInitQuiz(quiz);
    });
});


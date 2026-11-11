
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.pwqb-quiz').forEach((quiz) => {

        // Время начала прохождения квиза.
        const quizStartedAt = Math.floor(Date.now() / 1000);
        const questions = [
            ...quiz.querySelectorAll('[data-pwqb-frontend-question]')
        ];

        const navigation = quiz.querySelector('[data-pwqb-navigation]');
        const backButton = quiz.querySelector('[data-pwqb-back]');
        const nextButton = quiz.querySelector('[data-pwqb-next]');

        const progress = quiz.querySelector('.pwqb-quiz__progress');
        const progressLabel = quiz.querySelector('[data-pwqb-progress-label]');
        const progressPercent = quiz.querySelector('[data-pwqb-progress-percent]');
        const progressBar = quiz.querySelector('[data-pwqb-progress-bar]');
        const progressFill = quiz.querySelector('.pwqb-quiz__progress-fill');

        const result = quiz.querySelector('[data-pwqb-result]');
        const restartButton = quiz.querySelector('[data-pwqb-restart]');

        const submissionForm = quiz.querySelector('[data-pwqb-submission-form]');

        if (submissionForm) {
            const sendToSelect = submissionForm.querySelector('[data-pwqb-send-to]');
            const messengerFields = submissionForm.querySelector('[data-pwqb-messenger-fields]');
            const messengerCheckboxes = submissionForm.querySelectorAll('[name="messengers[]"]');
            const phoneInput = submissionForm.querySelector('[data-pwqb-phone]');

            /**
             * Переключение полей мессенджеров.
             */
            const updateMessengerFields = () => {
                const needsMessenger = ['messenger', 'both'].includes(sendToSelect.value);

                messengerFields.hidden = !needsMessenger;

                messengerCheckboxes.forEach((checkbox) => {
                    checkbox.required = false;
                });

                phoneInput.required = needsMessenger;

                if (!needsMessenger) {
                    messengerCheckboxes.forEach((checkbox) => {
                        checkbox.checked = false;
                    });

                    phoneInput.value = '';
                }
            };

            sendToSelect.addEventListener('change', updateMessengerFields);

            // Проверка выбора хотя бы одного мессенджера.
            // AJAX-отправка формы.
            submissionForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const needsMessenger = ['messenger', 'both'].includes(sendToSelect.value);

                if (needsMessenger) {
                    const hasMessenger = [...messengerCheckboxes].some(
                        (checkbox) => checkbox.checked
                    );

                    if (!hasMessenger) {
                        alert('Выберите хотя бы один мессенджер.');
                        return;
                    }

                    if (!phoneInput.value.trim()) {
                        phoneInput.focus();
                        return;
                    }
                }

                const submitButton = submissionForm.querySelector('[type="submit"]');
                const message = submissionForm.querySelector('[data-pwqb-form-message]');

                const originalButtonText = submitButton.textContent;

                submitButton.disabled = true;
                submitButton.textContent = 'Отправка...';

                message.hidden = true;
                message.textContent = '';

                const formData = new FormData(submissionForm);

                formData.append('action', 'pwqb_submit_quiz');
                formData.append('quiz_id', quiz.dataset.quizId);
                formData.append('answers', JSON.stringify(answers));

                formData.append('quiz_started_at', quizStartedAt);

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

                    // Перемещаем сообщение перед формой.
                    submissionForm.parentNode.insertBefore(message, submissionForm);

                    // Показываем сообщение об успехе.
                    message.textContent = data.data.message;
                    message.hidden = false;
                    message.classList.add('is-success');

                    // Скрываем описание.
                    const resultDescription = quiz.querySelector('.pwqb-quiz__result-description');

                    if (resultDescription) {
                        resultDescription.hidden = true;
                    }
                    // Скрываем форму.
                    submissionForm.hidden = true;

                    submissionForm.querySelectorAll('input, select, textarea, button').forEach((field) => {
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

            updateMessengerFields();
        }

        if (!questions.length || !navigation || !nextButton) {
            return;
        }

        const questionsMap = new Map(
            questions.map((question) => [
                question.dataset.questionId,
                question
            ])
        );

        // Ответы пользователя по ID вопросов.
        const answers = {};

        // Фактический путь прохождения квиза.
        let path = [questions[0].dataset.questionId];

        // Индекс текущего вопроса в path.
        let currentIndex = 0;

        let finished = false;

        const getCurrentQuestion = () => {
            return questionsMap.get(path[currentIndex]) || null;
        };

        /**
         * Получение выбранных ответов.
         */
        const getSelectedAnswers = (question) => {
            return [
                ...question.querySelectorAll('[data-pwqb-answer-input]:checked')
            ].map((input) => input.value);
        };

        /**
         * Сравнение двух наборов ответов.
         */
        const areAnswersEqual = (first, second) => {
            if (first.length !== second.length) {
                return false;
            }

            return first.every((value, index) => value === second[index]);
        };

        /**
         * Восстановление выбранных ответов.
         */
        const restoreAnswers = (question) => {
            const saved = answers[question.dataset.questionId] || [];

            question.querySelectorAll('[data-pwqb-answer-input]').forEach((input) => {
                input.checked = saved.includes(input.value);
            });
        };

        /**
         * Очистка старой ветки после изменения ответа.
         */
        const clearFuturePath = () => {
            const abandonedQuestions = path.slice(currentIndex + 1);

            abandonedQuestions.forEach((questionId) => {
                delete answers[questionId];

                const question = questionsMap.get(questionId);

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
                nextButton.disabled = true;
                return;
            }

            const selected = getSelectedAnswers(question);

            nextButton.disabled = selected.length === 0;
            nextButton.textContent = 'Далее';
        };

        /**
         * Обновление прогресса.
         */
        const updateProgress = () => {
            const total = questions.length;
            const completed = currentIndex + 1;

            const percent = Math.min(
                100,
                Math.round((completed / total) * 100)
            );

            progressLabel.textContent = `Вопрос ${completed} из ${total}`;
            progressPercent.textContent = `${percent}%`;

            progressBar.setAttribute('aria-valuenow', percent);
            progressFill.style.width = `${percent}%`;
        };

        /**
         * Показ вопроса.
         */
        const showQuestion = (questionId) => {
            const question = questionsMap.get(questionId);

            if (!question) {
                finishQuiz();
                return;
            }

            finished = false;

            questions.forEach((item) => {
                item.hidden = item !== question;
            });

            restoreAnswers(question);

            navigation.hidden = false;
            progress.hidden = false;
            result.hidden = true;

            backButton.hidden = currentIndex === 0;

            updateNextButton();
            updateProgress();
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

            // Для multiple используется первый выбранный ответ
            // в порядке отображения вариантов.
            const selectedInput = selectedInputs[0];

            const selectedAnswer = selectedInput.closest(
                '[data-pwqb-frontend-answer]'
            );

            const explicitNext = selectedAnswer?.dataset.nextQuestion;

            if (explicitNext && questionsMap.has(explicitNext)) {
                return explicitNext;
            }

            // Если переход не задан — следующий вопрос по порядку.
            const currentQuestionIndex = questions.indexOf(question);

            for (
                let i = currentQuestionIndex + 1;
                i < questions.length;
                i++
            ) {
                return questions[i].dataset.questionId;
            }

            return null;
        };

        /**
         * Переход вперёд.
         */
        const goNext = () => {
            if (finished) {
                return;
            }

            const question = getCurrentQuestion();

            if (!question) {
                return;
            }

            const selected = getSelectedAnswers(question);

            if (!selected.length) {
                return;
            }

            answers[question.dataset.questionId] = selected;

            // Если пользователь вернулся назад и не менял ответ,
            // продолжаем ранее выбранную ветку.
            let nextQuestionId = path[currentIndex + 1];

            // Если следующего вопроса в сохранённом пути нет,
            // определяем его заново.
            if (!nextQuestionId) {
                nextQuestionId = getNextQuestion(question);
            }

            if (!nextQuestionId) {
                finishQuiz();
                return;
            }

            // Не допускаем повторного посещения одного вопроса.
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

        /**
         * Возврат назад.
         */
        const goBack = () => {
            if (currentIndex <= 0 || finished) {
                return;
            }

            currentIndex--;

            showQuestion(path[currentIndex]);
        };

        /**
         * Завершение квиза.
         */
        const finishQuiz = () => {
            finished = true;

            questions.forEach((question) => {
                question.hidden = true;
            });

            navigation.hidden = true;
            result.hidden = false;

            progressLabel.textContent = `Пройдено ${path.length} вопросов`;
            progressPercent.textContent = '100%';

            progressBar.setAttribute('aria-valuenow', 100);
            progressFill.style.width = '100%';

            quiz.dispatchEvent(
                new CustomEvent('pwqb:completed', {
                    bubbles: true,
                    detail: {
                        quizId: quiz.dataset.quizId,
                        answers: { ...answers }
                    }
                })
            );
        };

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

            path = [questions[0].dataset.questionId];
            currentIndex = 0;
            finished = false;

            showQuestion(path[0]);
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

            if (!question || question.dataset.questionId !== path[currentIndex]) {
                return;
            }

            const selected = getSelectedAnswers(question);
            const previous = answers[question.dataset.questionId] || [];

            // Если ответ изменился, сбрасываем прежнюю ветку.
            if (!areAnswersEqual(previous, selected)) {
                clearFuturePath();
            }

            answers[question.dataset.questionId] = selected;

            updateNextButton();

            // Один вариант — автоматический переход.
            if (question.dataset.questionType === 'single') {
                goNext();
            }
        });

        nextButton.addEventListener('click', goNext);
        backButton.addEventListener('click', goBack);

        restartButton?.addEventListener('click', restart);

        showQuestion(path[0]);
    });
});

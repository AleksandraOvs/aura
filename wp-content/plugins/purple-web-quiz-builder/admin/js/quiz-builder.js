
document.addEventListener('DOMContentLoaded', () => {
    const builder = document.querySelector('.pwqb-builder');

    if (!builder) {
        return;
    }

    const questionsContainer = builder.querySelector('[data-pwqb-questions]');
    const emptyMessage = builder.querySelector('[data-pwqb-empty]');

    const questionTemplate = document.getElementById('tmpl-pwqb-question');
    const answerTemplate = document.getElementById('tmpl-pwqb-answer');

    if (!questionsContainer || !questionTemplate || !answerTemplate) {
        return;
    }

    const createId = (prefix) => {
        return prefix + '_' + Math.random().toString(36).slice(2, 10);
    };

    /**
     * Создание HTML из шаблона.
     */
    const createElementFromTemplate = (template) => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.trim();

        return wrapper.firstElementChild;
    };

    /**
     * Установка имени полей.
     */
    const setFieldNames = (container, prefix) => {
        container.querySelectorAll('[data-field]').forEach((field) => {
            const key = field.dataset.field;

            if (key === 'id') {
                return;
            }

            field.name = `${prefix}[${key}]`;
        });
    };

    /**
 * Перенумерация вопросов и ответов.
 */
    const reindex = () => {
        const questions = [...questionsContainer.querySelectorAll('[data-pwqb-question]')];

        questions.forEach((question, questionIndex) => {
            question.querySelector('.pwqb-question__number').textContent =
                `Вопрос ${questionIndex + 1}`;

            const questionPrefix = `pwqb_settings[questions][${questionIndex}]`;

            const questionId = question.querySelector('[data-field="id"]');
            questionId.name = `${questionPrefix}[id]`;

            const questionBody = question.querySelector('.pwqb-question__body');

            // Назначаем имена полям вопроса.
            questionBody.querySelectorAll(':scope > p [data-field]').forEach((field) => {
                field.name = `${questionPrefix}[${field.dataset.field}]`;
            });

            // Отдельно назначаем имя изображению вопроса.
            const questionImage = questionBody.querySelector(
                ':scope > [data-pwqb-image] [data-field="image_id"]'
            );

            if (questionImage) {
                questionImage.name = `${questionPrefix}[image_id]`;
            }

            const answersContainer = question.querySelector('[data-pwqb-answers]');

            [...answersContainer.querySelectorAll('[data-pwqb-answer]')].forEach((answer, answerIndex) => {
                const answerPrefix = `${questionPrefix}[answers][${answerIndex}]`;

                answer.querySelectorAll('[data-field]').forEach((field) => {
                    field.name = `${answerPrefix}[${field.dataset.field}]`;
                });
            });
        });

        if (emptyMessage) {
            emptyMessage.hidden = questions.length > 0;
        }

        updateQuestionSelects();
    };

    /**
  * Обновление списков переходов между вопросами.
  * Разрешаем переход только к последующим вопросам.
  */
    const updateQuestionSelects = () => {
        const questions = [...questionsContainer.querySelectorAll('[data-pwqb-question]')];

        questions.forEach((question, questionIndex) => {
            question.querySelectorAll('[data-field="next_question"]').forEach((select) => {
                const selectedValue = select.value;

                select.replaceChildren();

                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Следующий по порядку';

                select.append(defaultOption);

                // Добавляем только вопросы, расположенные ниже текущего.
                questions.forEach((targetQuestion, targetIndex) => {
                    if (targetIndex <= questionIndex) {
                        return;
                    }

                    const targetId = targetQuestion.querySelector('[data-field="id"]').value;

                    const option = document.createElement('option');
                    option.value = targetId;
                    option.textContent =
                        targetQuestion.querySelector('[data-field="title"]').value ||
                        `Вопрос ${targetIndex + 1}`;

                    select.append(option);
                });

                // Восстанавливаем выбор, если он всё ещё допустим.
                const hasSelectedOption = [...select.options].some(
                    (option) => option.value === selectedValue
                );

                select.value = hasSelectedOption ? selectedValue : '';
            });
        });
    };

    /**
     * Добавление вопроса.
     */
    const addQuestion = () => {
        const question = createElementFromTemplate(questionTemplate);

        question.querySelector('[data-field="id"]').value = createId('q');

        questionsContainer.append(question);

        reindex();

        addAnswer(question);
    };

    /**
     * Добавление ответа.
     */
    const addAnswer = (question) => {
        const answersContainer = question.querySelector('[data-pwqb-answers]');

        if (!answersContainer) {
            return;
        }

        const answer = createElementFromTemplate(answerTemplate);

        answer.querySelector('[data-field="id"]').value = createId('a');

        answersContainer.append(answer);

        reindex();
    };

    /**
     * Перемещение вопроса.
     */
    const moveQuestion = (question, direction) => {
        const sibling = direction === 'up'
            ? question.previousElementSibling
            : question.nextElementSibling;

        if (!sibling) {
            return;
        }

        if (direction === 'up') {
            questionsContainer.insertBefore(question, sibling);
        } else {
            questionsContainer.insertBefore(sibling, question);
        }

        reindex();
    };

    /**
     * Работа с медиабиблиотекой.
     */
    const selectImage = (field) => {
        if (!window.wp || !wp.media) {
            return;
        }

        const frame = wp.media({
            title: 'Выберите изображение',
            button: {
                text: 'Использовать изображение'
            },
            library: {
                type: 'image'
            },
            multiple: false
        });

        frame.on('select', () => {
            const attachment = frame.state().get('selection').first().toJSON();

            const input = field.querySelector('[data-field="image_id"]');
            const preview = field.querySelector('[data-image-preview]');
            const removeButton = field.querySelector('[data-pwqb-remove-image]');

            input.value = attachment.id;

            const image = document.createElement('img');
            image.src = attachment.sizes?.thumbnail?.url || attachment.url;
            image.alt = '';

            preview.replaceChildren(image);
            removeButton.hidden = false;
        });

        frame.open();
    };

    /**
     * Удаление изображения.
     */
    const removeImage = (field) => {
        const input = field.querySelector('[data-field="image_id"]');
        const preview = field.querySelector('[data-image-preview]');
        const removeButton = field.querySelector('[data-pwqb-remove-image]');

        input.value = 0;

        const placeholder = document.createElement('span');
        placeholder.textContent = 'Изображение не выбрано';

        preview.replaceChildren(placeholder);
        removeButton.hidden = true;
    };

    /**
     * Обработка действий.
     */
    builder.addEventListener('click', (event) => {
        const target = event.target;

        if (target.closest('[data-pwqb-add-question]')) {
            addQuestion();
            return;
        }

        if (target.closest('[data-pwqb-add-answer]')) {
            const question = target.closest('[data-pwqb-question]');
            addAnswer(question);
            return;
        }

        if (target.closest('[data-pwqb-remove-question]')) {
            target.closest('[data-pwqb-question]').remove();
            reindex();
            return;
        }

        if (target.closest('[data-pwqb-remove-answer]')) {
            target.closest('[data-pwqb-answer]').remove();
            reindex();
            return;
        }

        if (target.closest('[data-pwqb-up]')) {
            moveQuestion(target.closest('[data-pwqb-question]'), 'up');
            return;
        }

        if (target.closest('[data-pwqb-down]')) {
            moveQuestion(target.closest('[data-pwqb-question]'), 'down');
            return;
        }

        if (target.closest('[data-pwqb-select-image]')) {
            selectImage(target.closest('[data-pwqb-image]'));
            return;
        }

        if (target.closest('[data-pwqb-remove-image]')) {
            removeImage(target.closest('[data-pwqb-image]'));
        }
    });

    /**
     * Обновление подписей переходов при изменении текста вопроса.
     */
    builder.addEventListener('input', (event) => {
        if (event.target.matches('[data-field="title"]')) {
            updateQuestionSelects();
        }
    });

    reindex();
});

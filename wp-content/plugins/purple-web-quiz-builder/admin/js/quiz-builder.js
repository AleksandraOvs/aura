
document.addEventListener('DOMContentLoaded', () => {
    const builder = document.querySelector('.pwqb-builder');

    if (!builder) {
        return;
    }

    const questionsContainer = builder.querySelector('[data-pwqb-questions]');
    const emptyMessage = builder.querySelector('[data-pwqb-empty]');

    const questionTemplate = document.getElementById('tmpl-pwqb-question');
    const answerTemplate = document.getElementById('tmpl-pwqb-answer');

    const attributeRuleTemplate = document.getElementById('tmpl-pwqb-attribute-rule');

    if (!questionsContainer || !questionTemplate || !answerTemplate || !attributeRuleTemplate) {
        return;
    }

    const builderForm = builder.closest('form');

    if (builderForm) {
        builderForm.addEventListener('submit', () => {
            const formData = new FormData(builderForm);

            console.log('PWQB: поля критериев перед отправкой');

            for (const [name, value] of formData.entries()) {
                if (name.includes('[attributes]')) {
                    console.log(name, value);
                }
            }
        });
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

                // Назначаем имена полям критериев подбора.
                answer.querySelectorAll('[data-pwqb-criterion]').forEach((criterion, criterionIndex) => {
                    const criterionPrefix = `${answerPrefix}[attributes][${criterionIndex}]`;

                    const attributeInput = criterion.querySelector(
                        '[data-pwqb-criterion-attribute]'
                    );

                    if (attributeInput) {
                        attributeInput.name = `${criterionPrefix}[attribute]`;
                    }

                    criterion.querySelectorAll('[data-pwqb-criterion-value]').forEach((input) => {
                        input.name = `${criterionPrefix}[values][]`;
                    });
                });
            });
        });

        if (emptyMessage) {
            emptyMessage.hidden = questions.length > 0;
        }

        updateQuestionSelects();

        console.log('PWQB: критерии подбора', [...questionsContainer.querySelectorAll('[data-pwqb-answer]')].map((answer, answerIndex) => ({
            answerIndex,
            criteria: [...answer.querySelectorAll('[data-pwqb-criterion]')].map((criterion) => ({
                attribute: criterion.querySelector('[data-pwqb-criterion-attribute]')?.value,
                values: [...criterion.querySelectorAll('[data-pwqb-criterion-value]')].map((input) => input.value)
            }))
        })));
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



    const createSearchSelect = (container, items, placeholder, multiple = false) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'pwqb-search-select';

        const search = document.createElement('input');
        search.type = 'search';
        search.className = 'widefat';
        search.placeholder = placeholder;
        search.autocomplete = 'off';

        const selectedList = document.createElement('div');
        selectedList.className = 'pwqb-search-select__selected';

        const list = document.createElement('div');
        list.className = 'pwqb-search-select__list';
        list.hidden = true;

        const selected = new Set();

        const closeList = () => {
            list.hidden = true;
            wrapper.classList.remove('is-open');
        };

        const openList = () => {
            list.hidden = false;
            wrapper.classList.add('is-open');
            renderItems();
        };

        const renderSelected = () => {
            selectedList.replaceChildren();

            selected.forEach((value) => {
                const item = items.find((item) => item.value === value);

                if (!item) {
                    return;
                }

                const tag = document.createElement('span');
                tag.className = 'pwqb-search-select__tag';

                const text = document.createElement('span');
                text.textContent = item.name;

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = '×';
                remove.setAttribute('aria-label', 'Удалить ' + item.name);

                remove.addEventListener('click', () => {
                    selected.delete(value);
                    renderSelected();
                    renderItems();

                    wrapper.dispatchEvent(
                        new CustomEvent('pwqb:change', { bubbles: true })
                    );
                });

                tag.append(text, remove);
                selectedList.append(tag);
            });
        };

        const renderItems = () => {
            const query = search.value.trim().toLowerCase();

            list.replaceChildren();

            const filtered = items.filter((item) => {
                return item.name.toLowerCase().includes(query);
            });

            if (!filtered.length) {
                const empty = document.createElement('p');
                empty.textContent = 'Ничего не найдено';
                list.append(empty);
                return;
            }

            filtered.forEach((item) => {
                const label = document.createElement('label');
                label.className = 'pwqb-search-select__item';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.checked = selected.has(item.value);

                const text = document.createElement('span');
                text.textContent = item.name;

                checkbox.addEventListener('change', () => {
                    if (!multiple) {
                        selected.clear();
                    }

                    if (checkbox.checked) {
                        selected.add(item.value);
                    } else {
                        selected.delete(item.value);
                    }

                    renderSelected();
                    renderItems();

                    wrapper.dispatchEvent(
                        new CustomEvent('pwqb:change', { bubbles: true })
                    );

                    // Закрываем список после выбора.
                    closeList();
                    search.value = '';
                });

                label.append(checkbox, text);
                list.append(label);
            });
        };

        search.addEventListener('focus', openList);

        search.addEventListener('input', () => {
            openList();
            renderItems();
        });

        // Закрываем список при клике за пределами поля.
        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                closeList();
            }
        });

        wrapper.append(search, selectedList, list);
        container.append(wrapper);

        renderItems();

        return {
            getSelected: () => [...selected],

            setSelected: (values) => {
                selected.clear();
                values.forEach((value) => selected.add(value));
                renderSelected();
                renderItems();
            },

            clear: () => {
                selected.clear();
                search.value = '';
                renderSelected();
                renderItems();
                closeList();
            }
        };
    };

    /**
 * Добавление готового критерия подбора.
 */
    const addAttributeRule = (answer) => {
        const rulesContainer = answer.querySelector('[data-pwqb-attribute-rules]');

        if (!rulesContainer) {
            return;
        }

        // Не открываем второй редактор, пока не закрыт первый.
        if (rulesContainer.querySelector('.pwqb-attribute-editor')) {
            return;
        }

        const attributes = Array.isArray(window.pwqbBuilderData?.attributes)
            ? window.pwqbBuilderData.attributes
            : [];

        // Исключаем атрибуты, которые уже добавлены к этому ответу.
        const usedTaxonomies = [
            ...rulesContainer.querySelectorAll('[data-pwqb-criterion]')
        ].map((item) => item.dataset.taxonomy);

        const availableAttributes = attributes.filter((attribute) => {
            return !usedTaxonomies.includes(attribute.taxonomy);
        });

        const editor = document.createElement('div');
        editor.className = 'pwqb-attribute-editor';

        const attributeTitle = document.createElement('strong');
        attributeTitle.textContent = 'Атрибут';

        const attributeContainer = document.createElement('div');

        const valuesTitle = document.createElement('strong');
        valuesTitle.textContent = 'Значения';

        const valuesContainer = document.createElement('div');

        const actions = document.createElement('div');
        actions.className = 'pwqb-attribute-editor__actions';

        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'button button-primary';
        addButton.textContent = 'Добавить критерий';
        addButton.disabled = true;

        const cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.className = 'button';
        cancelButton.textContent = 'Отмена';

        actions.append(addButton, cancelButton);

        editor.append(
            attributeTitle,
            attributeContainer,
            valuesTitle,
            valuesContainer,
            actions
        );

        rulesContainer.append(editor);

        const attributeSearch = createSearchSelect(
            attributeContainer,
            availableAttributes.map((attribute) => ({
                value: attribute.taxonomy,
                name: attribute.label
            })),
            'Найти атрибут'
        );

        let selectedAttribute = null;
        let valuesSearch = null;

        const updateAddButton = () => {
            const selectedValues = valuesSearch
                ? valuesSearch.getSelected()
                : [];

            addButton.disabled = !selectedAttribute || !selectedValues.length;
        };

        // После выбора атрибута показываем его значения.
        attributeContainer.addEventListener('pwqb:change', () => {
            selectedAttribute = attributeSearch.getSelected()[0] || null;

            valuesContainer.replaceChildren();
            valuesSearch = null;

            if (!selectedAttribute) {
                updateAddButton();
                return;
            }

            const attribute = attributes.find((item) => {
                return item.taxonomy === selectedAttribute;
            });

            if (!attribute) {
                updateAddButton();
                return;
            }

            valuesSearch = createSearchSelect(
                valuesContainer,
                attribute.terms.map((term) => ({
                    value: term.slug,
                    name: term.name
                })),
                'Найти значения',
                true
            );

            const valuesInput = valuesContainer.querySelector('input[type="search"]');

            if (valuesInput) {
                valuesInput.focus();
            }

            updateAddButton();
        });

        // Проверяем, выбрано ли хотя бы одно значение.
        valuesContainer.addEventListener('pwqb:change', updateAddButton);

        // Добавляем готовый критерий.
        addButton.addEventListener('click', () => {
            if (!selectedAttribute || !valuesSearch) {
                return;
            }

            const selectedValues = valuesSearch.getSelected();

            if (!selectedValues.length) {
                return;
            }

            const attribute = attributes.find((item) => {
                return item.taxonomy === selectedAttribute;
            });

            if (!attribute) {
                return;
            }

            const criterion = document.createElement('div');
            criterion.className = 'pwqb-attribute-criterion';
            criterion.dataset.pwqbCriterion = '';
            criterion.dataset.taxonomy = attribute.taxonomy;

            // Скрытое поле с таксономией атрибута.
            const attributeInput = document.createElement('input');
            attributeInput.type = 'hidden';
            attributeInput.value = attribute.taxonomy;
            attributeInput.dataset.pwqbCriterionAttribute = '';

            criterion.append(attributeInput);

            const title = document.createElement('strong');
            title.textContent = attribute.label;

            const valuesList = document.createElement('div');
            valuesList.className = 'pwqb-attribute-criterion__values';

            selectedValues.forEach((value) => {
                const term = attribute.terms.find((item) => {
                    return item.slug === value;
                });

                if (!term) {
                    return;
                }

                // Скрытое поле со slug выбранного значения.
                const valueInput = document.createElement('input');
                valueInput.type = 'hidden';
                valueInput.value = term.slug;
                valueInput.dataset.pwqbCriterionValue = '';

                criterion.append(valueInput);

                const tag = document.createElement('span');
                tag.className = 'pwqb-search-select__tag';
                tag.textContent = term.name;

                valuesList.append(tag);
            });

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'button-link-delete';
            removeButton.textContent = 'Удалить';

            removeButton.addEventListener('click', () => {
                criterion.remove();
            });

            criterion.append(title, valuesList, removeButton);

            rulesContainer.insertBefore(criterion, editor);
            editor.remove();

            // Обновляем имена полей для отправки формы.
            reindex();
        });

        // Отмена создания критерия.
        cancelButton.addEventListener('click', () => {
            editor.remove();
        });
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

        if (target.closest('[data-pwqb-add-attribute]')) {
            addAttributeRule(target.closest('[data-pwqb-answer]'));
            return;
        }

        if (target.closest('[data-pwqb-remove-criterion]')) {
            const criterion = target.closest('[data-pwqb-criterion]');

            if (criterion) {
                criterion.remove();
                reindex();
            }

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

    /**
 * Раскрытие и сворачивание дочерних категорий товаров.
 */
    const initCategoryTree = () => {
        const categoriesContainer = builder.querySelector(
            '.pwqb-builder__categories'
        );

        if (!categoriesContainer) {
            return;
        }

        const categoryItems = categoriesContainer.querySelectorAll('li');

        categoryItems.forEach((item) => {
            const children = [...item.children].find((child) => {
                return child.tagName === 'UL' && child.classList.contains('children');
            });

            if (!children) {
                return;
            }

            // Создаём кнопку раскрытия.
            const toggle = document.createElement('button');

            toggle.type = 'button';
            toggle.className = 'pwqb-category-toggle';
            toggle.setAttribute('aria-label', 'Раскрыть подкатегории');
            toggle.setAttribute('aria-expanded', 'false');

            // Ставим кнопку перед названием категории.
            const label = [...item.children].find((child) => {
                return child.tagName === 'LABEL';
            });

            if (label) {
                item.insertBefore(toggle, label);
            } else {
                item.insertBefore(toggle, children);
            }

            // Сворачиваем дочерние категории.


            toggle.addEventListener('click', () => {
                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

                toggle.setAttribute('aria-expanded', String(!isExpanded));
                toggle.setAttribute(
                    'aria-label',
                    isExpanded ? 'Раскрыть подкатегории' : 'Свернуть подкатегории'
                );

                toggle.classList.toggle('is-expanded', !isExpanded);
                item.classList.toggle('is-expanded', !isExpanded);

            });
        });
    };

    initCategoryTree();

    reindex();
});

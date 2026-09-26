document.addEventListener('DOMContentLoaded', function () {

    const form = document.querySelector('#supplier-import-form');
    const fileInput = document.querySelector('#supplier-import-file');
    const info = document.querySelector('#supplier-import-info');
    const progressBlock = document.querySelector(
        '#supplier-import-progress'
    );
    const progressBar = document.querySelector(
        '#supplier-import-progress-bar'
    );
    const progressPercent = document.querySelector(
        '#supplier-import-progress-percent'
    );
    const progressStats = document.querySelector(
        '#supplier-import-progress-stats'
    );
    const startButton = document.querySelector(
        '#supplier-import-start'
    );

    const errorsBlock = document.querySelector(
        '#supplier-import-errors'
    );

    const errorsToggle = document.querySelector(
        '#supplier-import-errors-toggle'
    );

    const errorsList = document.querySelector(
        '#supplier-import-errors-list'
    );



    if (
        !form
        || !fileInput
        || !info
        || !progressBlock
        || !progressBar
        || !progressPercent
        || !progressStats
        || !startButton
    ) {
        return;
    }
    let currentImportId = null;
    form.addEventListener('submit', function (event) {
        event.preventDefault();

        const file = fileInput.files[0];

        if (!file) {
            return;
        }

        startButton.disabled = true;
        startButton.textContent = 'Создаём импорт...';

        info.hidden = false;
        info.textContent = 'Загружаем CSV-файл...';

        const formData = new FormData();

        formData.append(
            'action',
            'supplier_import_start'
        );

        formData.append(
            'nonce',
            supplierImporter.nonce
        );

        formData.append(
            'file',
            file
        );

        fetch(
            supplierImporter.ajaxUrl,
            {
                method: 'POST',
                body: formData
            }
        )
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {

                if (!result.success) {
                    throw new Error(
                        result.data?.message
                        || 'Не удалось создать импорт.'
                    );
                }

                const data = result.data;
                currentImportId = data.import_id;

                console.log(
                    'Import ID:',
                    currentImportId
                );


                info.innerHTML = `
                    <p>
                        <strong>Поставщик:</strong>
                        ${data.supplier.name}
                    </p>

                    <p>
                        <strong>Товаров:</strong>
                        ${data.total}
                    </p>
                `;

                progressBlock.hidden = true;

                startButton.textContent = 'Продолжить';

                renderAnalysis(
                    data.analysis,
                    data.supplier.id
                );
            })
            .catch(function (error) {

                console.error(error);

                info.hidden = false;
                info.textContent = error.message;

                startButton.disabled = false;
                startButton.textContent = 'Начать импорт';
            });
    });

    function processImport(importId) {
        const formData = new FormData();

        formData.append(
            'action',
            'supplier_import_process'
        );

        formData.append(
            'nonce',
            supplierImporter.nonce
        );

        formData.append(
            'import_id',
            importId
        );

        fetch(
            supplierImporter.ajaxUrl,
            {
                method: 'POST',
                body: formData
            }
        )
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {

                if (!result.success) {
                    throw new Error(
                        result.data?.message
                        || 'Ошибка обработки импорта.'
                    );
                }

                const data = result.data;

                updateProgress(data);

                if (!data.finished) {
                    setTimeout(function () {
                        processImport(importId);
                    }, 300);
                } else {
                    finishImport(data);
                }
            })
            .catch(function (error) {

                console.error(error);

                info.hidden = false;
                info.textContent = error.message;

                startButton.disabled = false;
                startButton.textContent = 'Начать импорт';
            });
    }

    function updateProgress(data) {

        progressBlock.hidden = false;

        const progress = data.progress;

        const processed = Number(
            progress.processed || 0
        );

        const total = Number(
            progress.total || data.total || 0
        );

        const percent = Number(
            progress.percent || 0
        );

        const progressCount = document.querySelector(
            '#supplier-import-progress-count'
        );

        progressBar.style.width =
            percent + '%';

        progressPercent.textContent =
            percent + '%';

        progressCount.textContent =
            processed + ' / ' + total;

        progressStats.innerHTML = `
        <div class="supplier-importer__stat">
            <span class="supplier-importer__stat-label">
                Создано
            </span>

            <span class="supplier-importer__stat-value">
                ${progress.created}
            </span>
        </div>

        <div class="supplier-importer__stat">
            <span class="supplier-importer__stat-label">
                Обновлено
            </span>

            <span class="supplier-importer__stat-value">
                ${progress.updated}
            </span>
        </div>

        <div class="supplier-importer__stat">
            <span class="supplier-importer__stat-label">
                Пропущено
            </span>

            <span class="supplier-importer__stat-value">
                ${progress.skipped}
            </span>
        </div>

        <div class="supplier-importer__stat">
            <span class="supplier-importer__stat-label">
                Ошибок
            </span>

            <span class="supplier-importer__stat-value">
                ${progress.errors}
            </span>
        </div>
    `;

        if (Number(progress.errors || 0) > 0 && currentImportId) {
            loadImportErrors(currentImportId);
        }
    }

    function finishImport(data) {

        startButton.disabled = false;
        startButton.textContent = 'Начать импорт';

        progressBlock.hidden = false;

        progressPercent.textContent = '100%';
        progressBar.style.width = '100%';

        const progress = data.progress || {};

        const progressCount = document.querySelector(
            '#supplier-import-progress-count'
        );

        if (progressCount) {
            progressCount.textContent =
                Number(progress.processed || 0)
                + ' / ' +
                Number(progress.total || data.total || 0);
        }

        progressStats.innerHTML = `
        <div class="supplier-importer__stat">
            <span class="supplier-importer__stat-label">
                Создано
            </span>

            <span class="supplier-importer__stat-value">
                ${Number(progress.created || 0)}
            </span>
        </div>

        <div class="supplier-importer__stat">
            <span class="supplier-importer__stat-label">
                Обновлено
            </span>

            <span class="supplier-importer__stat-value">
                ${Number(progress.updated || 0)}
            </span>
        </div>

        <div class="supplier-importer__stat">
            <span class="supplier-importer__stat-label">
                Пропущено
            </span>

            <span class="supplier-importer__stat-value">
                ${Number(progress.skipped || 0)}
            </span>
        </div>

        <div class="supplier-importer__stat">
            <span class="supplier-importer__stat-label">
                Ошибок
            </span>

            <span class="supplier-importer__stat-value">
                ${Number(progress.errors || 0)}
            </span>
        </div>
    `;

        info.innerHTML = `
        <p>
            <strong>Импорт завершён.</strong>
        </p>
    `;

        if (Number(progress.errors || 0) > 0) {
            loadImportErrors(data.import_id);
        }

        console.log(
            'Импорт завершён:',
            data
        );
    }

    function loadImportErrors(importId) {
        const formData = new FormData();

        formData.append(
            'action',
            'supplier_import_get_errors'
        );

        formData.append(
            'nonce',
            supplierImporter.nonce
        );

        formData.append(
            'import_id',
            importId
        );

        fetch(
            supplierImporter.ajaxUrl,
            {
                method: 'POST',
                body: formData
            }
        )
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {

                if (!result.success) {
                    throw new Error(
                        result.data?.message
                        || 'Не удалось получить ошибки.'
                    );
                }

                renderImportErrors(
                    result.data.errors
                );
            })
            .catch(function (error) {
                console.error(
                    'Ошибка получения ошибок импорта:',
                    error
                );
            });
    }



    function renderImportErrors(errors) {
        if (!errorsBlock || !errorsList) {
            return;
        }

        errorsBlock.hidden = false;

        if (!errors.length) {
            errorsList.innerHTML = `
            <p>Ошибок не найдено.</p>
        `;

            return;
        }

        errorsList.innerHTML = `
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Строка</th>
                    <th>Артикул</th>
                    <th>Тип</th>
                    <th>Ошибка</th>
                </tr>
            </thead>

            <tbody>
                ${errors.map(function (error) {
            return `
                        <tr>
                            <td>
                                ${escapeHtml(
                error.csv_row
            )}
                            </td>

                            <td>
                                ${escapeHtml(
                error.sku || '—'
            )}
                            </td>

                            <td>
                                ${escapeHtml(
                error.error_type
            )}
                            </td>

                            <td>
                                ${escapeHtml(
                error.message
            )}
                            </td>
                        </tr>
                    `;
        }).join('')}
            </tbody>
        </table>
    `;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function loadCategoryMappings(supplier) {

        const formData = new FormData();

        formData.append(
            'action',
            'supplier_mapping_get'
        );

        formData.append(
            'nonce',
            supplierImporter.nonce
        );

        formData.append(
            'supplier',
            supplier
        );

        return fetch(
            supplierImporter.ajaxUrl,
            {
                method: 'POST',
                body: formData
            }
        )
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {

                if (!result.success) {
                    throw new Error(
                        result.data?.message
                        || 'Не удалось загрузить сохранённые сопоставления.'
                    );
                }

                return result.data.mappings || {};
            });
    }

    async function renderAnalysis(analysis, supplier) {
        const mappingBlock = document.querySelector(
            '#supplier-import-mapping'
        );

        if (!mappingBlock) {
            return;
        }

        mappingBlock.hidden = false;
        mappingBlock.dataset.supplier = supplier;

        const categories = Array.isArray(
            analysis.categories
        )
            ? analysis.categories
            : [];

        let savedMappings = {};

        try {
            savedMappings =
                await loadCategoryMappings(supplier);

            console.log(
                'Сохранённые сопоставления категорий:',
                savedMappings
            );

        } catch (error) {
            console.error(
                'Ошибка загрузки сохранённых категорий:',
                error
            );
        }

        console.log(
            'CSV analysis:',
            analysis
        );

        console.log(
            'CSV attribute values:',
            analysis.attributes[2]
        );

        mappingBlock.innerHTML = `
        <h3>Сопоставление данных</h3>

        <div class="supplier-importer__mapping-section">
            <h4>Категории</h4>

            <div class="supplier-importer__mapping-categories">
                ${categories.map(function (category, index) {

            const saved =
                savedMappings[category.source] || null;

            const savedTargetId =
                saved
                    ? Number(saved.target_id)
                    : 0;

            const savedTargetName =
                saved
                    ? saved.target_name
                    : '';

            return `
                        <div
                            class="supplier-importer__mapping-item"
                            data-category-index="${index}"
                        >
                            <div class="supplier-importer__mapping-source">

                                <span class="supplier-importer__mapping-label">
                                    Категория поставщика
                                </span>

                                <strong>
                                    ${escapeHtml(category.source)}
                                </strong>

                                <span>
                                    Товаров: ${category.count}
                                </span>

                            </div>

                            <div class="supplier-importer__mapping-search">

                                <label>
                                    Категория на сайте
                                </label>

                                <input
                                    type="text"
                                    class="supplier-importer__mapping-input"
                                    placeholder="Начните вводить название..."
                                    autocomplete="off"
                                >

                                <div
                                    class="supplier-importer__mapping-results"
                                    hidden
                                ></div>

                            </div>

                            <div
                                class="supplier-importer__mapping-selected"
                                data-target-id="${savedTargetId}"
                                ${savedTargetId ? '' : 'hidden'}
                            >
                                ${savedTargetId
                    ? `
                                        <span>
                                            ${escapeHtml(savedTargetName)}
                                        </span>
                                    `
                    : ''
                }
                            </div>

                        </div>
                    `;

        }).join('')}
            </div>
        </div>

        <div class="supplier-importer__mapping-section">
            <h4>Атрибуты</h4>

            <div class="supplier-importer__mapping-attributes">
                ${renderAttributes(
            Array.isArray(analysis.attributes)
                ? analysis.attributes
                : []
        )}
            </div>
        </div>

        <div class="supplier-importer__mapping-actions">
            <button
                type="button"
                class="button button-primary"
                id="supplier-import-mapping-save"
            >
                Сохранить и начать импорт
            </button>

            <span
                class="supplier-importer__mapping-status"
                hidden
            ></span>
        </div>
    `;

        initCategorySearch(
            mappingBlock
        );

        initAttributeSearch(
            mappingBlock
        );

        initMappingSave(
            mappingBlock,
            currentImportId
        );
    }

    function renderAttributes(attributes) {

        if (!attributes.length) {
            return `
            <p>
                Атрибутов для сопоставления не найдено.
            </p>
        `;
        }

        return attributes.map(function (attribute, attributeIndex) {

            const values = Array.isArray(attribute.values)
                ? attribute.values
                : [];

            return `
            <div
                class="supplier-importer__attribute"
                data-attribute-index="${attributeIndex}"
                data-source="${escapeHtml(attribute.source)}"
            >

                <div class="supplier-importer__attribute-header">

                    <strong>
                        ${escapeHtml(attribute.source)}
                    </strong>

                    <span>
                        Товаров: ${Number(attribute.count || 0)}
                    </span>

                </div>

                <div class="supplier-importer__attribute-values">

                    ${values.map(function (item, valueIndex) {

                const sourceValue =
                    typeof item === 'object'
                        ? item.value
                        : item;

                const count =
                    typeof item === 'object'
                        ? Number(item.count || 0)
                        : 0;

                return `
                            <div
                                class="supplier-importer__mapping-item supplier-importer__attribute-value"
                                data-attribute="${escapeHtml(attribute.source)}"
                                data-value-index="${valueIndex}"
                            >

                                <div class="supplier-importer__mapping-source">

                                    <span class="supplier-importer__mapping-label">
                                        Значение поставщика
                                    </span>

                                    <strong>
                                        ${escapeHtml(sourceValue)}
                                    </strong>

                                    <span>
                                        Товаров: ${count}
                                    </span>

                                </div>

                                <div class="supplier-importer__mapping-search">

                                    <label>
                                        Значение на сайте
                                    </label>

                                    <input
                                        type="text"
                                        class="supplier-importer__mapping-input"
                                        placeholder="Начните вводить название..."
                                        autocomplete="off"
                                    >

                                    <div
                                        class="supplier-importer__mapping-results"
                                        hidden
                                    ></div>

                                </div>

                                <div
                                    class="supplier-importer__mapping-selected"
                                    hidden
                                ></div>

                            </div>
                        `;

            }).join('')}

                </div>

            </div>
        `;

        }).join('');
    }

    function initCategorySearch(mappingBlock) {
        const items = mappingBlock.querySelectorAll(
            '.supplier-importer__mapping-item'
        );

        items.forEach(function (item) {
            const input = item.querySelector(
                '.supplier-importer__mapping-input'
            );

            const results = item.querySelector(
                '.supplier-importer__mapping-results'
            );

            const selected = item.querySelector(
                '.supplier-importer__mapping-selected'
            );

            if (!input || !results || !selected) {
                return;
            }

            let timeout = null;

            input.addEventListener(
                'input',
                function () {
                    const search = input.value.trim();

                    clearTimeout(timeout);

                    if (search.length < 2) {
                        results.innerHTML = '';
                        results.hidden = true;

                        return;
                    }

                    timeout = setTimeout(
                        function () {
                            searchCategories(
                                search,
                                results,
                                selected,
                                input
                            );
                        },
                        300
                    );
                }
            );
        });
    }

    function searchCategories(
        search,
        results,
        selected,
        input
    ) {
        const formData = new FormData();

        formData.append(
            'action',
            'supplier_mapping_search_categories'
        );

        formData.append(
            'nonce',
            supplierImporter.nonce
        );

        formData.append(
            'search',
            search
        );

        results.hidden = false;

        results.innerHTML = `
        <div class="supplier-importer__mapping-loading">
            Поиск...
        </div>
    `;

        fetch(
            supplierImporter.ajaxUrl,
            {
                method: 'POST',
                body: formData
            }
        )
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {

                if (!result.success) {
                    throw new Error(
                        result.data?.message
                        || 'Не удалось выполнить поиск.'
                    );
                }

                const items = Array.isArray(
                    result.data.items
                )
                    ? result.data.items
                    : [];

                if (!items.length) {
                    results.innerHTML = `
                    <div class="supplier-importer__mapping-empty">
                        Ничего не найдено.
                    </div>
                `;

                    return;
                }

                results.innerHTML = items.map(
                    function (item) {
                        return `
                        <button
                            type="button"
                            class="supplier-importer__mapping-result"
                            data-id="${item.id}"
                            data-name="${escapeHtml(
                            item.name
                        )}"
                        >
                            <span>
                                ${escapeHtml(
                            item.name
                        )}
                            </span>

                            <small>
                                ${item.count} товаров
                            </small>
                        </button>
                    `;
                    }
                ).join('');

                results
                    .querySelectorAll(
                        '.supplier-importer__mapping-result'
                    )
                    .forEach(function (button) {

                        button.addEventListener(
                            'click',
                            function () {

                                const id =
                                    Number(
                                        button.dataset.id
                                    );

                                const name =
                                    button.dataset.name;

                                input.value = name;

                                results.innerHTML = '';

                                results.hidden = true;

                                selected.hidden = false;

                                selected.innerHTML = `
                                <span>
                                    Выбрано:
                                    <strong>
                                        ${escapeHtml(
                                    name
                                )}
                                    </strong>
                                </span>

                                <button
                                    type="button"
                                    class="supplier-importer__mapping-clear"
                                >
                                    Изменить
                                </button>
                            `;

                                selected.dataset.targetId =
                                    id;
                            }
                        );
                    });
            })
            .catch(function (error) {

                console.error(
                    'Ошибка поиска категорий:',
                    error
                );

                results.innerHTML = `
                <div class="supplier-importer__mapping-error">
                    ${escapeHtml(
                    error.message
                )}
                </div>
            `;
            });
    }

    function initAttributeSearch(mappingBlock) {

        const items = mappingBlock.querySelectorAll(
            '.supplier-importer__attribute-value'
        );

        items.forEach(function (item) {

            const input = item.querySelector(
                '.supplier-importer__mapping-input'
            );

            const results = item.querySelector(
                '.supplier-importer__mapping-results'
            );

            const selected = item.querySelector(
                '.supplier-importer__mapping-selected'
            );

            if (
                !input
                || !results
                || !selected
            ) {
                return;
            }

            let timeout = null;

            input.addEventListener(
                'input',
                function () {

                    const search =
                        input.value.trim();

                    clearTimeout(timeout);

                    if (search.length < 2) {
                        results.innerHTML = '';
                        results.hidden = true;

                        return;
                    }

                    timeout = setTimeout(
                        function () {

                            const attribute =
                                item.dataset.attribute;

                            searchAttributeTerms(
                                attribute,
                                search,
                                results,
                                selected,
                                input
                            );

                        },
                        300
                    );
                }
            );
        });
    }

    function searchAttributeTerms(
        attribute,
        search,
        results,
        selected,
        input
    ) {

        const formData = new FormData();

        formData.append(
            'action',
            'supplier_mapping_search_attribute_terms'
        );

        formData.append(
            'nonce',
            supplierImporter.nonce
        );

        /*
         * Пока передаём source напрямую.
         *
         * Сервер сам добавит pa_:
         *
         * цвет арматуры
         * → pa_цвет арматуры
         *
         * Но это НЕ то, что нам нужно.
         *
         * Поэтому taxonomy будем брать
         * из карты ниже.
         */
        const taxonomyMap = {

            'стиль': 'style',

            'форма': 'forma',

            'цвет арматуры': 'armature-color',

            'материал арматуры':
                'armature-material',

            'цвет плафона':
                'shade-color',

            'материал плафона':
                'shade-material',

            'ширина/диаметр':
                'shirina-diametr',

            'длина':
                'dlina-mm',

            'высота':
                'vysota-mm',

            'тип лампы':
                'tip-lampochki',

            'мощность общая':
                'obshhaya-moshhnost',

            'напряжение':
                'napryazhenie',

            'тип цоколя':
                'base',

            'степень защиты ip':
                'ip',

            'цвет свечения':
                'color-temperature',

            'место установки':
                'tip-krepleniya',

            'страна происхождения':
                'strana-proishozhdenia',

            'коллекция':
                'collection',

            'Назначение помещения':
                'room-purpose',

            'форма плафона':
                'shade-shape',

            'лампы в комплекте':
                'led',

            'площадь освещения':
                'light-area',

            'световой поток':
                'light-flux'
        };

        const taxonomy =
            taxonomyMap[attribute] || '';

        if (!taxonomy) {
            results.hidden = false;

            results.innerHTML = `
            <div class="supplier-importer__mapping-error">
                Для атрибута не настроена таксономия.
            </div>
        `;

            return;
        }

        formData.append(
            'taxonomy',
            taxonomy
        );

        formData.append(
            'search',
            search
        );

        results.hidden = false;

        results.innerHTML = `
        <div class="supplier-importer__mapping-loading">
            Поиск...
        </div>
    `;

        fetch(
            supplierImporter.ajaxUrl,
            {
                method: 'POST',
                body: formData
            }
        )
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {

                if (!result.success) {
                    throw new Error(
                        result.data?.message
                        || 'Не удалось выполнить поиск.'
                    );
                }

                const items =
                    Array.isArray(result.data.items)
                        ? result.data.items
                        : [];

                if (!items.length) {

                    results.innerHTML = `
                    <div class="supplier-importer__mapping-empty">
                        Ничего не найдено.
                    </div>
                `;

                    return;
                }

                results.innerHTML =
                    items.map(function (term) {

                        return `
                        <button
                            type="button"
                            class="supplier-importer__mapping-result"
                            data-id="${term.id}"
                            data-name="${escapeHtml(term.name)}"
                        >
                            ${escapeHtml(term.name)}
                        </button>
                    `;

                    }).join('');

                results
                    .querySelectorAll(
                        '.supplier-importer__mapping-result'
                    )
                    .forEach(function (button) {

                        button.addEventListener(
                            'click',
                            function () {

                                const id =
                                    Number(
                                        button.dataset.id
                                    );

                                const name =
                                    button.dataset.name;

                                input.value = name;

                                results.innerHTML = '';
                                results.hidden = true;

                                selected.hidden = false;

                                selected.innerHTML = `
                                <span>
                                    Выбрано:
                                    <strong>
                                        ${escapeHtml(name)}
                                    </strong>
                                </span>

                                <button
                                    type="button"
                                    class="supplier-importer__mapping-clear"
                                >
                                    Изменить
                                </button>
                            `;

                                selected.dataset.targetId =
                                    id;

                                selected.dataset.taxonomy =
                                    taxonomy;
                            }
                        );
                    });
            })
            .catch(function (error) {

                console.error(
                    'Ошибка поиска атрибутов:',
                    error
                );

                results.innerHTML = `
                <div class="supplier-importer__mapping-error">
                    ${escapeHtml(error.message)}
                </div>
            `;
            });
    }

    function initMappingSave(
        mappingBlock,
        importId
    ) {

        const button = mappingBlock.querySelector(
            '#supplier-import-mapping-save'
        );

        const status = mappingBlock.querySelector(
            '.supplier-importer__mapping-status'
        );

        if (!button) {
            return;
        }

        button.addEventListener(
            'click',
            function () {

                const supplier =
                    mappingBlock.dataset.supplier;

                const items =
                    mappingBlock.querySelectorAll(
                        '.supplier-importer__mapping-item'
                    );

                const mappings = [];

                items.forEach(function (item) {

                    const sourceElement =
                        item.querySelector(
                            '.supplier-importer__mapping-source strong'
                        );

                    const selected =
                        item.querySelector(
                            '.supplier-importer__mapping-selected'
                        );

                    if (
                        !sourceElement
                        || !selected
                    ) {
                        return;
                    }

                    const targetId =
                        Number(
                            selected.dataset.targetId
                        );

                    if (!targetId) {
                        return;
                    }

                    mappings.push({
                        source_value:
                            sourceElement.textContent.trim(),

                        target_id:
                            targetId
                    });
                });

                saveMappings(
                    supplier,
                    mappings,
                    mappingBlock,
                    importId,
                    button,
                    status
                );
            }
        );
    }

    function saveMappings(
        supplier,
        mappings,
        mappingBlock,
        importId,
        button,
        status
    ) {

        const formData = new FormData();

        formData.append(
            'action',
            'supplier_mapping_save'
        );

        formData.append(
            'nonce',
            supplierImporter.nonce
        );

        formData.append(
            'supplier',
            supplier
        );

        formData.append(
            'mappings',
            JSON.stringify(mappings)
        );

        button.disabled = true;

        status.hidden = false;
        status.textContent = 'Сохраняем...';

        fetch(
            supplierImporter.ajaxUrl,
            {
                method: 'POST',
                body: formData
            }
        )
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {

                if (!result.success) {
                    throw new Error(
                        result.data?.message
                        || 'Не удалось сохранить сопоставления.'
                    );
                }

                status.textContent =
                    'Сопоставления сохранены.';

                console.log(
                    'Сохранено mappings:',
                    result.data.saved
                );

                button.disabled = false;

                const savedCount = Number(
                    result.data.saved || 0
                );

                status.textContent =
                    'Категории сохранены.';

                saveAttributeMappings(
                    supplier,
                    mappingBlock,
                    importId,
                    button,
                    status
                );

            })
            .catch(function (error) {

                console.error(
                    'Ошибка сохранения mappings:',
                    error
                );

                status.textContent =
                    error.message;

                button.disabled = false;
            });
    }

    function saveAttributeMappings(
        supplier,
        mappingBlock,
        importId,
        button,
        status
    ) {

        const items =
            mappingBlock.querySelectorAll(
                '.supplier-importer__attribute-value'
            );

        const mappings = [];

        items.forEach(function (item) {

            const selected =
                item.querySelector(
                    '.supplier-importer__mapping-selected'
                );

            const sourceValue =
                item.querySelector(
                    '.supplier-importer__mapping-source strong'
                );

            if (
                !selected
                || !sourceValue
            ) {
                return;
            }

            const targetId =
                Number(
                    selected.dataset.targetId
                );

            const taxonomy =
                selected.dataset.taxonomy || '';

            const sourceParent =
                item.dataset.attribute || '';

            if (
                !targetId
                || !taxonomy
                || !sourceParent
            ) {
                return;
            }

            mappings.push({

                source_parent:
                    sourceParent,

                source_value:
                    sourceValue.textContent.trim(),

                target_id:
                    targetId,

                taxonomy:
                    taxonomy
            });
        });

        /*
         * Если пользователь ничего
         * не сопоставил — просто запускаем импорт.
         */
        if (!mappings.length) {

            status.textContent =
                'Сопоставления сохранены.';

            button.disabled = true;

            processImport(importId);

            return;
        }

        const formData = new FormData();

        formData.append(
            'action',
            'supplier_mapping_save_attribute_values'
        );

        formData.append(
            'nonce',
            supplierImporter.nonce
        );

        formData.append(
            'supplier',
            supplier
        );

        formData.append(
            'mappings',
            JSON.stringify(mappings)
        );

        status.textContent =
            'Сохраняем атрибуты...';

        fetch(
            supplierImporter.ajaxUrl,
            {
                method: 'POST',
                body: formData
            }
        )
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {

                if (!result.success) {
                    throw new Error(
                        result.data?.message
                        || 'Не удалось сохранить атрибуты.'
                    );
                }

                status.textContent =
                    'Сохранено сопоставлений: '
                    + Number(result.data.saved || 0);

                button.disabled = true;

                processImport(importId);
            })
            .catch(function (error) {

                console.error(
                    'Ошибка сохранения атрибутов:',
                    error
                );

                status.textContent =
                    error.message;

                button.disabled = false;
            });
    }
});

document.addEventListener('DOMContentLoaded', () => {

    const resetButton = document.querySelector(
        '#supplier-importer-reset-statistics'
    );

    if (!resetButton) {
        return;
    }

    const message = document.querySelector(
        '#supplier-importer-statistics-message'
    );

    resetButton.addEventListener('click', async () => {

        const confirmed = window.confirm(
            'Вы действительно хотите сбросить всю статистику импорта?'
        );

        if (!confirmed) {
            return;
        }

        resetButton.disabled = true;
        resetButton.textContent = 'Сбрасываем...';

        try {

            const formData = new FormData();

            formData.append(
                'action',
                'supplier_importer_reset_statistics'
            );

            formData.append(
                'nonce',
                supplierImporter.nonce
            );

            const response = await fetch(supplierImporter.ajaxUrl, {
                method: 'POST',
                body: formData
            });

            const responseText = await response.text();

            console.log('=== IMPORT AJAX RAW RESPONSE ===');
            console.log(responseText);

            let data;

            try {
                data = JSON.parse(responseText);
            } catch (error) {
                console.error('=== JSON PARSE ERROR ===');
                console.error(error);
                console.error('RAW RESPONSE:', responseText);

                throw error;
            }

            if (!data.success) {
                throw new Error(
                    data.data?.message ||
                    'Не удалось сбросить статистику.'
                );
            }

            window.location.reload();

        } catch (error) {

            console.error(
                'Ошибка сброса статистики:',
                error
            );

            if (message) {
                message.textContent = error.message;
                message.hidden = false;
            }

            resetButton.disabled = false;
            resetButton.textContent = 'Сбросить статистику';
        }

    });

});
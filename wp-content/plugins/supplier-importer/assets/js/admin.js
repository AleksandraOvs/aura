document.addEventListener('DOMContentLoaded', function () {

    console.log('SUPPLIER IMPORTER JS LOADED');

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
    let lastErrorsCount = 0;

    let currentAttributeConfig = {};
    let availableWooAttributes = [];

    console.log('SUPPLIER IMPORTER FORM HANDLER INIT');
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
                       ${escapeHtml(data.supplier.name || '')}
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

        const progressCount = document.querySelector(
            '#supplier-import-progress-count'
        );

        progressBlock.hidden = false;

        const progress = data.progress || {};

        const processed = Number(
            progress.processed || 0
        );

        const total = Number(
            progress.total || data.total || 0
        );

        const percent = Number(
            progress.percent || 0
        );

        const errorsCount = Number(
            progress.errors || 0
        );

        console.log('IMPORT PROGRESS:', progress);
        console.log('ERRORS COUNT:', errorsCount);
        console.log('CURRENT IMPORT ID:', currentImportId);

        if (progressCount) {
            const processed = Number(progress.processed || 0);
            const total = Number(progress.total || data.total || 0);

            progressCount.textContent = processed + ' / ' + total;
        }

        progressBar.style.width =
            percent + '%';

        progressPercent.textContent =
            percent + '%';

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
                ${errorsCount}
            </span>
        </div>
    `;

        if (
            errorsCount > 0
            && currentImportId
            && errorsCount !== lastErrorsCount
        ) {
            lastErrorsCount = errorsCount;

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
            const processed = Number(progress.processed || 0);
            const total = Number(progress.total || data.total || 0);

            progressCount.textContent = processed + ' / ' + total;
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
            loadImportErrors(data.import_id || currentImportId);
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

                console.log(
                    'Ошибки импорта:',
                    result.data.errors
                );

                renderImportErrors(
                    result.data.errors || []
                );
            })
            .catch(function (error) {

                console.error(
                    'Ошибка получения ошибок импорта:',
                    error
                );

                if (errorsBlock && errorsList) {
                    errorsBlock.hidden = false;

                    errorsList.innerHTML = `
                    <p class="supplier-importer__mapping-error">
                        ${escapeHtml(error.message)}
                    </p>
                `;
                }
            });
    }



    function renderImportErrors(errors) {
        if (!errorsBlock || !errorsList) {
            return;
        }

        errorsBlock.hidden = false;

        if (!Array.isArray(errors) || !errors.length) {
            errorsList.innerHTML = `
            <p>Ошибок не найдено.</p>
        `;

            return;
        }

        errorsList.innerHTML = `
        <div class="supplier-importer__errors-summary">
            Найдено ошибок: <strong>${errors.length}</strong>
        </div>

        <div class="supplier-importer__errors-list">
            ${errors.map(function (error, index) {

            let rawData = {};

            if (error.raw_data) {
                try {
                    rawData =
                        typeof error.raw_data === 'string'
                            ? JSON.parse(error.raw_data)
                            : error.raw_data;
                } catch (parseError) {
                    console.error(
                        'Не удалось разобрать raw_data:',
                        parseError,
                        error.raw_data
                    );
                }
            }

            const rawDataHtml =
                rawData && typeof rawData === 'object'
                    ? Object.entries(rawData)
                        .map(function ([key, value]) {
                            return `
                                    <tr>
                                        <th>
                                            ${escapeHtml(key)}
                                        </th>

                                        <td>
                                            ${escapeHtml(
                                value === null
                                    ? ''
                                    : String(value)
                            )}
                                        </td>
                                    </tr>
                                `;
                        })
                        .join('')
                    : '';

            return `
                    <div class="supplier-importer__error-item">

                        <div class="supplier-importer__error-header">

                            <div>
                                <strong>
                                    Ошибка #${index + 1}
                                </strong>

                                <span>
                                    Строка CSV:
                                    ${escapeHtml(error.csv_row)}
                                </span>
                            </div>

                            <span
                                class="supplier-importer__error-type"
                            >
                                ${escapeHtml(
                error.error_type || 'unknown'
            )}
                            </span>

                        </div>

                        <div class="supplier-importer__error-info">

                            <div>
                                <span class="supplier-importer__error-label">
                                    Артикул
                                </span>

                                <strong>
                                    ${escapeHtml(
                error.sku || '—'
            )}
                                </strong>
                            </div>

                            <div>
                                <span class="supplier-importer__error-label">
                                    Строка CSV
                                </span>

                                <strong>
                                    ${escapeHtml(error.csv_row)}
                                </strong>
                            </div>

                        </div>

                        <div class="supplier-importer__error-message">

                            <span class="supplier-importer__error-label">
                                Причина ошибки
                            </span>

                            <div>
                                ${escapeHtml(
                error.message || 'Неизвестная ошибка'
            )}
                            </div>

                        </div>

                        ${rawDataHtml
                    ? `
                                    <button
                                        type="button"
                                        class="button supplier-importer__error-toggle"
                                    >
                                        Показать данные строки
                                    </button>

                                    <div
                                        class="supplier-importer__error-raw"
                                        hidden
                                    >
                                        <table class="widefat striped">
                                            <tbody>
                                                ${rawDataHtml}
                                            </tbody>
                                        </table>
                                    </div>
                                `
                    : ''
                }

                    </div>
                `;
        }).join('')}
        </div>
    `;

        initErrorToggles();
    }

    function initErrorToggles() {
        if (!errorsList) {
            return;
        }

        errorsList
            .querySelectorAll('.supplier-importer__error-toggle')
            .forEach(function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        const rawBlock =
                            button.nextElementSibling;

                        if (!rawBlock) {
                            return;
                        }

                        const isHidden =
                            rawBlock.hidden;

                        rawBlock.hidden = !isHidden;

                        button.textContent =
                            isHidden
                                ? 'Скрыть данные строки'
                                : 'Показать данные строки';
                    }
                );
            });
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

    function loadAttributeConfig(supplier) {
        const formData = new FormData();

        formData.append('action', 'supplier_mapping_get_attribute_config');
        formData.append('nonce', supplierImporter.nonce);
        formData.append('supplier', supplier);

        return fetch(supplierImporter.ajaxUrl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                if (!result.success) {
                    throw new Error(
                        result.data?.message ||
                        'Не удалось загрузить настройки атрибутов.'
                    );
                }

                currentAttributeConfig = result.data.config || {};
                availableWooAttributes = result.data.attributes || [];

                return currentAttributeConfig;
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

        try {
            await loadAttributeConfig(supplier);
        } catch (error) {
            console.error(error);
            info.textContent = error.message;
            return;
        }

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
            Array.isArray(analysis.attributes)
                ? analysis.attributes[2]
                : undefined
        );

        mappingBlock.innerHTML = `
    <h3>Сопоставление данных</h3>

    <div class="supplier-importer__mapping-tabs">
        <button
            type="button"
            class="button is-active"
            data-mapping-tab="categories"
        >
            Категории
        </button>

        <button
            type="button"
            class="button"
            data-mapping-tab="values"
        >
            Значения атрибутов
        </button>

        <button
            type="button"
            class="button"
            data-mapping-tab="config"
        >
            Названия атрибутов
        </button>
    </div>

    <div
        class="supplier-importer__mapping-panel"
        data-mapping-panel="categories"
    >
        <div class="supplier-importer__mapping-section">
            <h4>Сопоставление категорий</h4>

            <div class="supplier-importer__mapping-categories">
                ${categories.map(function (category, index) {
            const saved =
                savedMappings[category.source] || null;

            const savedTargetId =
                saved ? Number(saved.target_id) : 0;

            const savedTargetName =
                saved ? saved.target_name : '';

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
                    ? `<span>${escapeHtml(savedTargetName)}</span>`
                    : ''
                }
                            </div>
                        </div>
                    `;
        }).join('')}
            </div>
        </div>
    </div>

    <div
        class="supplier-importer__mapping-panel"
        data-mapping-panel="values"
        hidden
    >
        <div class="supplier-importer__mapping-section">
            <h4>Сопоставление значений атрибутов</h4>

            <p>
                Здесь можно связать значения из файла поставщика
                с существующими значениями атрибутов WooCommerce.
            </p>

            <div class="supplier-importer__mapping-attributes">
                ${renderAttributes(
            Array.isArray(analysis.attributes)
                ? analysis.attributes
                : []
        )}
            </div>
        </div>
    </div>

    <div
        class="supplier-importer__mapping-panel"
        data-mapping-panel="config"
        hidden
    >
        <div class="supplier-importer__mapping-section">
            <h4>Названия атрибутов</h4>

            <p>
                Выберите, в какой атрибут WooCommerce импортировать
                каждое поле поставщика. Здесь же можно изменить
                название атрибута для импортируемого товара.
                Чтобы пропустить поле, выберите «Не импортировать».
            </p>

            <div class="supplier-importer__attribute-config">
                ${renderAttributeConfig()}
            </div>
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

        mappingBlock
            .querySelectorAll('.supplier-importer__attribute-taxonomy')
            .forEach(function (select) {
                select.addEventListener('change', function () {
                    const row = select.closest(
                        '.supplier-importer__attribute-config-row'
                    );

                    if (!row) {
                        return;
                    }

                    const source = row.dataset.source;

                    if (currentAttributeConfig[source]) {
                        currentAttributeConfig[source].taxonomy = select.value;
                    }

                    mappingBlock
                        .querySelectorAll('.supplier-importer__attribute-value')
                        .forEach(function (item) {
                            if (item.dataset.attribute !== source) {
                                return;
                            }

                            const selected = item.querySelector(
                                '.supplier-importer__mapping-selected'
                            );

                            const input = item.querySelector(
                                '.supplier-importer__mapping-input'
                            );

                            const results = item.querySelector(
                                '.supplier-importer__mapping-results'
                            );

                            if (selected) {
                                selected.hidden = true;
                                selected.dataset.targetId = '';
                                selected.dataset.taxonomy = '';
                                selected.innerHTML = '';
                            }

                            if (input) {
                                input.value = '';
                            }

                            if (results) {
                                results.hidden = true;
                                results.innerHTML = '';
                            }
                        });
                });
            });

        initMappingTabs(mappingBlock);

        initCategorySearch(mappingBlock);

        mappingBlock.addEventListener('click', function (event) {
            const button = event.target.closest(
                '.supplier-importer__mapping-clear'
            );

            if (!button || !mappingBlock.contains(button)) {
                return;
            }

            const selected = button.closest(
                '.supplier-importer__mapping-selected'
            );

            if (!selected) {
                return;
            }

            selected.hidden = true;
            selected.dataset.targetId = '';
            selected.dataset.taxonomy = '';
            selected.innerHTML = '';

            const item = selected.closest(
                '.supplier-importer__mapping-item'
            );

            const input = item?.querySelector(
                '.supplier-importer__mapping-input'
            );

            if (input) {
                input.focus();
            }
        });

        initAttributeSearch(mappingBlock);

        initMappingSave(
            mappingBlock,
            currentImportId
        );

        function initMappingTabs(mappingBlock) {
            const tabs = mappingBlock.querySelectorAll('[data-mapping-tab]');
            const panels = mappingBlock.querySelectorAll('[data-mapping-panel]');

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const target = tab.dataset.mappingTab;

                    tabs.forEach(function (item) {
                        item.classList.toggle('is-active', item === tab);
                    });

                    panels.forEach(function (panel) {
                        panel.hidden = panel.dataset.mappingPanel !== target;
                    });
                });
            });
        }

        function renderAttributeConfig() {
            const entries = Object.entries(currentAttributeConfig);

            if (!entries.length) {
                return '<p>Настройки атрибутов не найдены.</p>';
            }

            return entries.map(function ([source, item]) {
                const selectedTaxonomy = item.taxonomy || '';
                const label = item.label || '';

                const options = [
                    '<option value="">Не импортировать</option>',
                    ...availableWooAttributes.map(function (attribute) {
                        const selected =
                            attribute.slug === selectedTaxonomy
                                ? ' selected'
                                : '';

                        return `
                    <option
                        value="${escapeHtml(attribute.slug)}"${selected}
                    >
                        ${escapeHtml(attribute.label)}
                        (${escapeHtml(attribute.slug)})
                    </option>
                `;
                    })
                ].join('');

                return `
            <div
                class="supplier-importer__attribute-config-row"
                data-source="${escapeHtml(source)}"
            >
                <div class="supplier-importer__attribute-config-source">
                    <strong>${escapeHtml(source)}</strong>
                </div>

                <label>
                    Атрибут WooCommerce
                    <select class="supplier-importer__attribute-taxonomy">
                        ${options}
                    </select>
                </label>

                <label>
                    Название для товара
                    <input
                        type="text"
                        class="supplier-importer__attribute-label"
                        value="${escapeHtml(label)}"
                    >
                </label>
            </div>
        `;
            }).join('');
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
                '.supplier-importer__mapping-categories .supplier-importer__mapping-item'
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

                        selected.dataset.targetId = '';
                        selected.dataset.taxonomy = '';
                        selected.hidden = true;
                        selected.innerHTML = '';

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

                input.addEventListener('input', function () {
                    const search = input.value.trim();

                    selected.dataset.targetId = '';
                    selected.dataset.taxonomy = '';
                    selected.hidden = true;
                    selected.innerHTML = '';

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
            const config = currentAttributeConfig[attribute];
            const taxonomy = config?.taxonomy || '';

            if (!taxonomy) {
                results.hidden = false;
                results.innerHTML = `
            <div class="supplier-importer__mapping-error">
                Для этого поля не выбран атрибут WooCommerce.
                Настройте его во вкладке «Названия атрибутов».
            </div>
        `;
                return;
            }

            const formData = new FormData();

            formData.append(
                'action',
                'supplier_mapping_search_attribute_terms'
            );
            formData.append('nonce', supplierImporter.nonce);
            formData.append('taxonomy', taxonomy);
            formData.append('search', search);

            results.hidden = false;
            results.innerHTML = `
        <div class="supplier-importer__mapping-loading">
            Поиск...
        </div>
    `;

            fetch(supplierImporter.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (!result.success) {
                        throw new Error(
                            result.data?.message ||
                            'Не удалось выполнить поиск.'
                        );
                    }

                    if (input.value.trim() !== search) {
                        return;
                    }

                    const terms = Array.isArray(result.data.items)
                        ? result.data.items
                        : [];

                    if (!terms.length) {
                        results.innerHTML = `
                    <div class="supplier-importer__mapping-empty">
                        Ничего не найдено.
                    </div>
                `;
                        return;
                    }

                    results.innerHTML = terms.map(function (term) {
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
                        .querySelectorAll('.supplier-importer__mapping-result')
                        .forEach(function (button) {
                            button.addEventListener('click', function () {
                                selected.dataset.targetId = button.dataset.id;
                                selected.dataset.taxonomy = taxonomy;
                                selected.hidden = false;

                                input.value = button.dataset.name;
                                results.innerHTML = '';
                                results.hidden = true;

                                selected.innerHTML = `
                            <span>
                                Выбрано:
                                <strong>${escapeHtml(button.dataset.name)}</strong>
                            </span>

                            <button
                                type="button"
                                class="supplier-importer__mapping-clear"
                            >
                                Изменить
                            </button>
                        `;
                            });
                        });
                })
                .catch(function (error) {
                    console.error('Ошибка поиска атрибутов:', error);

                    results.hidden = false;
                    results.innerHTML = `
                <div class="supplier-importer__mapping-error">
                    ${escapeHtml(error.message)}
                </div>
            `;
                });
        }

        function initMappingSave(mappingBlock, importId) {
            const button = mappingBlock.querySelector(
                '#supplier-import-mapping-save'
            );

            const status = mappingBlock.querySelector(
                '.supplier-importer__mapping-status'
            );

            if (!button || !status) {
                return;
            }

            button.addEventListener('click', function () {
                const supplier = mappingBlock.dataset.supplier;

                // Собираем только категории, не значения атрибутов.
                const items = mappingBlock.querySelectorAll(
                    '.supplier-importer__mapping-categories .supplier-importer__mapping-item'
                );

                const mappings = [];

                items.forEach(function (item) {
                    const sourceElement = item.querySelector(
                        '.supplier-importer__mapping-source strong'
                    );

                    const selected = item.querySelector(
                        '.supplier-importer__mapping-selected'
                    );

                    if (!sourceElement || !selected) {
                        return;
                    }

                    const targetId = Number(selected.dataset.targetId);

                    if (!targetId) {
                        return;
                    }

                    mappings.push({
                        source_value: sourceElement.textContent.trim(),
                        target_id: targetId
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
            });
        }

        function saveAttributeConfig(supplier, mappingBlock) {
            const config = {};

            const rows = mappingBlock.querySelectorAll(
                '.supplier-importer__attribute-config-row'
            );

            rows.forEach(function (row) {
                const source = row.dataset.source;

                const taxonomySelect = row.querySelector(
                    '.supplier-importer__attribute-taxonomy'
                );

                const labelInput = row.querySelector(
                    '.supplier-importer__attribute-label'
                );

                if (!source || !taxonomySelect || !labelInput) {
                    return;
                }

                config[source] = {
                    taxonomy: taxonomySelect.value,
                    label: labelInput.value.trim()
                };
            });

            if (!rows.length) {
                return Promise.reject(
                    new Error(
                        'Не найдены строки настроек атрибутов. Конфигурация не сохранена.'
                    )
                );
            }

            const formData = new FormData();

            formData.append(
                'action',
                'supplier_mapping_save_attribute_config'
            );

            formData.append('nonce', supplierImporter.nonce);
            formData.append('supplier', supplier);
            formData.append('config', JSON.stringify(config));

            return fetch(supplierImporter.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (!result.success) {
                        throw new Error(
                            result.data?.message ||
                            'Не удалось сохранить настройки атрибутов.'
                        );
                    }

                    currentAttributeConfig = config;

                    return result;
                });
        }

        function saveMappings(
            supplier,
            mappings,
            mappingBlock,
            importId,
            button,
            status
        ) {
            button.disabled = true;
            status.hidden = false;
            status.textContent = 'Сохраняем настройки атрибутов...';

            // Сначала сохраняем настройки полей поставщика.
            saveAttributeConfig(supplier, mappingBlock)

                .then(function () {
                    // Затем сохраняем категории.
                    status.textContent = 'Сохраняем категории...';

                    const formData = new FormData();

                    formData.append('action', 'supplier_mapping_save');
                    formData.append('nonce', supplierImporter.nonce);
                    formData.append('supplier', supplier);
                    formData.append('mappings', JSON.stringify(mappings));

                    return fetch(supplierImporter.ajaxUrl, {
                        method: 'POST',
                        body: formData
                    });
                })

                .then(function (response) {
                    return response.json();
                })

                .then(function (result) {
                    if (!result.success) {
                        throw new Error(
                            result.data?.message ||
                            'Не удалось сохранить сопоставление категорий.'
                        );
                    }

                    // Внутри этой функции сохраняются значения атрибутов.
                    // Если всё успешно, она сама запускает импорт.
                    status.textContent = 'Сохраняем значения атрибутов...';

                    return saveAttributeMappings(
                        supplier,
                        mappingBlock,
                        importId,
                        button,
                        status
                    );
                })

                .catch(function (error) {
                    console.error('Ошибка сохранения настроек:', error);

                    status.hidden = false;
                    status.textContent = error.message;

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
            const items = mappingBlock.querySelectorAll(
                '.supplier-importer__attribute-value'
            );

            const mappings = [];

            items.forEach(function (item) {
                const selected = item.querySelector(
                    '.supplier-importer__mapping-selected'
                );

                const sourceValue = item.querySelector(
                    '.supplier-importer__mapping-source strong'
                );

                if (!selected || !sourceValue) {
                    return;
                }

                const targetId = Number(selected.dataset.targetId);
                const taxonomy = selected.dataset.taxonomy || '';
                const sourceParent = item.dataset.attribute || '';

                if (!targetId || !taxonomy || !sourceParent) {
                    return;
                }

                mappings.push({
                    source_parent: sourceParent,
                    source_value: sourceValue.textContent.trim(),
                    target_id: targetId,
                    taxonomy: taxonomy
                });
            });

            // Если значения не сопоставляли, запускаем импорт.
            if (!mappings.length) {
                status.textContent = 'Запускаем импорт...';
                processImport(importId);
                return Promise.resolve();
            }

            const formData = new FormData();

            formData.append(
                'action',
                'supplier_mapping_save_attribute_values'
            );

            formData.append('nonce', supplierImporter.nonce);
            formData.append('supplier', supplier);
            formData.append('mappings', JSON.stringify(mappings));

            return fetch(supplierImporter.ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (!result.success) {
                        throw new Error(
                            result.data?.message ||
                            'Не удалось сохранить значения атрибутов.'
                        );
                    }

                    status.textContent =
                        'Сопоставления сохранены. Запускаем импорт...';

                    processImport(importId);
                })
                .catch(function (error) {
                    console.error(
                        'Ошибка сохранения значений атрибутов:',
                        error
                    );

                    status.textContent = error.message;
                    button.disabled = false;

                    throw error;
                });
        }
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
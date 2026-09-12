document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('supplier-import-start');

    if (!button) {
        return;
    }

    /*
     * Проверяем, что конфигурация действительно передана из PHP.
     */
    if (
        typeof supplierImportData === 'undefined' ||
        !supplierImportData.ajaxUrl ||
        !supplierImportData.nonce ||
        !supplierImportData.supplier
    ) {
        console.error('Supplier Importer: supplierImportData не найден.');

        button.disabled = true;
        button.textContent = 'Ошибка инициализации';

        return;
    }

    let importing = false;

    /*
     * Общее состояние импорта.
     */
    const state = {
        offset: 0,
        chunkSize: Number(supplierImportData.chunkSize) || 2,

        processed: 0,
        created: 0,
        updated: 0,
        errors: 0,

        finished: false,
    };

    /*
     * Создаём блок статуса.
     */
    let status = document.getElementById('supplier-import-status');

    if (!status) {
        status = document.createElement('div');
        status.id = 'supplier-import-status';

        status.style.marginTop = '20px';
        status.style.padding = '15px';
        status.style.background = '#f6f7f7';
        status.style.border = '1px solid #dcdcde';
        status.style.maxWidth = '900px';
        status.style.boxSizing = 'border-box';

        button.parentNode.insertBefore(
            status,
            button.nextSibling
        );
    }

    /*
     * Создаём блок прогресса.
     */
    let progressWrapper = document.getElementById(
        'supplier-import-progress'
    );

    if (!progressWrapper) {
        progressWrapper = document.createElement('div');

        progressWrapper.id = 'supplier-import-progress';

        progressWrapper.style.display = 'none';
        progressWrapper.style.marginTop = '15px';
        progressWrapper.style.maxWidth = '900px';

        const progressBar = document.createElement('div');

        progressBar.id = 'supplier-import-progress-bar';

        progressBar.style.width = '0%';
        progressBar.style.height = '24px';
        progressBar.style.background = '#2271b1';
        progressBar.style.transition = 'width 0.2s ease';
        progressBar.style.borderRadius = '3px';

        progressWrapper.appendChild(progressBar);

        button.parentNode.insertBefore(
            progressWrapper,
            button.nextSibling
        );
    }

    const progressBar = document.getElementById(
        'supplier-import-progress-bar'
    );

    /*
     * Показываем статус.
     */
    function setStatus(message, type = 'info') {

        status.style.display = 'block';

        if (type === 'error') {
            status.style.background = '#fcf0f1';
            status.style.borderColor = '#d63638';
        } else if (type === 'success') {
            status.style.background = '#edfaef';
            status.style.borderColor = '#00a32a';
        } else {
            status.style.background = '#f6f7f7';
            status.style.borderColor = '#dcdcde';
        }

        status.innerHTML = message;
    }

    /*
     * Обновляем прогресс.
     *
     * Точное общее количество товаров мы пока не знаем,
     * поэтому показываем количество обработанных товаров.
     */
    function updateProgress() {

        progressWrapper.style.display = 'block';

        /*
         * Просто визуально двигаем полоску.
         * Она не является процентом от общего количества,
         * потому что сервер пока не сообщает total.
         */
        const currentChunk = state.chunkSize > 0
            ? state.processed % state.chunkSize
            : 0;

        let percent = 0;

        if (state.finished) {
            percent = 100;
        } else if (currentChunk === 0) {
            /*
             * Каждый завершённый chunk немного двигает полоску.
             */
            percent = Math.min(
                95,
                Math.max(
                    5,
                    Math.floor(state.processed / state.chunkSize) * 2
                )
            );
        } else {
            percent = Math.min(
                95,
                Math.max(
                    5,
                    Math.floor(state.processed / state.chunkSize) * 2
                )
            );
        }

        progressBar.style.width = percent + '%';
    }

    /*
     * Обновляем текст статистики.
     */
    function updateStats() {

        setStatus(`
            <strong>Импорт выполняется...</strong>

            <div style="margin-top:8px;">
                Поставщик: <strong>${escapeHtml(
            supplierImportData.supplier
        )}</strong>
            </div>

            <div style="margin-top:5px;">
                Обработано: <strong>${state.processed}</strong>
            </div>

            <div style="margin-top:5px;">
                Создано: <strong>${state.created}</strong>
                &nbsp;&nbsp;
                Обновлено: <strong>${state.updated}</strong>
                &nbsp;&nbsp;
                Ошибок: <strong>${state.errors}</strong>
            </div>

            <div style="margin-top:5px;">
                Следующая позиция: <strong>${state.offset}</strong>
            </div>
        `);

        updateProgress();
    }

    /*
     * Экранируем значения перед вставкой в HTML.
     */
    function escapeHtml(value) {

        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /*
     * Выводим ошибки конкретных товаров.
     */
    function renderErrors(results) {

        const errors = results.filter(
            item => item && item.success === false
        );

        if (!errors.length) {
            return;
        }

        let html = `
            <div style="
                margin-top:15px;
                padding-top:15px;
                border-top:1px solid #dcdcde;
            ">
                <strong>Ошибки текущей порции:</strong>

                <ul style="margin-top:10px;">
        `;

        errors.forEach(item => {

            const name = item.name || 'Без названия';
            const sku = item.sku || '';
            const externalId = item.external_id || '';
            const error = item.error || 'Неизвестная ошибка';

            html += `
                <li style="margin-bottom:8px;">
                    <strong>${escapeHtml(name)}</strong>

                    ${sku
                    ? `<br>SKU: ${escapeHtml(sku)}`
                    : ''
                }

                    ${externalId
                    ? `<br>ID поставщика: ${escapeHtml(externalId)}`
                    : ''
                }

                    <br>
                    <span style="color:#d63638;">
                        ${escapeHtml(error)}
                    </span>
                </li>
            `;
        });

        html += `
                </ul>
            </div>
        `;

        status.insertAdjacentHTML(
            'beforeend',
            html
        );
    }

    /*
     * Финальное состояние.
     */
    function finishImport() {

        state.finished = true;

        progressWrapper.style.display = 'block';
        progressBar.style.width = '100%';

        button.disabled = false;
        button.textContent = 'Импортировать все товары';

        importing = false;

        setStatus(`
            <strong>Импорт завершён.</strong>

            <div style="margin-top:8px;">
                Обработано:
                <strong>${state.processed}</strong>
            </div>

            <div style="margin-top:5px;">
                Создано:
                <strong>${state.created}</strong>
            </div>

            <div style="margin-top:5px;">
                Обновлено:
                <strong>${state.updated}</strong>
            </div>

            <div style="margin-top:5px;">
                Ошибок:
                <strong>${state.errors}</strong>
            </div>
        `, state.errors > 0 ? 'error' : 'success');
    }

    /*
     * Остановка импорта при критической ошибке.
     */
    function failImport(message) {

        importing = false;

        button.disabled = false;
        button.textContent = 'Повторить импорт';

        setStatus(`
            <strong>Импорт остановлен.</strong>

            <div style="
                margin-top:8px;
                color:#d63638;
            ">
                ${escapeHtml(message)}
            </div>
        `, 'error');
    }

    /*
     * Один AJAX-запрос.
     */
    async function importChunk() {

        if (!importing) {
            return;
        }

        const formData = new FormData();

        formData.append(
            'action',
            'supplier_import_chunk'
        );

        formData.append(
            'nonce',
            supplierImportData.nonce
        );

        formData.append(
            'supplier',
            supplierImportData.supplier
        );

        formData.append(
            'offset',
            String(state.offset)
        );

        formData.append(
            'limit',
            String(state.chunkSize)
        );

        console.log(
            'Supplier Importer: отправка chunk',
            {
                supplier: supplierImportData.supplier,
                offset: state.offset,
                limit: state.chunkSize
            }
        );

        try {

            const response = await fetch(
                supplierImportData.ajaxUrl,
                {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                }
            );

            /*
             * Сначала проверяем HTTP.
             */
            if (!response.ok) {
                throw new Error(
                    `HTTP ошибка ${response.status}`
                );
            }

            /*
             * Получаем JSON.
             */
            const data = await response.json();

            console.log(
                'Supplier Importer: ответ AJAX',
                data
            );

            /*
             * WordPress wp_send_json_error().
             */
            if (!data.success) {

                const message =
                    data?.data?.message ||
                    'Сервер вернул ошибку.';

                throw new Error(message);
            }

            const result = data.data || {};

            /*
             * Добавляем статистику текущего chunk.
             */
            state.processed += Number(
                result.processed || 0
            );

            state.created += Number(
                result.created || 0
            );

            state.updated += Number(
                result.updated || 0
            );

            state.errors += Number(
                result.errors || 0
            );

            /*
             * Очень важно:
             * следующий offset приходит от PHP.
             */
            state.offset = Number(
                result.next_offset ?? state.offset
            );

            /*
             * Показываем результаты.
             */
            updateStats();

            if (Array.isArray(result.results)) {
                renderErrors(result.results);
            }

            /*
             * Если PHP сообщил, что импорт закончен,
             * прекращаем цепочку AJAX.
             */
            if (result.finished) {
                finishImport();
                return;
            }

            /*
             * Следующая порция.
             *
             * Небольшая задержка даёт браузеру возможность
             * обновить интерфейс между запросами.
             */
            setTimeout(
                importChunk,
                100
            );

        } catch (error) {

            console.error(
                'Supplier Importer:',
                error
            );

            failImport(
                error.message ||
                'Неизвестная ошибка AJAX.'
            );
        }
    }

    /*
     * Запуск импорта.
     */
    button.addEventListener('click', () => {

        /*
         * Не позволяем запустить два импорта одновременно.
         */
        if (importing) {
            return;
        }

        importing = true;

        /*
         * Сбрасываем состояние.
         */
        state.offset = 0;
        state.processed = 0;
        state.created = 0;
        state.updated = 0;
        state.errors = 0;
        state.finished = false;

        button.disabled = true;
        button.textContent = 'Импорт выполняется...';

        progressWrapper.style.display = 'block';
        progressBar.style.width = '2%';

        setStatus(`
            <strong>Начинаем импорт...</strong>

            <div style="margin-top:8px;">
                Поставщик:
                <strong>${escapeHtml(
            supplierImportData.supplier
        )}</strong>
            </div>

            <div style="margin-top:5px;">
                Размер порции:
                <strong>${state.chunkSize}</strong>
            </div>
        `);

        /*
         * Запускаем первую порцию.
         */
        importChunk();
    });
});
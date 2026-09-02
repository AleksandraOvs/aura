document.addEventListener('DOMContentLoaded', function () {

    /*
     * ==========================================================
     * ПОИСК — ОТКРЫТИЕ / ЗАКРЫТИЕ
     * ==========================================================
     */

    const searchButton = document.querySelector(
        '.header__bottom__button-search'
    );

    const searchBlock = document.querySelector(
        '.header__search'
    );

    const closeButton = document.querySelector(
        '.collapse-form-button'
    );

    const catalogButton = document.querySelector(
        '.header__bottom__catalog-link'
    );

    /*
     * ==========================================================
     * LIVE SEARCH
     * ==========================================================
     */

    const searchInput = document.querySelector(
        '#search-input'
    );

    const searchResults = document.querySelector(
        '#search-results'
    );

    /*
     * Если самого поиска нет на странице —
     * ничего дальше не выполняем.
     */
    if (!searchInput || !searchResults) {
        return;
    }


    /*
     * ==========================================================
     * ОТКРЫТИЕ / ЗАКРЫТИЕ ПОИСКА
     * ==========================================================
     */

    if (searchButton && searchBlock) {

        searchButton.addEventListener('click', function () {

            searchBlock.classList.toggle('show');

        });

    }


    /*
     * Закрытие по кнопке
     */

    if (closeButton && searchBlock) {

        closeButton.addEventListener('click', function () {

            searchBlock.classList.remove('show');

        });

    }


    /*
     * Закрытие поиска при открытии каталога
     */

    if (catalogButton && searchBlock) {

        catalogButton.addEventListener('click', function () {

            searchBlock.classList.remove('show');

        });

    }


    /*
     * Закрытие при клике вне поиска
     */

    if (searchBlock && searchButton) {

        document.addEventListener('click', function (event) {

            if (
                searchBlock.classList.contains('show') &&
                !searchBlock.contains(event.target) &&
                !searchButton.contains(event.target)
            ) {

                searchBlock.classList.remove('show');

            }

        });

    }


    /*
     * ==========================================================
     * НАСТРОЙКИ ПОИСКА
     * ==========================================================
     */

    let searchTimer = null;
    let controller = null;

    const SEARCH_DELAY = 350;
    const MIN_QUERY_LENGTH = 2;


    /*
     * ==========================================================
     * ПОКАЗАТЬ РЕЗУЛЬТАТЫ
     * ==========================================================
     */

    function showResults() {

        searchResults.classList.add('show');

    }


    /*
     * ==========================================================
     * СКРЫТЬ РЕЗУЛЬТАТЫ
     * ==========================================================
     */

    function hideResults() {

        searchResults.classList.remove('show');

        searchResults.innerHTML = '';

    }


    /*
     * ==========================================================
     * ЗАГРУЗКА
     * ==========================================================
     */

    function showLoading() {

        searchResults.innerHTML = `
        <div class="search-results__loading">
            <span>Ищем</span>
            <span class="search-results__dots">
                <i></i>
                <i></i>
                <i></i>
            </span>
        </div>
    `;

        showResults();

    }


    /*
     * ==========================================================
     * НИЧЕГО НЕ НАЙДЕНО
     * ==========================================================
     */

    function showEmpty() {

        searchResults.innerHTML = `
            <div class="search-results__empty">
                Ничего не найдено
            </div>
        `;

        showResults();

    }


    /*
     * ==========================================================
     * ВЫВОД РЕЗУЛЬТАТОВ
     * ==========================================================
     */

    function renderResults(results) {

        if (!results.length) {

            showEmpty();

            return;

        }


        searchResults.innerHTML = results.map(function (item) {

            return `
                <a
                    href="${item.url}"
                    class="search-results__item"
                >

                    ${item.image ? `
                        <span class="search-results__image">
                            <img
                                src="${item.image}"
                                alt=""
                            >
                        </span>
                    ` : ''}

                    <span class="search-results__content">

                        <span class="search-results__title">
                            ${item.title}
                        </span>

                        ${item.sku ? `
                            <span class="search-results__sku">
                                Артикул: ${item.sku}
                            </span>
                        ` : ''}

                        ${item.type ? `
                            <span class="search-results__type">
                                ${item.type}
                            </span>
                        ` : ''}

                    </span>

                </a>
            `;

        }).join('');

        showResults();

    }


    /*
     * ==========================================================
     * AJAX ПОИСК
     * ==========================================================
     */

    async function search(query) {

        /*
         * Отменяем предыдущий запрос,
         * если пользователь продолжил печатать.
         */

        if (controller) {

            controller.abort();

        }

        controller = new AbortController();

        showLoading();


        try {

            const formData = new FormData();

            formData.append(
                'action',
                'live_search'
            );

            formData.append(
                's',
                query
            );


            const response = await fetch(
                auraSearch.ajaxUrl,
                {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                }
            );


            /*
             * Получаем сначала обычный текст.
             *
             * Это специально для диагностики.
             * Если WordPress/PHP вернёт ошибку,
             * мы увидим её в консоли.
             */

            const responseText = await response.text();

            console.log(
                'Live search status:',
                response.status
            );

            console.log(
                'Live search response:',
                responseText
            );


            if (!response.ok) {

                throw new Error(
                    `HTTP ${response.status}: ${responseText}`
                );

            }


            /*
             * Преобразуем ответ WordPress в JSON
             */

            const data = JSON.parse(responseText);


            console.log(
                'Live search data:',
                data
            );


            renderResults(
                data.data?.results || []
            );


        } catch (error) {

            /*
             * Отмена старого запроса —
             * это не ошибка.
             */

            if (error.name === 'AbortError') {

                return;

            }


            console.error(
                'Live search error:',
                error
            );


            searchResults.innerHTML = `
                <div class="search-results__empty">
                    Ошибка поиска
                </div>
            `;

            showResults();

        }

    }


    /*
     * ==========================================================
     * ВВОД В ПОИСК
     * ==========================================================
     */

    searchInput.addEventListener(
        'input',
        function () {

            const query = searchInput.value.trim();


            /*
             * Отменяем отложенный поиск
             */

            clearTimeout(searchTimer);


            /*
             * Если меньше двух символов —
             * ничего не ищем.
             */

            if (query.length < MIN_QUERY_LENGTH) {

                hideResults();

                return;

            }


            /*
             * Ждём 350 мс после окончания ввода.
             */

            searchTimer = setTimeout(
                function () {

                    search(query);

                },
                SEARCH_DELAY
            );

        }
    );


    /*
     * ==========================================================
     * ESC — ЗАКРЫТЬ РЕЗУЛЬТАТЫ
     * ==========================================================
     */

    searchInput.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                hideResults();

                searchInput.blur();

            }

        }
    );

});
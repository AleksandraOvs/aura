document.addEventListener('DOMContentLoaded', function () {

    const searchButton = document.querySelector(
        '.header__bottom__button-search'
    );

    const searchBlock = document.querySelector('.header__search');

    const closeButton = document.querySelector(
        '.collapse-form-button'
    );

    const catalogButton = document.querySelector('.header__bottom__catalog-link');

    if (!searchButton || !searchBlock || !closeButton) return;

    // Открытие / закрытие по кнопке поиска
    searchButton.addEventListener('click', function () {
        searchBlock.classList.toggle('show');
    });

    // Закрытие по кнопке
    closeButton.addEventListener('click', function () {
        searchBlock.classList.remove('show');
    });

    // Закрытие каталога при открытии меню
    if (catalogButton) {

        catalogButton.addEventListener('click', function () {

            searchBlock.classList.remove('show');

        });

    }

    // Закрытие при клике вне поиска
    document.addEventListener('click', function (event) {

        if (
            searchBlock.classList.contains('show') &&
            !searchBlock.contains(event.target) &&
            !searchButton.contains(event.target)
        ) {
            searchBlock.classList.remove('show');
        }

    });

});

document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.querySelector('#search-input');
    const searchResults = document.querySelector('#search-results');

    if (!searchInput || !searchResults) return;

    let searchTimer = null;
    let controller = null;

    const SEARCH_DELAY = 350;
    const MIN_QUERY_LENGTH = 2;

    function showResults() {
        searchResults.classList.add('show');
    }

    function hideResults() {
        searchResults.classList.remove('show');
        searchResults.innerHTML = '';
    }

    function showLoading() {
        searchResults.innerHTML = `
            <div class="search-results__loading">
                Поиск...
            </div>
        `;

        showResults();
    }

    function showEmpty() {
        searchResults.innerHTML = `
            <div class="search-results__empty">
                Ничего не найдено
            </div>
        `;

        showResults();
    }

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
                    <span class="search-results__title">
                        ${item.title}
                    </span>

                    ${item.type ? `
                        <span class="search-results__type">
                            ${item.type}
                        </span>
                    ` : ''}
                </a>
            `;

        }).join('');

        showResults();
    }

    async function search(query) {

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();

        showLoading();

        try {

            /*
             * В будущем здесь будет WordPress AJAX/REST URL.
             */
            const url = `/search?q=${encodeURIComponent(query)}`;

            const response = await fetch(url, {
                method: 'GET',
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Ошибка поиска');
            }

            const data = await response.json();

            renderResults(data.results || []);

        } catch (error) {

            if (error.name === 'AbortError') {
                return;
            }

            console.error(error);

            searchResults.innerHTML = `
                <div class="search-results__empty">
                    Здесь пока результатов нет
                </div>
            `;

        }
    }

    searchInput.addEventListener('input', function () {

        const query = searchInput.value.trim();

        clearTimeout(searchTimer);

        if (query.length < MIN_QUERY_LENGTH) {
            hideResults();
            return;
        }

        searchTimer = setTimeout(function () {
            search(query);
        }, SEARCH_DELAY);

    });

    // Escape закрывает результаты
    searchInput.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            hideResults();
            searchInput.blur();
        }

    });

});
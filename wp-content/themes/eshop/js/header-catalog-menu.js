document.addEventListener('DOMContentLoaded', function () {

    const catalogButton = document.querySelector('.header__bottom__catalog-link');
    const catalogMenu = document.querySelector('.header__catalog-menu');
    const closeButton = document.querySelector('.collapse-menu-button');

    const searchButton = document.querySelector(
        '.header__bottom__button-search'
    );

    if (!catalogButton || !catalogMenu) {
        console.log('Каталог: элементы не найдены');
        return;
    }

    // Открытие / закрытие каталога
    catalogButton.addEventListener('click', function (event) {

        event.stopPropagation();

        catalogMenu.classList.toggle('show');

    });

    // Закрытие по крестику
    if (closeButton) {
        closeButton.addEventListener('click', function (event) {

            event.stopPropagation();

            catalogMenu.classList.remove('show');

        });
    }

    // Закрытие каталога при открытии поиска
    if (searchButton) {

        searchButton.addEventListener('click', function () {

            catalogMenu.classList.remove('show');

        });

    }


    // Закрытие при клике вне меню
    document.addEventListener('click', function (event) {

        if (
            catalogMenu.classList.contains('show') &&
            !catalogMenu.contains(event.target) &&
            !catalogButton.contains(event.target)
        ) {
            catalogMenu.classList.remove('show');
        }

    });

});
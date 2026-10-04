document.addEventListener('DOMContentLoaded', () => {

    const filterToggle = document.querySelector('.filter-toggle');
    const filtersWrapper = document.querySelector('.sidebar-area-wrapper');
    const sidebar = document.querySelector('.sidebar-area-wrapper._filters');

    const backBtn = document.querySelector('.filter-wrapper__back');
    const closeBtn = document.querySelector('.filter-wrapper__close');
    const applyBtn = document.querySelector('#cwc-apply-filters');

    if (!filterToggle || !filtersWrapper || !sidebar) return;


    /* ===============================
       ТЕКСТ КНОПКИ
    =============================== */

    function updateFilterToggleText() {

        // Мобильная версия
        if (window.innerWidth <= 768) {
            filterToggle.textContent = 'Показать фильтры';
            return;
        }

        // Десктоп
        filterToggle.textContent = filtersWrapper.classList.contains('opened')
            ? 'Скрыть фильтры'
            : 'Показать фильтры';
    }


    /* ===============================
       BODY FIXED
    =============================== */

    function updateBodyFixed() {

        if (
            window.innerWidth <= 768 &&
            sidebar.classList.contains('opened')
        ) {
            document.body.classList.add('fixed');
        } else {
            document.body.classList.remove('fixed');
        }
    }


    /* ===============================
       ОТКРЫТИЕ / ЗАКРЫТИЕ ФИЛЬТРОВ
    =============================== */

    filterToggle.addEventListener('click', () => {

        if (window.innerWidth <= 768) {

            sidebar.classList.toggle('opened');

        } else {

            filtersWrapper.classList.toggle('opened');

        }

        updateFilterToggleText();
        updateBodyFixed();
    });


    /* ===============================
       BACK
    =============================== */

    if (backBtn) {
        backBtn.addEventListener('click', () => {

            if (window.innerWidth <= 768) {
                sidebar.classList.remove('opened');
                updateFilterToggleText();
                updateBodyFixed();
            }

        });
    }


    /* ===============================
       CLOSE
    =============================== */

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {

            if (window.innerWidth <= 768) {
                sidebar.classList.remove('opened');
                updateFilterToggleText();
                updateBodyFixed();
            }

        });
    }


    /* ===============================
       APPLY
    =============================== */

    if (applyBtn) {
        applyBtn.addEventListener('click', () => {

            if (window.innerWidth <= 768) {
                sidebar.classList.remove('opened');
                updateFilterToggleText();
                updateBodyFixed();
            }

        });
    }


    /* ===============================
       ESC
    =============================== */

    document.addEventListener('keydown', (e) => {

        if (e.key === 'Escape' && window.innerWidth <= 768) {
            sidebar.classList.remove('opened');
            updateFilterToggleText();
            updateBodyFixed();
        }

    });


    /* ===============================
       ИНИЦИАЛИЗАЦИЯ
    =============================== */

    function updateMobileFilters() {

        if (window.innerWidth <= 768) {
            sidebar.classList.remove('opened');
        }

        updateFilterToggleText();
        updateBodyFixed();
    }

    updateMobileFilters();


    /* ===============================
       RESIZE
    =============================== */

    window.addEventListener('resize', updateMobileFilters);

});

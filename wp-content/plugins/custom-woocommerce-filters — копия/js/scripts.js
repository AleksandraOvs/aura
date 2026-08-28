document.addEventListener('DOMContentLoaded', () => {

    const filtersHead = document.querySelector('.filters-head');
    const filtersWrapper = document.querySelector('.filters-wrapper');

    if (!filtersHead || !filtersWrapper) return;

    filtersHead.addEventListener('click', function () {
        filtersWrapper.classList.toggle('opened');
    });

    // document.addEventListener('click', function (e) {

    //     const title = e.target.closest('.filter-item__title');
    //     if (!title) return;

    //     const filter = title.closest('.filter');
    //     if (!filter) return;

    //     const content = filter.querySelector('.filter-item__content');

    //     title.classList.toggle('active');
    //     content.classList.toggle('opened');
    // });


    /* ===============================
       КНОПКА ОТКРЫТИЯ ФИЛЬТРА НА <992PX
    =============================== */

    const button = document.querySelector('button.toggle-filter');
    const sidebar = document.querySelector('.sidebar-area-wrapper._filters');
    const closeBtn = document.querySelector('.close-filters');
    const applyBtn = document.querySelector('#cwc-apply-filters');

    if (!button || !sidebar) return;

    // Открытие / переключение
    button.addEventListener('click', () => {
        if (window.innerWidth <= 992) {
            sidebar.classList.toggle('show');
        }
    });

    // Закрытие по кнопке
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            sidebar.classList.remove('show');
        });
    }

    // Закрытие по клику вне сайдбара
    document.addEventListener('click', (e) => {
        if (
            window.innerWidth <= 992 &&
            sidebar.classList.contains('show') &&
            !sidebar.contains(e.target) &&
            !button.contains(e.target)
        ) {
            sidebar.classList.remove('show');
        }
    });

    // Закрытие по Esc
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            sidebar.classList.remove('show');
        }
    });

    // Закрытие после применения фильтров на <576px
    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            if (window.innerWidth < 576) {
                sidebar.classList.remove('show');
            }
        });
    }
});
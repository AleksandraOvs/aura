(function ($) {

    let state = {
        page: 1,
        loading: false,
        finished: false,
        filters: {}
    };

    const wrapper = $('.sidebar-area-wrapper');
    const products = $('.taxonomy-content .products');

    function collectFilters() {

        let filters = {
            // action: 'cwc_get_products',
            action: 'cwc_filter_products',
            page: state.page,
            current_cat_id: wrapper.data('current-cat')
        };

        /* ATTRIBUTES */
        wrapper.find('.sidebar-list').each(function () {

            let taxonomy = $(this).data('taxonomy');
            let values = [];

            $(this).find('a.active').each(function () {
                values.push($(this).data('slug'));
            });

            if (values.length) {
                filters['filter_' + taxonomy] = values;
            }
        });

        /* PRICE */
        filters.min_price = $('#min_price').val();
        filters.max_price = $('#max_price').val();

        /* SORT */
        filters.orderby = $('select.orderby').val();

        return filters;
    }

    function initPriceSlider(context) {

        const root = context || document;

        $(root).find('#price-slider').each(function () {

            let slider = $(this);

            if (slider.hasClass('ui-slider')) return; // уже инициализирован

            let wrapper = slider.closest('.sidebar-area-wrapper');

            let minInput = wrapper.find('#min_price');
            let maxInput = wrapper.find('#max_price');

            let min = parseInt(slider.data('min'), 10);
            let max = parseInt(slider.data('max'), 10);

            slider.slider({
                range: true,
                min: min,
                max: max,
                values: [
                    parseInt(minInput.val(), 10),
                    parseInt(maxInput.val(), 10)
                ],
                slide: function (event, ui) {
                    minInput.val(ui.values[0]);
                    maxInput.val(ui.values[1]);
                },
                change: function () {
                    updateProducts(wrapper);
                }
            });
        });
    }

    function renderProducts(html, append = false) {

        if (append) {
            products.append(html);
        } else {
            products.html(html);
        }
    }

    function initSidebarLists(context) {

        const root = context || document;

        $(root).find('ul.sidebar-list').each(function () {

            const $list = $(this);

            if ($list.data('collapse-init')) return;

            const $items = $list.children('li');

            if ($items.length <= 9) return;

            $list.data('collapse-init', true);

            let opened = false;

            // Скрываем все элементы после 9-го
            $items.slice(9).hide();

            const $button = $('<button type="button" class="sidebar-list-more">Ещё</button>');
            const $buttonItem = $('<li class="sidebar-list-more-item"></li>').append($button);

            $list.append($buttonItem);

            $button.on('click', function (e) {

                e.preventDefault();

                opened = !opened;

                if (opened) {
                    $items.show();
                    $(this).text('Свернуть');
                } else {
                    $items.slice(9).hide();
                    $(this).text('Ещё');
                }

            });

        });

    }


    function updateProducts(append = false) {

        if (state.loading) return;
        state.loading = true;

        const data = collectFilters();

        data.page = state.page; // 🔥 важно

        console.log(data);

        $.ajax({
            url: cwc_ajax_object.ajax_url,
            type: 'POST',
            data: data,

            success: function (res) {

                if (!res.success) return;

                if (append) {
                    products.append(res.data.html);
                } else {
                    products.html(res.data.html);
                }

                // Обновляем количество найденных товаров
                //wrapper.find('.cwc-found-count span').text(res.data.found_posts);

                console.log(res);
                wrapper.find('.cwc-found-count')
                    .show()
                    .find('span')
                    .text(res.data.found_posts);

                if (res.data.has_more) {
                    $('#load-more-products').show();
                } else {
                    $('#load-more-products').hide();
                }

                initPriceSlider(document);
            },

            complete: function () {
                state.loading = false;
            }
        });
    }


    /* =========================
     * FILTER EVENTS
     * ========================= */
    $(document).on('click', '.sidebar-list a', function (e) {
        e.preventDefault();

        $(this).toggleClass('active');

        state.page = 1; // reset
        updateProducts(false);
    });

    $(document).on('change', '#min_price, #max_price', function () {
        state.page = 1;
        updateProducts(false);
    });

    $(document).on('change', 'select.orderby', function () {
        state.page = 1;
        updateProducts(false);
    });

    /* =========================
     * LOAD MORE
     * ========================= */
    $(document).on('click', '#load-more-products', function (e) {
        e.preventDefault();

        if (state.finished) return;

        state.page++;
        updateProducts(true);
    });
    $(function () {

        initSidebarLists();

        // Не запускаем AJAX-фильтр на странице поиска
        if ($('body').hasClass('search')) {
            return;
        }

        // первая загрузка товаров
        state.page = 1;
        updateProducts(false);
    });
})(jQuery);


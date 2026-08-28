(function ($) {
    function initShowMoreFilters(context) {

        const root = context || document;

        $(root).find('.filter-item__title')
            .off('click.showmore')
            .on('click.showmore', function () {

                const $title = $(this);
                const $filter = $title.closest('.filter');
                const $content = $filter.find('.filter-item__content');

                $title.toggleClass('active');
                $content.toggleClass('opened');
            });
    }

    $(function () {
        cwcIsInit = false;
        initShowMoreFilters(document);
    });
    function isMobile() {
        return window.innerWidth < 768;
    }

    let cwcIsInit = true;
    let cwcIsUpdating = false;
    let cwcTimer;

    function debounceUpdate(wrapper) {

        window.cwcCurrentPage = 1; // 🔥 сброс страницы

        clearTimeout(cwcTimer);

        cwcTimer = setTimeout(() => {
            updateProducts(wrapper);
        }, 300);

        console.log('DEBOUNCE');
    }

    function getProductsContainer() {
        return $('.products');
    }

    function updateProducts(wrapper) {

        if (cwcIsUpdating) return;
        cwcIsUpdating = true;

        let filters = {
            action: 'cwc_filter_products',
            page: window.cwcCurrentPage || 1
        };


        // страховка от вечного lock
        setTimeout(() => {
            cwcIsUpdating = false;
        }, 3000);

        console.log('UPDATE PRODUCTS');

        /* -------------------
         * ATTRIBUTES
         * ------------------- */
        $(wrapper).find('.sidebar-list').each(function () {

            let taxonomy = $(this).data('taxonomy');
            let values = [];

            $(this).find('a.active').each(function () {
                values.push($(this).data('slug'));
            });

            if (!values.length) return;

            if (taxonomy === 'instock_filter') {
                filters.instock = true;
            } else {
                filters['filter_' + taxonomy] = values;
            }
        });

        /* -------------------
         * PRICE
         * ------------------- */
        let minPriceInput = $(wrapper).find('#min_price');
        let maxPriceInput = $(wrapper).find('#max_price');
        let priceSlider = $(wrapper).find('#price-slider');

        if (priceSlider.length) {
            filters.min_price = parseInt(minPriceInput.val(), 10);
            filters.max_price = parseInt(maxPriceInput.val(), 10);
        }

        /* -------------------
         * NUMERIC
         * ------------------- */
        $(wrapper).find('.range-inputs').each(function () {

            let $minInput = $(this).find('input[name$="_min"]');
            let $maxInput = $(this).find('input[name$="_max"]');

            if (!$minInput.length || !$maxInput.length) return;

            let minVal = parseFloat($minInput.val());
            let maxVal = parseFloat($maxInput.val());

            let minDef = parseFloat($minInput.attr('min'));
            let maxDef = parseFloat($maxInput.attr('max'));

            if (minVal === minDef && maxVal === maxDef) return;

            filters[$minInput.attr('name')] = minVal;
            filters[$maxInput.attr('name')] = maxVal;
        });

        /* -------------------
         * SORT
         * ------------------- */
        let orderby = $('select.orderby').val();
        if (orderby) {
            filters.orderby = orderby;
        }

        /* -------------------
         * CATEGORY
         * ------------------- */
        let currentCat = $(wrapper).data('current-cat');
        if (currentCat) {
            filters.current_cat_id = currentCat;
        }

        console.log('FILTERS →', filters);

        let $products = getProductsContainer();

        $.ajax({
            url: cwc_ajax_object.ajax_url,
            type: 'POST',
            data: filters,

            beforeSend: function () {
                $products.fadeTo(200, 0.5);
            },

            success: function (response) {

                if (response.success) {

                    $products
                        .html(response.data.html)
                        .fadeTo(200, 1);

                    // 🔥 ПАГИНАЦИЯ
                    // if ($('#pagination').length) {
                    //     $('#pagination').html(response.data.pagination);
                    // } else {
                    //     $products.after('<div id="pagination">' + response.data.pagination + '</div>');
                    // }

                    initShowMoreFilters(document);
                }
            },

            complete: function () {

                cwcIsUpdating = false;

                if (isMobile()) {
                    $('.woocommerce-layout__sidebar').removeClass('show');
                    $('.toggle-filter').removeClass('active');
                }
            }
        });
    }

    /* -------------------
     * CLICK FILTERS (СТАБИЛЬНО)
     * ------------------- */
    $(document).on('click', '.sidebar-list a', function (e) {

        e.preventDefault();

        const $item = $(this);

        $item.toggleClass('active');

        debounceUpdate($item.closest('.sidebar-area-wrapper'));
    });
    /* -------------------
     * PRICE
     * ------------------- */
    $(document).on('change', '#min_price, #max_price', function () {
        debounceUpdate($(this).closest('.sidebar-area-wrapper'));
    });

    $(document).on('change', '.range-inputs input', function () {
        debounceUpdate($(this).closest('.sidebar-area-wrapper'));
    });

    /* -------------------
     * SLIDER
     * ------------------- */
    $('.sidebar-area-wrapper').each(function () {

        let wrapper = $(this);
        let slider = wrapper.find('#price-slider');
        if (!slider.length) return;

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

    /* -------------------
     * RESET
     * ------------------- */
    $(document).on('click', '#cwc-reset-filters', function (e) {

        e.preventDefault();

        let wrapper = $(this).closest('.sidebar-area-wrapper');

        wrapper.find('.filter-item').removeClass('active');

        let slider = wrapper.find('#price-slider');

        if (slider.length) {
            slider.slider('values', [
                slider.data('min'),
                slider.data('max')
            ]);

            wrapper.find('#min_price').val(slider.data('min'));
            wrapper.find('#max_price').val(slider.data('max'));
        }

        wrapper.find('.range-inputs').each(function () {
            let $min = $(this).find('input[name$="_min"]');
            let $max = $(this).find('input[name$="_max"]');

            $min.val($min.attr('min'));
            $max.val($max.attr('max'));
        });

        updateProducts(wrapper);

        if (isMobile()) {
            $('.woocommerce-layout__sidebar').removeClass('show');
            $('.toggle-filter').removeClass('active');
        }
    });

    /* -------------------
     * APPLY
     * ------------------- */
    $(document).on('click', '#cwc-apply-filters', function (e) {

        e.preventDefault();

        let wrapper = $(this).closest('.sidebar-area-wrapper');

        updateProducts(wrapper);

        $('.woocommerce-layout__sidebar').removeClass('show');
        $('.toggle-filter').removeClass('active');
    });

    /* -------------------
     * SORT
     * ------------------- */
    $(document).on('change', 'select.orderby', function (e) {

        // if (cwcIsInit) return;

        e.preventDefault();

        updateProducts($('.sidebar-area-wrapper').first());
    });

    /* -------------------
     * INIT
     * ------------------- */
    $(function () {
        cwcIsInit = false;
        initShowMoreFilters(document);
    });

    /* -------------------
 * PAGINATION (NEW)
 * ------------------- */
    $(document).on('click', '.page-numbers', function (e) {

        e.preventDefault();

        let page = 1;

        if ($(this).hasClass('next')) {
            page = (window.cwcCurrentPage || 1) + 1;
        } else if ($(this).hasClass('prev')) {
            page = (window.cwcCurrentPage || 1) - 1;
        } else {
            page = parseInt($(this).text());
        }

        window.cwcCurrentPage = page;

        updateProducts($('.sidebar-area-wrapper').first());
    });

})(jQuery);


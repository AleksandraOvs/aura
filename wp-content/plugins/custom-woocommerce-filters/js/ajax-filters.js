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

    function isMobile() {
        return window.innerWidth < 768;
    }

    let cwcIsInit = true;
    let cwcIsUpdating = false;
    let cwcTimer;


    /* ---------------------------------------------------
     * СБОР ТЕКУЩИХ ФИЛЬТРОВ
     * --------------------------------------------------- */

    function getCurrentFilters(wrapper) {

        let filters = {
            action: 'cwc_filter_products',
            page: window.cwcCurrentPage || 1
        };


        /* -------------------------
         * ATTRIBUTES
         * ------------------------- */

        $(wrapper).find('.sidebar-list').each(function () {

            let taxonomy = $(this).data('taxonomy');
            let values = [];

            $(this).find('a.active').each(function () {
                values.push($(this).data('slug'));
            });

            if (!values.length) {
                return;
            }

            if (taxonomy === 'instock_filter') {

                filters.instock = true;

            } else {

                filters['filter_' + taxonomy] = values;
            }
        });


        /* -------------------------
         * PRICE
         * ------------------------- */

        let minPriceInput = $(wrapper).find('#min_price');
        let maxPriceInput = $(wrapper).find('#max_price');
        let priceSlider = $(wrapper).find('#price-slider');

        if (priceSlider.length) {

            filters.min_price =
                parseInt(minPriceInput.val(), 10);

            filters.max_price =
                parseInt(maxPriceInput.val(), 10);
        }


        /* -------------------------
         * NUMERIC
         * ------------------------- */

        $(wrapper).find('.range-inputs').each(function () {

            let $minInput =
                $(this).find('input[name$="_min"]');

            let $maxInput =
                $(this).find('input[name$="_max"]');

            if (!$minInput.length || !$maxInput.length) {
                return;
            }

            let minVal =
                parseFloat($minInput.val());

            let maxVal =
                parseFloat($maxInput.val());

            let minDef =
                parseFloat($minInput.attr('min'));

            let maxDef =
                parseFloat($maxInput.attr('max'));

            if (
                minVal === minDef &&
                maxVal === maxDef
            ) {
                return;
            }

            filters[$minInput.attr('name')] = minVal;
            filters[$maxInput.attr('name')] = maxVal;
        });


        /* -------------------------
         * SORT
         * ------------------------- */

        let orderby = $('select.orderby').val();

        if (orderby) {
            filters.orderby = orderby;
        }


        /* -------------------------
         * CATEGORY
         * ------------------------- */

        let currentCat =
            $(wrapper).data('current-cat');

        if (currentCat) {
            filters.current_cat_id = currentCat;
        }


        return filters;
    }


    /*
     * Делаем функцию доступной
     * для products-load-more.js
     */

    window.cwcGetCurrentFilters = getCurrentFilters;


    /* ---------------------------------------------------
     * PRODUCTS CONTAINER
     * --------------------------------------------------- */

    function getProductsContainer() {
        return $('.js-products-list');
    }


    /* ---------------------------------------------------
     * DEBOUNCE
     * --------------------------------------------------- */

    function debounceUpdate(wrapper) {

        window.cwcCurrentPage = 1;

        clearTimeout(cwcTimer);

        cwcTimer = setTimeout(function () {
            updateProducts(wrapper);
        }, 300);

        console.log('DEBOUNCE');
    }


    /* ---------------------------------------------------
     * UPDATE PRODUCTS
     * --------------------------------------------------- */

    function updateProducts(wrapper) {

        if (cwcIsUpdating) {
            return;
        }

        cwcIsUpdating = true;

        /*
         * Любое новое применение фильтра,
         * сортировки или сброса начинается
         * снова с первой страницы.
         */

        window.cwcCurrentPage = 1;

        const filters =
            getCurrentFilters(wrapper);

        console.log('FILTERS →', filters);

        const $products =
            getProductsContainer();


        $.ajax({

            url: cwc_ajax_object.ajax_url,

            type: 'POST',

            data: filters,


            beforeSend: function () {

                $products.fadeTo(200, 0.5);
            },


            success: function (response) {

                console.log(
                    'FILTER RESULT →',
                    response
                );


                if (!response.success) {
                    return;
                }


                /*
                 * Заменяем первые 18 товаров
                 */

                $products
                    .html(response.data.html)
                    .fadeTo(200, 1);


                initShowMoreFilters(document);


                /*
                 * Сбрасываем состояние infinite scroll
                 */

                const productsList =
                    document.querySelector(
                        '.js-products-list'
                    );


                if (productsList) {

                    productsList.dataset.page = '1';

                    productsList.dataset.hasMore =
                        response.data.has_more
                            ? '1'
                            : '0';
                }


                /*
                 * Сообщаем другим скриптам,
                 * что товары обновились.
                 */

                $(document).trigger(
                    'cwc:products-updated',
                    [response.data]
                );
            },


            error: function (xhr, status, error) {

                console.error(
                    'CWC FILTER ERROR:',
                    status,
                    error
                );
            },


            complete: function () {

                cwcIsUpdating = false;


                if (isMobile()) {

                    $('.woocommerce-layout__sidebar')
                        .removeClass('show');

                    $('.toggle-filter')
                        .removeClass('active');
                }
            }

        });
    }


    /* ---------------------------------------------------
     * CLICK FILTERS
     * --------------------------------------------------- */

    $(document).on(
        'click',
        '.sidebar-list a',
        function (e) {

            e.preventDefault();

            const $item = $(this);

            $item.toggleClass('active');

            debounceUpdate(
                $item.closest(
                    '.sidebar-area-wrapper'
                )
            );
        }
    );


    /* ---------------------------------------------------
     * PRICE
     * --------------------------------------------------- */

    $(document).on(
        'change',
        '#min_price, #max_price',
        function () {

            debounceUpdate(
                $(this).closest(
                    '.sidebar-area-wrapper'
                )
            );
        }
    );


    /* ---------------------------------------------------
     * NUMERIC RANGE
     * --------------------------------------------------- */

    $(document).on(
        'change',
        '.range-inputs input',
        function () {

            debounceUpdate(
                $(this).closest(
                    '.sidebar-area-wrapper'
                )
            );
        }
    );


    /* ---------------------------------------------------
     * PRICE SLIDER
     * --------------------------------------------------- */

    $('.sidebar-area-wrapper').each(function () {

        const wrapper = $(this);

        const slider =
            wrapper.find('#price-slider');

        if (!slider.length) {
            return;
        }

        const minInput =
            wrapper.find('#min_price');

        const maxInput =
            wrapper.find('#max_price');


        const min =
            parseInt(
                slider.data('min'),
                10
            );

        const max =
            parseInt(
                slider.data('max'),
                10
            );


        slider.slider({

            range: true,

            min: min,

            max: max,

            values: [

                parseInt(
                    minInput.val(),
                    10
                ),

                parseInt(
                    maxInput.val(),
                    10
                )

            ],


            slide: function (event, ui) {

                minInput.val(
                    ui.values[0]
                );

                maxInput.val(
                    ui.values[1]
                );
            },


            change: function () {

                debounceUpdate(wrapper);
            }

        });
    });


    /* ---------------------------------------------------
     * RESET
     * --------------------------------------------------- */

    $(document).on(
        'click',
        '#cwc-reset-filters',
        function (e) {

            e.preventDefault();

            const wrapper =
                $(this).closest(
                    '.sidebar-area-wrapper'
                );


            /*
             * Сбрасываем активные пункты
             */

            wrapper
                .find('.sidebar-list a.active')
                .removeClass('active');


            /*
             * PRICE
             */

            const slider =
                wrapper.find('#price-slider');


            if (slider.length) {

                slider.slider(
                    'values',
                    [
                        slider.data('min'),
                        slider.data('max')
                    ]
                );


                wrapper
                    .find('#min_price')
                    .val(
                        slider.data('min')
                    );


                wrapper
                    .find('#max_price')
                    .val(
                        slider.data('max')
                    );
            }


            /*
             * NUMERIC
             */

            wrapper
                .find('.range-inputs')
                .each(function () {

                    const $min =
                        $(this)
                            .find(
                                'input[name$="_min"]'
                            );

                    const $max =
                        $(this)
                            .find(
                                'input[name$="_max"]'
                            );


                    $min.val(
                        $min.attr('min')
                    );

                    $max.val(
                        $max.attr('max')
                    );
                });


            /*
             * Загружаем первые 18
             */

            updateProducts(wrapper);


            if (isMobile()) {

                $('.woocommerce-layout__sidebar')
                    .removeClass('show');

                $('.toggle-filter')
                    .removeClass('active');
            }

        }
    );


    /* ---------------------------------------------------
     * APPLY
     * --------------------------------------------------- */

    $(document).on(
        'click',
        '#cwc-apply-filters',
        function (e) {

            e.preventDefault();

            const wrapper =
                $(this).closest(
                    '.sidebar-area-wrapper'
                );


            updateProducts(wrapper);


            $('.woocommerce-layout__sidebar')
                .removeClass('show');

            $('.toggle-filter')
                .removeClass('active');
        }
    );


    /* ---------------------------------------------------
     * SORT
     * --------------------------------------------------- */

    $(document).on(
        'change',
        'select.orderby',
        function (e) {

            e.preventDefault();

            updateProducts(
                $('.sidebar-area-wrapper').first()
            );
        }
    );


    /* ---------------------------------------------------
     * INIT
     * --------------------------------------------------- */

    $(function () {

        cwcIsInit = false;

        initShowMoreFilters(document);
    });


})(jQuery);

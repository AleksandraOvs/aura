jQuery(document).ready(function ($) {

    /*
     * =========================================================
     * Счётчик сравнения
     * =========================================================
     */

    function updateCompareCount(count) {

        var item = $('a[href="/compare"]')
            .closest('._header-links__list__item');

        if (!item.length) {
            return;
        }

        count = parseInt(count, 10) || 0;

        if (count === 0) {
            item.find('.aura-compare-count').remove();
            return;
        }

        var counter = item.find('.aura-compare-count');

        if (!counter.length) {

            item.append(
                '<span class="aura-compare-count">' +
                count +
                '</span>'
            );

        } else {

            counter.text(count);

        }
    }


    /*
     * Начальное значение счётчика
     */

    if (
        typeof auraCompare !== 'undefined' &&
        typeof auraCompare.count !== 'undefined'
    ) {
        updateCompareCount(auraCompare.count);
    }


    /*
     * =========================================================
     * Добавление / удаление товара из сравнения
     * =========================================================
     */

    $('body').on(
        'click',
        '.aura-compare-btn',
        function (e) {

            e.preventDefault();

            const btn = $(this);
            const productId = btn.data('product-id');

            if (!productId || btn.hasClass('loading')) {
                return;
            }

            const isAdded = btn.hasClass('added');

            const action = isAdded
                ? 'aura_compare_remove'
                : 'aura_compare_add';


            $.ajax({

                url: auraCompare.ajax_url,

                type: 'POST',

                data: {

                    action: action,

                    nonce: auraCompare.nonce,

                    product_id: productId

                },


                beforeSend: function () {

                    btn.addClass('loading');

                },


                success: function (response) {

                    if (!response.success) {

                        if (
                            response.data &&
                            response.data.message
                        ) {

                            alert(response.data.message);

                        }

                        return;
                    }


                    /*
                     * Добавление
                     */

                    if (action === 'aura_compare_add') {

                        btn.addClass('added');

                    }


                    /*
                     * Удаление
                     */

                    else {

                        btn.removeClass('added');

                        btn.find('.aura-compare-btn__text')
                            .text('Добавить в сравнение');

                    }


                    /*
                     * Обновляем счётчик
                     */

                    updateCompareCount(
                        response.data.count
                    );

                },


                error: function () {

                    alert('Произошла ошибка. Попробуйте ещё раз.');

                },


                complete: function () {

                    btn.removeClass('loading');

                }

            });

        }
    );


    /*
     * =========================================================
     * Удаление товара со страницы сравнения
     * =========================================================
     */

    $('body').on(
        'click',
        '.aura-compare__remove',
        function (e) {

            e.preventDefault();

            const btn = $(this);
            const productId = btn.data('product-id');

            if (!productId || btn.hasClass('loading')) {
                return;
            }


            $.ajax({

                url: auraCompare.ajax_url,

                type: 'POST',

                data: {

                    action: 'aura_compare_remove',

                    nonce: auraCompare.nonce,

                    product_id: productId

                },


                beforeSend: function () {

                    btn.addClass('loading');

                },


                success: function (response) {

                    if (!response.success) {

                        if (
                            response.data &&
                            response.data.message
                        ) {

                            alert(response.data.message);

                        }

                        return;
                    }


                    updateCompareCount(
                        response.data.count
                    );


                    /*
                     * Пока перезагружаем страницу
                     */

                    window.location.reload();

                },


                error: function () {

                    alert('Произошла ошибка. Попробуйте ещё раз.');

                },


                complete: function () {

                    btn.removeClass('loading');

                }

            });

        }
    );


    /*
     * =========================================================
     * Удаление всех товаров
     * =========================================================
     */

    $('body').on(
        'click',
        '.aura-compare__clear',
        function (e) {

            e.preventDefault();

            const btn = $(this);

            if (btn.hasClass('loading')) {
                return;
            }


            $.ajax({

                url: auraCompare.ajax_url,

                type: 'POST',

                data: {

                    action: 'aura_compare_clear',

                    nonce: auraCompare.nonce

                },


                beforeSend: function () {

                    btn.addClass('loading');

                },


                success: function (response) {

                    if (!response.success) {

                        if (
                            response.data &&
                            response.data.message
                        ) {

                            alert(response.data.message);

                        }

                        return;
                    }


                    updateCompareCount(0);


                    /*
                     * Показываем пустое состояние
                     */

                    window.location.reload();

                },


                error: function () {

                    alert('Произошла ошибка. Попробуйте ещё раз.');

                },


                complete: function () {

                    btn.removeClass('loading');

                }

            });

        }
    );


    /*
     * =========================================================
     * Фильтр различающихся характеристик
     * =========================================================
     */

    function applyDifferentFilter() {

        const checkbox = $('.aura-compare__different');

        if (!checkbox.length) {
            return;
        }


        if (checkbox.is(':checked')) {

            $('.aura-compare__attribute-value.is-same')
                .hide();

            $('.aura-compare__attribute-value.is-different')
                .show();

        } else {

            $('.aura-compare__attribute-value')
                .show();

        }

    }


    $('body').on(
        'change',
        '.aura-compare__different',
        function () {

            applyDifferentFilter();

        }
    );


    /*
     * =========================================================
     * Swiper сравнения
     * =========================================================
     */

    const compareSliderElement = document.querySelector(
        '.aura-compare__products'
    );


    /*
     * Если страницы сравнения нет —
     * ничего дальше не делаем.
     */

    if (!compareSliderElement) {
        return;
    }


    /*
     * Сохраняем исходные слайды.
     *
     * Они понадобятся для возврата
     * к состоянию "Все товары".
     */

    const originalSlides = Array.from(
        compareSliderElement.querySelectorAll(
            '.swiper-slide'
        )
    );


    const slidesCount = originalSlides.length;


    /*
     * Переменная Swiper
     */

    let compareSlider = null;


    /*
     * Создаём Swiper
     */

    compareSlider = new Swiper(
        compareSliderElement,
        {

            slidesPerView: Math.min(
                slidesCount,
                1.5
            ),

            spaceBetween: 20,

            watchOverflow: true,

            navigation: {

                nextEl: '.aura-compare__next',

                prevEl: '.aura-compare__prev',

            },


            breakpoints: {

                1400: {

                    slidesPerView: Math.min(
                        slidesCount,
                        5
                    ),

                },

                1024: {

                    slidesPerView: 3.2,

                },

                768: {

                    slidesPerView: 2.4,

                },

                480: {

                    slidesPerView: 1.4,

                }

            }

        }
    );


    /*
     * =========================================================
     * Фильтрация товаров по категории
     * =========================================================
     */

    $('body').on(
        'click',
        '.aura-compare__category',
        function () {

            const button = $(this);

            const categoryId = String(
                button.data('category')
            );


            /*
             * Активная категория
             */

            $('.aura-compare__category')
                .removeClass('is-active');

            button.addClass('is-active');


            /*
             * -------------------------------------------------
             * Все товары
             * -------------------------------------------------
             */

            if (categoryId === 'all') {

                compareSlider.removeAllSlides();

                compareSlider.appendSlide(
                    originalSlides
                );

                compareSlider.slideTo(0);

                compareSlider.update();

                applyDifferentFilter();

                return;
            }


            /*
             * -------------------------------------------------
             * Фильтруем товары
             * -------------------------------------------------
             */

            const filteredSlides = originalSlides.filter(
                function (slide) {

                    const categories = String(
                        slide.dataset.categories || ''
                    )
                        .split(',')
                        .map(function (id) {
                            return id.trim();
                        })
                        .filter(Boolean);


                    return categories.includes(
                        categoryId
                    );

                }
            );


            /*
             * Заменяем содержимое Swiper
             */

            compareSlider.removeAllSlides();

            compareSlider.appendSlide(
                filteredSlides
            );


            /*
             * Возвращаемся к первому товару
             */

            compareSlider.slideTo(0);


            /*
             * Пересчитываем Swiper
             */

            compareSlider.update();


            /*
             * Повторно применяем фильтр
             * различающихся характеристик
             */

            applyDifferentFilter();

        }
    );


});
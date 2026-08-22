jQuery(document).ready(function ($) {


    /*
     * Обновление счётчика сравнения
     */
    function updateCompareCount(count) {

        $('.aura-compare-count').text(count);

    }


    /*
     * Устанавливаем начальное значение
     */
    updateCompareCount(auraCompare.count);


    /*
     * Добавление / удаление товара
     * из сравнения
     */
    $('body').on('click', '.aura-compare-btn', function (e) {

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

                /*
                 * Ошибка
                 */
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

                    btn
                        .addClass('added');

                    btn.find('.aura-compare-btn__text')
                        .text('В сравнении');

                }


                /*
                 * Удаление
                 */
                else {

                    btn
                        .removeClass('added');

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


            complete: function () {

                btn.removeClass('loading');

            }

        });

    });


    /*
     * Удаление товара
     * со страницы сравнения
     */
    $('body').on('click', '.aura-compare__remove', function (e) {

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


                /*
                 * Обновляем счётчик
                 */
                updateCompareCount(
                    response.data.count
                );


                /*
                 * Пока обновляем страницу.
                 */
                window.location.reload();

            },


            complete: function () {

                btn.removeClass('loading');

            }

        });

    });

    /*
    * Фильтр различающихся характеристик
    */
    $('body').on('change', '.aura-compare__different', function () {

        const checkbox = $(this);

        if (checkbox.is(':checked')) {

            $('.aura-compare__attribute-value.is-same').hide();
            $('.aura-compare__attribute-value.is-different').show();

        } else {

            $('.aura-compare__attribute-value').show();

        }

    });

});

const compareSlider = document.querySelector('.aura-compare__products');

if (compareSlider) {

    const slidesCount = compareSlider.querySelectorAll('.swiper-slide').length;

    new Swiper(compareSlider, {

        slidesPerView: Math.min(slidesCount, 5),

        spaceBetween: 20,

        watchOverflow: true,

        navigation: {
            nextEl: '.aura-compare__next',
            prevEl: '.aura-compare__prev',
        },

        breakpoints: {

            1400: {
                slidesPerView: Math.min(slidesCount, 5),
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

        },

    });

}
jQuery(document).ready(function ($) {

    // =====================================================
    // WISHLIST
    // =====================================================



    // --------------------------------------------------
    // ФУНКЦИЯ ОБНОВЛЕНИЯ СЧЁТЧИКА
    // --------------------------------------------------
    function updateWishlistCounter(count) {

        count = parseInt(count, 10) || 0;

        var item = $('a[href="/wishlist"]').closest('._header-links__list__item');

        if (!item.length) {
            return;
        }

        // Если 0 — полностью удаляем элемент из DOM
        if (count === 0) {
            item.find('.wishlist-counter').remove();
            return;
        }

        // Ищем существующий счётчик
        var counter = item.find('.wishlist-counter');

        // Если счётчика нет — создаём
        if (!counter.length) {

            item.append(
                '<span class="wishlist-counter">' + count + '</span>'
            );

        } else {

            counter.text(count);

        }
    }




    // --------------------------------------------------
    // ПОЛУЧАЕМ КОЛИЧЕСТВО ПРИ ЗАГРУЗКЕ СТРАНИЦЫ
    // --------------------------------------------------
    $.post(wl_ajax.ajax_url, {
        action: 'custom_get_wishlist_count'
    }, function (response) {

        if (response.success) {
            updateWishlistCounter(response.data.count);
        }

    });


    // --------------------------------------------------
    // ДОБАВЛЕНИЕ / УДАЛЕНИЕ ТОВАРА ИЗ ИЗБРАННОГО
    // --------------------------------------------------
    $('body').on('click', '.custom-wishlist-btn', function (e) {

        e.preventDefault();

        var btn = $(this);
        var product_id = btn.data('product_id');


        $.post(wl_ajax.ajax_url, {

            action: 'custom_toggle_wishlist',
            product_id: product_id

        }, function (response) {

            if (!response.success) {
                return;
            }


            // ---------------------------------------------
            // ОБНОВЛЯЕМ КНОПКУ НА КАРТОЧКЕ
            // ---------------------------------------------

            if (response.data.status === 'added') {

                btn.addClass('added');
                btn.find('.wishlist-text').text('В избранном');

            } else {

                btn.removeClass('added');
                btn.find('.wishlist-text').text('Добавить в избранное');


                // Если на странице вишлиста —
                // удаляем карточку

                btn.closest('.wishlist-item').fadeOut(300, function () {
                    $(this).remove();
                });

            }


            // ---------------------------------------------
            // ОБНОВЛЯЕМ СЧЁТЧИК
            // ---------------------------------------------

            var counter = $('._header-links__list__item .wishlist-counter');

            var count = parseInt(counter.text(), 10) || 0;


            if (response.data.status === 'added') {

                count++;

            } else {

                count = Math.max(0, count - 1);

            }


            updateWishlistCounter(count);

        });

    });

});

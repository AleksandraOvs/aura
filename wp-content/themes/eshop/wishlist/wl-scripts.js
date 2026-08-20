jQuery(document).ready(function ($) {

    $('body').on('click', '.custom-wishlist-btn', function (e) {
        e.preventDefault();

        var btn = $(this);
        var product_id = btn.data('product_id');

        $.post(wl_ajax.ajax_url, {
            action: 'custom_toggle_wishlist',
            product_id: product_id
        }, function (response) {
            if (response.success) {

                // обновляем кнопку на карточке
                if (response.data.status === 'added') {
                    btn.addClass('added');
                    btn.find('.wishlist-text').text('В избранном');
                } else {
                    btn.removeClass('added');
                    btn.find('.wishlist-text').text('Добавить в избранное');

                    // если на странице вишлиста — удаляем карточку
                    btn.closest('.wishlist-item').fadeOut(300, function () {
                        $(this).remove();
                    });
                }

                // -----------------------------
                // обновляем счётчик в шапке
                // -----------------------------
                var counter = $('.header-wishlist .wishlist-counter');
                var count = parseInt(counter.text()) || 0;

                if (response.data.status === 'added') {
                    count++;
                } else {
                    count--;
                }

                if (count > 0) {
                    if (counter.length) {
                        counter.text(count);
                    } else {
                        $('.header-wishlist').append('<span class="wishlist-counter">' + count + '</span>');
                    }
                } else {
                    counter.remove();
                }
            }
        });
    });

    // -----------------------------
    // обработчики для кнопок + / - и ручного ввода
    // -----------------------------
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.pro-qty .inc').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                let input = this.parentElement.querySelector('.qty');
                let step = parseFloat(input.dataset.step) || 1;
                let max = parseFloat(input.dataset.max) || 9999;
                let val = parseFloat(input.value) || 1;
                if (val + step <= max) {
                    input.value = val + step;
                    updateTotal(this.closest('.cart-flex__row'));
                }
            });
        });

        document.querySelectorAll('.pro-qty .dec').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                let input = this.parentElement.querySelector('.qty');
                let step = parseFloat(input.dataset.step) || 1;
                let min = parseFloat(input.dataset.min) || 1;
                let val = parseFloat(input.value) || 1;
                if (val - step >= min) {
                    input.value = val - step;
                    updateTotal(this.closest('.cart-flex__row'));
                }
            });
        });

        document.querySelectorAll('.pro-qty .qty').forEach(function (input) {
            input.addEventListener('input', function () {
                updateTotal(this.closest('.cart-flex__row'));
            });
        });

        function updateTotal(row) {
            if (!row) return;
            let priceElem = row.querySelector('.cart-flex__col--price .price');
            let totalElem = row.querySelector('.cart-flex__col--total .price');
            let qtyInput = row.querySelector('.pro-qty .qty');
            if (!priceElem || !totalElem || !qtyInput) return;

            let price = parseFloat(priceElem.dataset.rawPrice || priceElem.textContent.replace(/[^0-9\.,]/g, '').replace(',', '.'));
            let qty = parseFloat(qtyInput.value) || 1;
            let total = price * qty;

            totalElem.textContent = new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(total);
        }
    });

});

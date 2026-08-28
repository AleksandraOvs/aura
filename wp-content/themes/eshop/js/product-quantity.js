let cartUpdateTimer;

document.addEventListener('click', function (e) {

    const button = e.target.closest('.quantity-minus, .quantity-plus');

    if (!button) {
        return;
    }

    const wrapper = button.closest('.product-quantity');

    if (!wrapper) {
        return;
    }

    const input = wrapper.querySelector('.qty');

    if (!input) {
        return;
    }

    const min = parseFloat(input.min) || 1;
    const max = input.max !== '' ? parseFloat(input.max) : Infinity;
    const step = parseFloat(input.step) || 1;

    let value = parseFloat(input.value) || min;

    if (button.classList.contains('quantity-minus')) {
        value -= step;
    }

    if (button.classList.contains('quantity-plus')) {
        value += step;
    }

    value = Math.max(min, Math.min(max, value));

    input.value = value;


    /*
     * Каталог
     *
     * Передаём выбранное количество
     * в кнопку "В корзину"
     */
    const card = input.closest('li.product');

    if (card) {

        const addToCartButton = card.querySelector('.add_to_cart_button');

        if (addToCartButton) {
            addToCartButton.setAttribute('data-quantity', value);
            addToCartButton.dataset.quantity = value;
        }

    }


    /*
     * Автоматическое обновление корзины
     */

    const cartForm = input.closest('.woocommerce-cart-form');

    if (!cartForm) {
        return;
    }

    input.dispatchEvent(new Event('change', {
        bubbles: true
    }));

    clearTimeout(cartUpdateTimer);

    cartUpdateTimer = setTimeout(function () {

        const updateButton = cartForm.querySelector(
            'button[name="update_cart"], input[name="update_cart"]'
        );

        if (updateButton) {
            updateButton.click();
        }

    }, 500);

});
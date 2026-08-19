document.addEventListener('click', function (e) {

    const button = e.target.closest('.quantity-minus, .quantity-plus');

    if (!button) {
        return;
    }

    console.log('QUANTITY BUTTON:', button);

    const wrapper = button.closest('.product-quantity');

    if (!wrapper) {
        return;
    }

    const input = wrapper.querySelector('.qty');

    if (!input) {
        console.log('NO INPUT');
        return;
    }

    console.log('INPUT:', input);
    console.log('CURRENT VALUE:', input.value);

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

    console.log('NEW VALUE:', value);

    input.value = value;

    // /*
    //  * Каталог
    //  */
    // const card = input.closest('li.product');

    // if (card) {

    //     const addToCartButton = card.querySelector('.add_to_cart_button');

    //     if (addToCartButton) {
    //         addToCartButton.setAttribute('data-quantity', value);
    //         addToCartButton.dataset.quantity = value;
    //     }
    // }


    // /*
    //  * Страница товара
    //  */
    // const productContent = input.closest('.product-inner__content');

    // if (productContent) {

    //     const hiddenQuantity = productContent.querySelector(
    //         '.product-buy-quantity'
    //     );

    //     if (hiddenQuantity) {
    //         hiddenQuantity.value = value;
    //     }
    // }


    // // Сообщаем другим обработчикам, что количество изменилось
    // input.dispatchEvent(new Event('change', {
    //     bubbles: true
    // }));

});
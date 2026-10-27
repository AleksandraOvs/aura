document.addEventListener('wpcf7mailsent', function (event) {
    const form = event.target;

    // Скрываем содержимое формы
    const formContent = form.querySelectorAll(
        'p, .wpcf7-form-control-wrap, input, textarea, select, button, .form__footer'
    );

    formContent.forEach(function (element) {
        element.style.display = 'none';
    });

    // Скрываем кнопку отправки и остальные элементы формы
    form.querySelectorAll('.wpcf7-submit, .wpcf7-spinner').forEach(function (element) {
        element.style.display = 'none';
    });

    // Находим стандартный блок сообщения
    const response = form.querySelector('.wpcf7-response-output');

    if (response) {
        response.textContent =
            'Спасибо, запрос отправлен! Мы свяжемся с Вами в ближайшее время.';

        response.style.display = 'block';
        response.style.margin = '1em 0px 0px 0px';
        response.style.padding = '20px';
        response.style.border = '1px solid #d4e8d8';
        response.style.borderRadius = '6px';
        response.style.backgroundColor = '#f0f8f1';
        response.style.color = 'var(--theme-color-black)';
        response.style.textAlign = 'center';
    }
});
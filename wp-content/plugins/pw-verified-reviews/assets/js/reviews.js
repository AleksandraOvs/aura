
document.addEventListener('submit', async function (event) {
    const form = event.target.closest('.pwvr-form');

    if (!form) {
        return;
    }

    event.preventDefault();

    const button = form.querySelector('[type="submit"]');
    const message = form.querySelector('.pwvr-form__message');
    const data = new FormData(form);

    data.append('action', 'pwvr_submit_review');

    button.disabled = true;
    message.textContent = 'Отправляем отзыв…';

    try {
        const response = await fetch(PWVR.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.data?.message || 'Не удалось отправить отзыв.'
            );
        }

        form.innerHTML = '';

        const successMessage = document.createElement('p');
        successMessage.className = 'pwvr-form__success';
        successMessage.textContent = result.data.message;

        form.appendChild(successMessage);

    } catch (error) {
        message.textContent = error.message || 'Ошибка отправки. Попробуйте ещё раз.';
        button.disabled = false;
    }
});

document.querySelectorAll('.pwvr-rating').forEach((rating) => {
    const inputs = [...rating.querySelectorAll('input[type="radio"]')];
    const labels = [...rating.querySelectorAll('label')];

    function updateRating(value) {
        labels.forEach((label, index) => {
            label.classList.toggle('is-active', index < value);
        });
    }

    inputs.forEach((input) => {
        input.addEventListener('change', () => {
            updateRating(Number(input.value));
        });

        input.addEventListener('focus', () => {
            updateRating(Number(input.value));
        });
    });

    rating.addEventListener('mouseleave', () => {
        const checked = inputs.find((input) => input.checked);
        updateRating(checked ? Number(checked.value) : 0);
    });

    labels.forEach((label, index) => {
        label.addEventListener('mouseenter', () => {
            updateRating(index + 1);
        });
    });

    const checked = inputs.find((input) => input.checked);
    updateRating(checked ? Number(checked.value) : 0);
});
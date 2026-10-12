
document.addEventListener('DOMContentLoaded', () => {
    const reviewTexts = document.querySelectorAll(
        '.reviews-slider__slide__text'
    );

    reviewTexts.forEach((container) => {
        const text = container.querySelector('p');
        const button = container.querySelector('.reviews-slider__more');

        if (!text || !button) {
            return;
        }

        // Проверяем, обрезается ли текст
        if (text.scrollHeight <= text.clientHeight) {
            button.classList.add('is-hidden');
            return;
        }

        button.addEventListener('click', () => {
            const isExpanded = text.classList.toggle('is-expanded');

            button.textContent = isExpanded
                ? 'Свернуть'
                : 'Читать полностью';
        });
    });
});

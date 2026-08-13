// ===============================
// Мини-корзина (открытие / закрытие)
// ===============================
const openBtn = document.querySelector('#open-minicart');
const popup = document.querySelector('#minicart-popup');
const body = document.body;

if (openBtn && popup) {
    // открыть мини-корзину
    openBtn.addEventListener('click', e => {
        e.preventDefault();
        popup.classList.add('is-open');
        body.classList.add('_fixed');
    });

    // закрыть по кнопке или клику вне попапа
    document.addEventListener('click', e => {
        if (e.target.closest('#minicart-popup button.close')) {
            popup.classList.remove('is-open');
            body.classList.remove('_fixed');
            return;
        }
        if (!e.target.closest('#minicart-popup') && !e.target.closest('#open-minicart')) {
            popup.classList.remove('is-open');
            body.classList.remove('_fixed');
        }
    });
}
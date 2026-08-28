// document.addEventListener('DOMContentLoaded', function () {

//     if (typeof Swiper === 'undefined') {
//         console.warn('Swiper is not loaded');
//         return;
//     }

//     const sliders = document.querySelectorAll('.js-swiper');
//     if (!sliders.length) return;

//     sliders.forEach((slider) => {

//         const config = {
//             slidesPerView: parseInt(slider.dataset.slides) || 1,
//             spaceBetween: parseInt(slider.dataset.space) || 20,
//             loop: slider.dataset.loop === 'true',
//             speed: parseInt(slider.dataset.speed) || 600,

//             pagination: {
//                 el: slider.querySelector('.swiper-pagination'),
//                 clickable: true,
//             },

//             navigation: {
//                 nextEl: slider.querySelector('.swiper-button-next'),
//                 prevEl: slider.querySelector('.swiper-button-prev'),
//             }
//         };

//         // Если pagination или кнопок нет — не передаём их
//         if (!config.pagination.el) delete config.pagination;
//         if (!config.navigation.nextEl || !config.navigation.prevEl) {
//             delete config.navigation;
//         }

//         new Swiper(slider, config);

//     });

// });
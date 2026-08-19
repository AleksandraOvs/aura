function initProductsSlider(
    sliderSelector,
    paginationSelector,
    prevButtonSelector,
    nextButtonSelector
) {
    const slider = document.querySelector(sliderSelector);

    if (!slider) {
        return;
    }

    return new Swiper(sliderSelector, {

        slidesPerView: 1.4,
        spaceBetween: 24,

        loop: true,

        navigation: {
            prevEl: prevButtonSelector,
            nextEl: nextButtonSelector,
        },

        // pagination: {
        //     el: paginationSelector,
        //     clickable: true,
        // },

        breakpoints: {

            576: {
                slidesPerView: 2.3,
                spaceBetween: 20,
            },

            768: {
                slidesPerView: 4,
            },

            1400: {
                slidesPerView: 5,
            }

        }

    });
}


/**
 * Cross-sells
 * Сопутствующие товары
 */
const crossSellsSlider = initProductsSlider(
    ".cross-sells-products-slider",
    ".cross-sells-products-slider .swiper-button-prev",
    ".cross-sells-products-slider .swiper-button-next"
);


/**
 * Upsells
 * Похожие товары
 */
const relatedSlider = initProductsSlider(
    ".related-products-slider",
    ".related-products-slider .swiper-button-prev",
    ".related-products-slider .swiper-button-next"
);
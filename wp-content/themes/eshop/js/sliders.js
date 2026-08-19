new Swiper(".brands-slider", {
    slidesPerView: 6,
    spaceBetween: 16,

    grid: {
        rows: 2,
        fill: "row"
    },

    pagination: {
        el: ".brands-slider .swiper-pagination",
        clickable: true
    },

    breakpoints: {
        320: {
            slidesPerView: 2,
            grid: {
                rows: 2
            }
        },

        576: {
            slidesPerView: 3,
            grid: {
                rows: 2
            }
        },

        768: {
            slidesPerView: 4,
            grid: {
                rows: 2
            }
        },

        992: {
            slidesPerView: 5,
            grid: {
                rows: 2
            }
        },

        1200: {
            slidesPerView: 6,
            grid: {
                rows: 2
            }
        }
    }
});

const sertificatesSlider = new Swiper(".sertificates-slider", {

    slidesPerView: 1.3,
    spaceBetween: 24,

    loop: true,

    pagination: {
        el: ".sertificates-slider-pagination",
        clickable: true,
    },



    breakpoints: {

        576: {
            slidesPerView: 2.2,
            spaceBetween: 20,
        },

        768: {
            slidesPerView: 4,
            spaceBetween: 20,
        },

        1200: {
            slidesPerView: 5,
            spaceBetween: 24,
        },

    }

});

Fancybox.bind('[data-fancybox="sertificates"]', {
    Toolbar: {
        display: {
            left: [],
            middle: [],
            right: ["close"],
        },
    },
});

const reviewsSlider = new Swiper(".reviews-slider", {

    slidesPerView: 1.2,
    spaceBetween: 24,

    loop: true,

    // pagination: {
    //     el: ".sertificates-slider-pagination",
    //     clickable: true,
    // },

    breakpoints: {

        480: {
            slidesPerView: 1.6,
            spaceBetween: 20,
        },

        768: {
            slidesPerView: 2,
            spaceBetween: 20,
        },
        1200: {
            slidesPerView: 3,
        }

    }

});

Fancybox.bind('[data-fancybox="reviews-photo"]', {
    Toolbar: {
        display: {
            left: [],
            middle: [],
            right: ["close"],
        },
    },
});

const projectSlider = new Swiper(".project-slider", {

    slidesPerView: "auto",
    spaceBetween: 20,

    loop: true,

    navigation: {
        prevEl: ".button-slider__prev",
        nextEl: ".button-slider__next",
    },

    breakpoints: {
        320: {
            slidesPerView: 1.4,
            spaceBetween: 15,
        },

        576: {
            slidesPerView: 2.3,
        },

        1200: {
            slidesPerView: "auto",
        }

    },

});

Fancybox.bind('[data-fancybox="project-photo"]', {
    Toolbar: {
        display: {
            left: [],
            middle: [],
            right: ["close"],
        },
    },
});


// const relatedSlider = new Swiper(".related-products-slider", {

//     slidesPerView: 1.4,
//     spaceBetween: 24,

//     loop: true,

//     pagination: {
//         el: ".related-products__pagination",
//         clickable: true,
//     },

//     breakpoints: {

//         576: {
//             slidesPerView: 2.3,
//             spaceBetween: 20,
//         },

//         768: {
//             slidesPerView: 4,
//         },
//         1400: {
//             slidesPerView: 5,
//         }

//     }

// });
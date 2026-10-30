document.addEventListener('DOMContentLoaded', () => {

    /* ===============================
      WOOCOMMERCE MESSAGE AUTO-HIDE
   =============================== */

    document.addEventListener('click', e => {
        document.querySelectorAll('.woocommerce-message, .woocommerce-error').forEach(msg => {
            if (!msg.contains(e.target)) {
                msg.classList.add('fade-out');
                setTimeout(() => msg.remove(), 700);
            }
        });
    });

    /* ===============================
      SINGLE PRODUCT TABS
   =============================== */

    const tabs = document.querySelectorAll('.product-tabs__btn');
    const panes = document.querySelectorAll('.product-tabs__pane');

    tabs.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            // активная кнопка
            tabs.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // активный контент
            panes.forEach(pane => {
                pane.classList.remove('active');
                if (pane.dataset.tab === tab) {
                    pane.classList.add('active');
                }
            });
        });
    });

    /* ===============================
      SINGLE PRODUCT FAQ
   =============================== */

    document.addEventListener('click', function (e) {

        const button = e.target.closest('.faq-items__question');

        if (!button) {
            return;
        }

        const item = button.closest('.faq-items__item');
        const answer = item.querySelector('.faq-items__answer');

        const isOpen = button.getAttribute('aria-expanded') === 'true';

        button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        item.classList.toggle('active', !isOpen);

        if (isOpen) {
            answer.style.maxHeight = null;
        } else {
            answer.style.maxHeight = answer.scrollHeight + 'px';
        }
    });

});

document.body.addEventListener('added_to_cart', function (e) {
    const button = e.detail?.button;

    if (!button) return;

    button.textContent = 'В корзине';
    button.classList.add('in-cart');
    button.disabled = true;
});

/* ===============================
      SINGLE PRODUCT GALLERY
   =============================== */

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.aura-product-gallery').forEach((gallery) => {
        const mainElement = gallery.querySelector('.aura-product-gallery__main');
        const thumbsElement = gallery.querySelector('.aura-product-gallery__thumbs');

        if (!mainElement || typeof Swiper === 'undefined') {
            return;
        }

        let thumbsSwiper = null;

        if (thumbsElement) {
            thumbsSwiper = new Swiper(thumbsElement, {
                direction: 'horizontal',
                slidesPerView: 4,
                spaceBetween: 10,
                watchSlidesProgress: true,
                breakpoints: {
                    480: {
                        direction: 'vertical',
                        slidesPerView: 5,
                        spaceBetween: 10,
                    },
                    768: {
                        slidesPerView: 4,
                        spaceBetween: 12,
                    },
                },
            });
        }

        new Swiper(mainElement, {
            slidesPerView: 1,
            spaceBetween: 10,
            //autoHeight: true,
            navigation: {
                nextEl: gallery.querySelector('.slider-next'),
                prevEl: gallery.querySelector('.slider-prev'),
            },
            thumbs: thumbsSwiper
                ? {
                    swiper: thumbsSwiper,
                }
                : undefined,
        });
    });
});

/* ===============================
    TOGGLE PASSWORD CHANGE
 =============================== */

document.addEventListener('DOMContentLoaded', () => {
    const passwordChange = document.querySelector('.password-change');
    const toggle = passwordChange?.querySelector('.change-toggle');

    if (!passwordChange || !toggle) return;

    toggle.addEventListener('click', () => {
        passwordChange.classList.toggle('is-open');
    });
});
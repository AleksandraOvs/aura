window.PWQB = window.PWQB || {};

window.PWQB.products = {
    init() {
        document.addEventListener('pwqb:completed', async (event) => {
            const quiz = event.target;

            const result = quiz.querySelector('[data-pwqb-products-result]');
            const productsList = quiz.querySelector('[data-pwqb-products-list]');
            const productsCount = quiz.querySelector('[data-pwqb-products-count]');

            const loader = result.querySelector('.products-loader');
            const productsSlider = result.querySelector('.pwqb-quiz__products-slider');
            const productsTitle = result.querySelector('.pwqb-quiz__products-title');

            loader.hidden = false;
            productsTitle.hidden = true;
            productsSlider.hidden = true;
            const formData = new FormData();

            formData.append('action', 'pwqb_get_products');
            formData.append('nonce', PWQBQuiz.productsNonce);
            formData.append('quiz_id', event.detail.quizId);
            formData.append('attributes', JSON.stringify(event.detail.attributes));

            try {
                const response = await fetch(PWQBQuiz.ajaxUrl, {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (!data.success) {
                    console.error('PWQB: Ошибка поиска товаров.', data.data);
                    return;
                }

                productsList.innerHTML = data.data.html;
                productsCount.textContent = data.data.count;
                const productsLabel = quiz.querySelector('[data-pwqb-products-label]');

                productsLabel.textContent = PWQB.products.getProductLabel(
                    Number(data.data.count)
                );

                quiz.dataset.productIds = JSON.stringify(data.data.product_ids);
                quiz.dataset.attributes = JSON.stringify(event.detail.attributes);
                quiz.dataset.answers = JSON.stringify(event.detail.answers);

                loader.hidden = true;
                productsTitle.hidden = false;
                productsSlider.hidden = data.data.count === 0;

                if (data.data.count > 0) {
                    new Swiper(result.querySelector('.pwqb-quiz__products-slider'), {
                        slidesPerView: 1,
                        spaceBetween: 20,
                        navigation: {
                            nextEl: result.querySelector('.pwqb-quiz__products-next'),
                            prevEl: result.querySelector('.pwqb-quiz__products-prev'),
                        },
                        breakpoints: {
                            481: {
                                slidesPerView: 2,
                            },
                            576: {
                                slidesPerView: 3,
                            },
                            992: {
                                slidesPerView: 2,
                            },
                            1400: {
                                slidesPerView: 3,
                            },
                        },
                    });
                }

            } catch (error) {
                console.error('PWQB: Не удалось загрузить товары.', error);

                loader.hidden = true;
                productsTitle.hidden = false;
                productsSlider.hidden = true;
            }
        });
    },

    getProductLabel(count) {
        const lastTwo = count % 100;
        const lastDigit = count % 10;

        if (lastTwo >= 11 && lastTwo <= 14) {
            return 'товаров';
        }

        if (lastDigit === 1) {
            return 'товар';
        }

        if (lastDigit >= 2 && lastDigit <= 4) {
            return 'товара';
        }

        return 'товаров';
    },
};

window.PWQB.products.init();
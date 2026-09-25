console.log('=== REAL PRODUCTS LOAD MORE FILE ===');

document.addEventListener('DOMContentLoaded', () => {

    console.log('=== DOM READY IN PRODUCTS LOAD MORE ===');

    const productsList = document.querySelector('.js-products-list');
    const loader = document.querySelector('.products-loader');

    if (!productsList) {
        return;
    }

    let loading = false;

    const loadMoreProducts = async () => {

        if (loading) {
            return;
        }

        if (productsList.dataset.hasMore !== '1') {
            return;
        }

        loading = true;

        if (loader) {
            loader.hidden = false;
        }

        const currentPage =
            parseInt(productsList.dataset.page, 10) || 1;

        const formData = new FormData();

        formData.append(
            'action',
            'load_more_products'
        );

        formData.append(
            'nonce',
            productsLoadMore.nonce
        );

        formData.append(
            'paged',
            currentPage
        );

        formData.append(
            'page_type',
            productsList.dataset.pageType
        );

        formData.append(
            'category_id',
            productsList.dataset.categoryId
        );

        try {

            const response = await fetch(
                productsLoadMore.ajaxUrl,
                {
                    method: 'POST',
                    body: formData,
                }
            );

            const result = await response.json();

            console.log('AJAX RESULT:', result);

            if (!result.success) {
                return;
            }

            if (result.data.html) {

                productsList.insertAdjacentHTML(
                    'beforeend',
                    result.data.html
                );

                productsList.dataset.page =
                    currentPage + 1;
            }

            productsList.dataset.hasMore =
                result.data.has_more ? '1' : '0';

        } catch (error) {

            console.error(
                'Ошибка загрузки товаров:',
                error
            );

        } finally {

            loading = false;

            if (loader) {
                loader.hidden = true;
            }
        }
    };


    /*
     * Триггер загрузки
     */

    const observerTarget = document.createElement('div');

    observerTarget.className =
        'products-load-more-trigger';

    productsList.after(observerTarget);


    /*
     * IntersectionObserver
     */

    const observer = new IntersectionObserver(
        (entries) => {

            if (entries[0].isIntersecting) {

                console.log(
                    'LOAD MORE TRIGGERED'
                );

                loadMoreProducts();
            }

        },
        {
            rootMargin: '500px 0px',
            threshold: 0,
        }
    );

    observer.observe(observerTarget);

});
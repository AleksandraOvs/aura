document.addEventListener('DOMContentLoaded', () => {

    const productsList = document.querySelector('.js-products-list');

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

        const currentPage = parseInt(productsList.dataset.page, 10) || 1;

        const formData = new FormData();

        formData.append('action', 'load_more_products');
        formData.append('nonce', productsLoadMore.nonce);
        formData.append('paged', currentPage);
        formData.append('page_type', productsList.dataset.pageType);
        formData.append('category_id', productsList.dataset.categoryId);

        try {

            const response = await fetch(productsLoadMore.ajaxUrl, {
                method: 'POST',
                body: formData,
            });

            const result = await response.json();

            if (!result.success) {
                return;
            }

            if (result.data.html) {

                productsList.insertAdjacentHTML(
                    'beforeend',
                    result.data.html
                );

                productsList.dataset.page = currentPage + 1;
            }

            productsList.dataset.hasMore = result.data.has_more ? '1' : '0';

        } catch (error) {

            console.error('Ошибка загрузки товаров:', error);

        } finally {

            loading = false;
        }
    };


    /*
     * IntersectionObserver
     *
     * Когда пользователь приблизился к низу списка,
     * загружаем следующую партию.
     */
    const observerTarget = document.createElement('div');

    observerTarget.className = 'products-load-more-trigger';

    productsList.after(observerTarget);

    const observer = new IntersectionObserver(
        (entries) => {

            if (entries[0].isIntersecting) {
                loadMoreProducts();
            }

        },
        {
            rootMargin: '500px 0px',
        }
    );

    observer.observe(observerTarget);

});
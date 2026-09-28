document.addEventListener('DOMContentLoaded', () => {

    const productsList =
        document.querySelector('.js-products-list');

    const loader =
        document.querySelector('.products-loader');


    if (!productsList) {
        return;
    }


    let loading = false;


    /* ---------------------------------------------------
     * LOAD MORE
     * --------------------------------------------------- */

    const loadMoreProducts = async () => {

        if (loading) {
            return;
        }


        /*
         * Больше товаров нет
         */

        if (productsList.dataset.hasMore !== '1') {
            return;
        }


        /*
         * Фильтры ещё не готовы
         */

        if (
            typeof window.cwcGetCurrentFilters !==
            'function'
        ) {
            console.error(
                'cwcGetCurrentFilters не найден'
            );

            return;
        }


        loading = true;


        if (loader) {
            loader.hidden = false;
        }


        /*
         * Текущая страница
         */

        const currentPage =
            parseInt(
                productsList.dataset.page,
                10
            ) || 1;


        const nextPage =
            currentPage + 1;


        /*
         * Берём ТОЧНО такие же фильтры,
         * которые использует ajax-filters.js
         */

        const wrapper =
            document.querySelector(
                '.sidebar-area-wrapper'
            );


        if (!wrapper) {

            loading = false;

            if (loader) {
                loader.hidden = true;
            }

            return;
        }


        const filters =
            window.cwcGetCurrentFilters(wrapper);


        /*
         * Для infinite scroll отправляем
         * следующую страницу
         */

        filters.page = nextPage;


        console.log(
            'LOAD MORE →',
            filters
        );


        /*
         * FormData
         */

        const formData =
            new FormData();


        Object.entries(filters).forEach(
            ([key, value]) => {

                if (Array.isArray(value)) {

                    value.forEach(item => {

                        formData.append(
                            `${key}[]`,
                            item
                        );

                    });

                } else {

                    formData.append(
                        key,
                        value
                    );
                }

            }
        );


        try {

            const response =
                await fetch(
                    cwc_ajax_object.ajax_url,
                    {
                        method: 'POST',
                        body: formData,
                    }
                );


            if (!response.ok) {

                throw new Error(
                    `HTTP ${response.status}`
                );
            }


            const result =
                await response.json();


            console.log(
                'LOAD MORE RESULT:',
                result
            );


            if (!result.success) {

                console.error(
                    'LOAD MORE ERROR:',
                    result.data
                );

                return;
            }


            /*
             * Добавляем товары
             */

            if (result.data.html) {

                productsList.insertAdjacentHTML(
                    'beforeend',
                    result.data.html
                );

            }


            /*
             * Страница считается загруженной
             * только после успешного ответа.
             */

            productsList.dataset.page =
                String(nextPage);


            /*
             * Есть ли ещё товары
             */

            productsList.dataset.hasMore =
                result.data.has_more
                    ? '1'
                    : '0';


            /*
             * Если товаров больше нет,
             * observer больше ничего делать не будет,
             * потому что hasMore = 0.
             */

            if (
                productsList.dataset.hasMore !== '1'
            ) {

                console.log(
                    'Все товары загружены'
                );
            }

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


    /* ---------------------------------------------------
     * TRIGGER
     * --------------------------------------------------- */

    const observerTarget =
        document.createElement('div');


    observerTarget.className =
        'products-load-more-trigger';


    productsList.after(
        observerTarget
    );


    /* ---------------------------------------------------
     * INTERSECTION OBSERVER
     * --------------------------------------------------- */

    const observer =
        new IntersectionObserver(

            (entries) => {

                if (
                    entries[0].isIntersecting
                ) {

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


    observer.observe(
        observerTarget
    );

});

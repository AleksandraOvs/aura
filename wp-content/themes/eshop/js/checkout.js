jQuery(function ($) {

    /**
     * Показываем нужную вкладку
     */
    function showCheckoutTab(tab) {

        $('.checkout-steps [data-checkout-tab]')
            .removeClass('active');

        $('.checkout-steps [data-checkout-tab="' + tab + '"]')
            .addClass('active');

        $('.checkout-tab-panel')
            .removeClass('active');

        $('.checkout-tab-panel[data-checkout-panel="' + tab + '"]')
            .addClass('active');
    }


    /**
     * Переключение вкладок
     */
    $(document).on(
        'click',
        '.checkout-steps [data-checkout-tab]',
        function () {

            const tab = $(this).data('checkout-tab');

            showCheckoutTab(tab);

        }
    );


    /**
     * При загрузке страницы определяем
     * активную вкладку из PHP-класса active.
     */
    const activeTab = $('.checkout-steps [data-checkout-tab].active')
        .data('checkout-tab');


    if (activeTab) {
        showCheckoutTab(activeTab);
    }

});
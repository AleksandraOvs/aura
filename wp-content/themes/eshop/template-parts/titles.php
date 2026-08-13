<?php
// Не выводим заголовок на страницах cart, checkout, account и одиночного товара
if (! (is_singular('product'))) {

    if (is_shop() || is_product_taxonomy() || is_post_type_archive('product') || is_order_received_page()) {
        // Главная магазина или архив категорий/товаров
        $title = woocommerce_page_title(false); // Получаем заголовок без вывода
        // if ($title) {
        //     echo '<h1 class="page-title">' . esc_html($title) . '</h1>';
        // }
    } elseif (is_page()) {
        // Обычная страница
        the_title('<h1 class="page-title">', '</h1>');
    } elseif (is_archive()) {
        // Другие архивы постов
        the_archive_title('<h1 class="page-title">', '</h1>');
    } elseif (is_search()) {
        echo '<h1 class="page-title">Результаты поиска: ' . get_search_query() . '</h1>';
    } else {
        // По умолчанию
        echo '<h1 class="page-title">' . get_bloginfo('name') . '</h1>';
    }
}

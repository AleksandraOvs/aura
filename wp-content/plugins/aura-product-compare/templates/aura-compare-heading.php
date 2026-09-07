<?php

$compare_categories = [];

foreach ($products as $compare_product) {

    $product_categories = get_the_terms(
        $compare_product->get_id(),
        'product_cat'
    );

    if (
        !empty($product_categories) &&
        !is_wp_error($product_categories)
    ) {

        foreach ($product_categories as $category) {

            $compare_categories[$category->term_id] = $category;
        }
    }
}

?>

<div class="aura-compare__heading">

    <div class="aura-compare__categories">

        <button
            type="button"
            class="aura-compare__category is-active"
            data-category="all">
            Все товары
        </button>

        <?php if (!empty($compare_categories)) : ?>

            <?php foreach ($compare_categories as $category) : ?>

                <button
                    type="button"
                    class="aura-compare__category"
                    data-category="<?php echo esc_attr($category->term_id); ?>">

                    <?php echo esc_html($category->name); ?>

                </button>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>


    <div class="aura-compare__heading-actions">

        <?php
        $svg_clear = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M5.39744 0C5.04656 0 4.7624 0.28416 4.7624 0.63504V0.95248H0.63504C0.28448 0.95248 0 1.23696 0 1.58752C0 1.93808 0.28448 2.22256 0.63504 2.22256H1.00336L1.81008 11.9058C1.89136 12.8853 2.72592 13.6526 3.7088 13.6526L9.62608 13.6525C10.6091 13.6525 11.4434 12.8851 11.5248 11.9056L12.3315 2.2224L12.7 2.22256C13.0506 2.22256 13.335 1.93808 13.335 1.58752C13.335 1.23696 13.0506 0.95248 12.7 0.95248H8.57248V0.63504C8.57248 0.28416 8.28832 0 7.93744 0H5.39744ZM2.27776 2.22256H11.0579L10.2592 11.8003C10.2318 12.1267 9.9544 12.3826 9.62672 12.3826H3.70896C3.38096 12.3826 3.10288 12.1267 3.07584 11.8003L2.27776 2.22256Z" fill="#D4D4D4"/>
</svg>';

        $svg_docatalog = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M7.75 0.75V14.75" stroke="#D4D4D4" stroke-width="1.5" stroke-linecap="round"/>
<path d="M14.75 7.75H0.75" stroke="#D4D4D4" stroke-width="1.5" stroke-linecap="round"/>
</svg>
';
        ?>
        <button
            type="button"
            class="aura-compare__clear">
            <?php echo $svg_clear ?>
            Удалить список
        </button>

        <a
            href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"
            class="aura-compare__back-shop">
            <?php echo $svg_docatalog ?>
            Добавить товары
        </a>

    </div>

</div>
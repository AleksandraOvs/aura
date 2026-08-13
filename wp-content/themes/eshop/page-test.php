<?php

/**
 * Template name: test
 */

get_header() ?>
<h1>njdfhs b fnhb,ens</h1>
<?php

$args = [
    'post_type'      => 'product',
    'posts_per_page' => 20,
];

$query = new WP_Query($args);

if ($query->have_posts()) {

    echo '<h2>DEBUG ТОВАРЫ</h2>';

    while ($query->have_posts()) {
        $query->the_post();

        $product = wc_get_product(get_the_ID());

        echo '<div style="border:1px solid #000; padding:10px; margin-bottom:10px;">';

        echo '<strong>ID:</strong> ' . get_the_ID() . '<br>';
        echo '<strong>Название:</strong> ' . get_the_title() . '<br>';

        $attributes = $product->get_attributes();

        if (!empty($attributes)) {

            echo '<strong>Атрибуты:</strong><br>';

            foreach ($attributes as $attr_name => $attr) {

                echo '<div style="margin-left:10px;">';
                echo '<b>' . $attr_name . '</b>: ';

                if ($attr->is_taxonomy()) {

                    $terms = wp_get_post_terms($product->get_id(), $attr_name);

                    $values = [];
                    foreach ($terms as $term) {
                        $values[] = $term->name . ' (' . $term->slug . ')';
                    }

                    echo implode(', ', $values);
                } else {
                    echo implode(', ', $attr->get_options());
                }

                echo '</div>';
            }
        } else {
            echo 'Нет атрибутов';
        }

        echo '</div>';
    }
} else {
    echo '<h2>❌ ТОВАРЫ НЕ НАЙДЕНЫ</h2>';
}

wp_reset_postdata();

?>
<?php get_footer() ?>
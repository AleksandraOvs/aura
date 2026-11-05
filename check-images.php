<?php

require_once __DIR__ . '/wp-load.php';

$ids = [5263, 5265, 5267];

echo '<pre>';

foreach ($ids as $id) {
    echo "\nATTACHMENT: {$id}\n";

    echo "\nWOOCOMMERCE SINGLE:\n";

    echo wp_get_attachment_image(
        $id,
        'woocommerce_single'
    );

    echo "\nLARGE:\n";

    echo wp_get_attachment_image(
        $id,
        'large'
    );

    echo "\nFULL:\n";

    echo wp_get_attachment_image(
        $id,
        'full'
    );

    echo "\n----------------------\n";
}

echo '</pre>';

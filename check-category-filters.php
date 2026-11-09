<?php

$cat = get_term_by(
    'slug',
    'interernoe-osveshhenie',
    'product_cat'
);

if (!$cat) {
    echo "Category not found\n";
    return;
}

$filters = cwc_get_enabled_filter_attributes($cat->term_id);

$ancestors = get_ancestors(
    $cat->term_id,
    'product_cat',
    'taxonomy'
);

$root_id = $ancestors
    ? (int) end($ancestors)
    : (int) $cat->term_id;

echo "Category ID: {$cat->term_id}\n";
echo "Root ID: {$root_id}\n";
echo "Enabled filters: " . count($filters) . "\n\n";

foreach ($filters as $tax) {
    echo $tax . ' => ' . CWC_Filter_Renderer::get_type($tax) . "\n";
}

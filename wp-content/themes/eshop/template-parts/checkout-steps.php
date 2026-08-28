<?php

$current_page_id = get_queried_object_id();
$parent_id = wp_get_post_parent_id($current_page_id);

if (!$parent_id) {
    return;
}

$page_nav = get_children([
    'post_parent' => $parent_id,
    'post_type'   => 'page',
    'post_status' => 'publish',
    'orderby'     => 'menu_order',
    'order'       => 'ASC',
]);

if (!$page_nav) {
    return;
}

?>

<nav class="page-nav">

    <?php foreach ($page_nav as $page): ?>

        <a
            class="page-nav__link <?= $page->ID === $current_page_id ? 'active' : ''; ?>"
            href="<?= esc_url(get_permalink($page->ID)); ?>">
            <?= esc_html(get_the_title($page->ID)); ?>
        </a>

    <?php endforeach; ?>

</nav>
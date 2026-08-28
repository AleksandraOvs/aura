<?php

/**
 * Template Name: Шаблон Ваш заказ
 */

defined('ABSPATH') || exit;

get_header();

$page_nav = [
    [
        'title' => 'Корзина',
        'step'  => 'cart',
    ],
    [
        'title' => 'Оформление заказа',
        'step'  => 'checkout',
    ],
];

$current_step = isset($_GET['step'])
    ? sanitize_key($_GET['step'])
    : 'cart';

if (!in_array($current_step, ['cart', 'checkout'], true)) {
    $current_step = 'cart';
}

?>

<section class="page-title-block">

    <div class="fixed-container">

        <?php site_breadcrumbs(); ?>

        <nav class="page-nav">

            <?php foreach ($page_nav as $item): ?>

                <a
                    class="page-nav__link <?= $current_step === $item['step'] ? 'active' : ''; ?>"
                    href="<?= esc_url(
                                add_query_arg('step', $item['step'], get_permalink())
                            ); ?>">
                    <?= esc_html($item['title']); ?>
                </a>

            <?php endforeach; ?>

        </nav>

        <h1 class="page-title" data-scroll-animation="fade-down">
            <?= esc_html(
                $current_step === 'cart'
                    ? 'Корзина'
                    : 'Оформление заказа'
            ); ?>
        </h1>

    </div>

</section>

<section class="page-content">

    <div class="container">

        <?php if ($current_step === 'cart'): ?>

            <?= do_shortcode('[woocommerce_cart]'); ?>

        <?php else: ?>

            <?= do_shortcode('[woocommerce_checkout]'); ?>

        <?php endif; ?>

    </div>

</section>

<?php get_footer(); ?>
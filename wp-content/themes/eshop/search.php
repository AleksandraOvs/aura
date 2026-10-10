<?php
defined('ABSPATH') || exit;

get_header('shop');

global $wp_query;

$current_page = max(1, (int) $wp_query->get('paged'));
$has_more     = $current_page < (int) $wp_query->max_num_pages;


$search_query = get_search_query();
$columns      = wc_get_loop_prop('columns') ?: 4;
$total_posts  = (int) $wp_query->found_posts;

?>

<main class="search-page">

    <section class="page-title-block">
        <div class="fixed-container">
            <?php site_breadcrumbs(); ?>

            <h1 class="page-title">
                <?php if ($search_query !== '') : ?>
                    Результаты поиска: «<?php echo esc_html($search_query); ?>»
                <?php else : ?>
                    Поиск товаров
                <?php endif; ?>
            </h1>
        </div>
    </section>

    <div class="container">

        <?php if (have_posts()) : ?>

            <div class="shop-inner">

                <div class="shop-inner__products">

                    <p class="search-page__count">
                        Найдено товаров:
                        <?php echo esc_html((string) $total_posts); ?>
                    </p>

                    <?php $rendered_products = 0; ?>

                    <ul class="products products-<?php echo esc_attr($columns); ?>">

                        <?php while (have_posts()) : the_post(); ?>

                            <?php
                            $rendered_products++;
                            wc_get_template_part('content', 'product');
                            ?>

                        <?php endwhile; ?>

                    </ul>

                    <?php eshop_search_pagination(); ?>

                </div>
            </div>

        <?php else : ?>

            <div class="empty-wl">
                <p>
                    По запросу «<?php echo esc_html($search_query); ?>»
                    ничего не найдено.
                </p>

                <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">
                    Перейти в каталог
                </a>
            </div>

        <?php endif; ?>

    </div>

    <?php get_template_part('sections/offer'); ?>

</main>

<?php get_footer('shop'); ?>
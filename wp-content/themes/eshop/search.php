<?php
defined('ABSPATH') || exit;

get_header('shop');

global $wp_query;

$current_page = max(1, (int) $wp_query->get('paged'));
$has_more     = $current_page < (int) $wp_query->max_num_pages;


$search_query = get_search_query();
$columns      = wc_get_loop_prop('columns') ?: 4;
$total_posts  = (int) $wp_query->found_posts;

// DEBUG: информация об основном запросе поиска
if (current_user_can('manage_options') && isset($_GET['search_debug'])) {
    echo '<pre style="position:relative;z-index:999999;background:#fff;color:#111;padding:20px;margin:20px;border:2px solid red;white-space:pre-wrap;">';

    echo "=== SEARCH DEBUG ===\n";
    echo 'URL query: ' . esc_html(wp_json_encode($_GET, JSON_UNESCAPED_UNICODE)) . "\n";
    echo 'is_search: ' . (is_search() ? 'true' : 'false') . "\n";
    echo 'is_shop: ' . (is_shop() ? 'true' : 'false') . "\n";
    echo 'post_type: ' . esc_html(wp_json_encode($wp_query->get('post_type'), JSON_UNESCAPED_UNICODE)) . "\n";
    echo 'search term: ' . esc_html($search_query) . "\n";
    echo 'found_posts: ' . (int) $wp_query->found_posts . "\n";
    echo 'post_count: ' . (int) $wp_query->post_count . "\n";
    echo 'posts_per_page: ' . (int) $wp_query->get('posts_per_page') . "\n";
    echo 'paged: ' . (int) max(1, $wp_query->get('paged')) . "\n";
    echo 'max_num_pages: ' . (int) $wp_query->max_num_pages . "\n";
    echo 'IDs in main query: ' . esc_html(implode(', ', wp_list_pluck($wp_query->posts, 'ID'))) . "\n";

    echo '</pre>';
}

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

                    <?php if (current_user_can('manage_options') && isset($_GET['search_debug'])) : ?>
                        <pre style="background:#fff;color:#111;padding:20px;border:2px solid blue;white-space:pre-wrap;">
=== RENDER DEBUG ===
Карточек выведено циклом: <?php echo (int) $rendered_products; ?>

    </pre>
                    <?php endif; ?>

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
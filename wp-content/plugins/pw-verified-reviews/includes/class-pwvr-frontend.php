<?php

defined('ABSPATH') || exit;

class PWVR_Frontend
{

    public static function init()
    {
        add_shortcode(
            'pwvr_review_form',
            array(__CLASS__, 'render_review_form')
        );

        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    /**
     * Подключает стили и JavaScript на страницах товаров.
     */
    public static function enqueue_assets()
    {

        if (! is_product()) {
            return;
        }

        wp_enqueue_style(
            'pwvr-reviews',
            PWVR_URL . 'assets/css/reviews.css',
            array(),
            PWVR_VERSION
        );

        wp_enqueue_script(
            'pwvr-reviews',
            PWVR_URL . 'assets/js/reviews.js',
            array(),
            PWVR_VERSION,
            true
        );

        wp_localize_script(
            'pwvr-reviews',
            'PWVR',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('pwvr_submit_review'),
            )
        );
    }

    /**
     * Возвращает форму отзыва.
     */
    private static function render_form($product_id)
    {

        $ratings_enabled = get_option('woocommerce_enable_review_rating') === 'yes';
        $rating_required = get_option('woocommerce_review_rating_required') === 'yes';

        ob_start();
?>
        <h3>Оставите отзыв об этом товаре?</h3>
        <form class="pwvr-form">
            <input type="hidden" name="product_id"
                value="<?php echo esc_attr($product_id); ?>">

            <input type="hidden" name="nonce"
                value="<?php echo esc_attr(wp_create_nonce('pwvr_submit_review')); ?>">

            <div class="pwvr-form__honeypot" aria-hidden="true">
                <label>
                    Не заполняйте это поле
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </label>
            </div>
            <label class="pwvr-form__label">
                Ваш отзыв
                <textarea
                    name="review"
                    rows="5"
                    minlength="5"
                    maxlength="5000"
                    required></textarea>
            </label>

            <?php if ($ratings_enabled) : ?>
                <fieldset class="pwvr-form__rating">
                    <legend>
                        Оценка товара
                        <?php echo $rating_required ? '*' : ''; ?>
                    </legend>

                    <div class="pwvr-rating" role="radiogroup" aria-label="Оценка товара">
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <input
                                type="radio"
                                id="pwvr-rating-<?php echo esc_attr($product_id); ?>-<?php echo $i; ?>"
                                name="rating"
                                value="<?php echo $i; ?>"
                                <?php echo $rating_required ? 'required' : ''; ?>>

                            <label
                                for="pwvr-rating-<?php echo esc_attr($product_id); ?>-<?php echo $i; ?>"
                                aria-label="<?php echo esc_attr($i . ' из 5'); ?>"
                                title="<?php echo esc_attr($i . ' из 5'); ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </fieldset>
            <?php endif; ?>

            <button type="submit" class="button button-black">
                Отправить отзыв
            </button>

            <p class="pwvr-form__message" role="status" aria-live="polite"></p>
        </form>
<?php

        return ob_get_clean();
    }

    /**
     * Выводит интерфейс отзыва для текущего товара.
     */
    public static function render_review_form()
    {

        if (! function_exists('is_product') || ! is_product()) {
            return '';
        }

        $product_id = get_queried_object_id();

        if (! $product_id) {
            return '';
        }

        ob_start();

        echo '<div class="pwvr-review">';

        // Гость: предлагаем авторизацию.
        if (! is_user_logged_in()) {

            $login_url = add_query_arg(
                'redirect_to',
                get_permalink($product_id),
                wc_get_page_permalink('myaccount')
            );

            echo '<p>Чтобы оставить отзыв, войдите в личный кабинет.</p>';
            echo '<p><a href="' . esc_url($login_url) . '">Войти в аккаунт</a></p>';
        } else {

            $user_id = get_current_user_id();

            // Проверяем наличие выполненного заказа.
            if (! PWVR_Purchases::user_purchased_product($user_id, $product_id)) {

                echo '<p>Оставить отзыв можно после выполнения заказа на этот товар.</p>';
            } else {

                // Проверяем, оставлял ли пользователь отзыв.
                $existing_reviews = get_comments(array(
                    'post_id' => $product_id,
                    'user_id' => $user_id,
                    'type'    => 'review',
                    'status'  => 'all',
                    'number'  => 1,
                ));

                if (! empty($existing_reviews)) {

                    echo '<p>Вы уже оставили отзыв на этот товар.</p>';
                } else {

                    // Пользователь купил товар и ещё не оставлял отзыв.
                    echo self::render_form($product_id);
                }
            }
        }

        echo '</div>';

        return ob_get_clean();
    }
}

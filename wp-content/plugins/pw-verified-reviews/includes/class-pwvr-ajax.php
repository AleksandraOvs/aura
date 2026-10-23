
<?php

defined('ABSPATH') || exit;

class PWVR_Ajax
{

    public static function init()
    {
        add_action(
            'wp_ajax_pwvr_submit_review',
            array(__CLASS__, 'submit_review')
        );
    }

    public static function submit_review()
    {

        if (! is_user_logged_in()) {
            wp_send_json_error(
                array('message' => 'Войдите в аккаунт, чтобы оставить отзыв.'),
                401
            );
        }

        check_ajax_referer('pwvr_submit_review', 'nonce');

        $user_id    = get_current_user_id();
        $product_id = absint($_POST['product_id'] ?? 0);
        $content    = sanitize_textarea_field(
            wp_unslash($_POST['review'] ?? '')
        );

        // Honeypot: поле должно оставаться пустым.
        if (! empty($_POST['website'])) {
            wp_send_json_error(
                array('message' => 'Не удалось отправить отзыв.'),
                400
            );
        }

        if (
            ! $product_id
            || 'product' !== get_post_type($product_id)
            || ! comments_open($product_id)
        ) {
            wp_send_json_error(
                array('message' => 'Отзывы для этого товара недоступны.'),
                400
            );
        }

        if (! PWVR_Purchases::user_purchased_product($user_id, $product_id)) {
            wp_send_json_error(
                array('message' => 'Отзыв доступен после выполнения заказа на этот товар.'),
                403
            );
        }

        if (mb_strlen($content) < 5 || mb_strlen($content) > 5000) {
            wp_send_json_error(
                array('message' => 'Текст отзыва должен содержать от 5 до 5000 символов.'),
                400
            );
        }

        $ratings_enabled = get_option('woocommerce_enable_review_rating') === 'yes';
        $rating_required = get_option('woocommerce_review_rating_required') === 'yes';
        $rating = isset($_POST['rating'])
            ? (int) wp_unslash($_POST['rating'])
            : 0;

        if (
            $ratings_enabled
            && (
                ($rating_required && ($rating < 1 || $rating > 5))
                || ($rating !== 0 && ($rating < 1 || $rating > 5))
            )
        ) {
            wp_send_json_error(
                array('message' => 'Выберите оценку от 1 до 5 звёзд.'),
                400
            );
        }

        // Не разрешаем несколько отзывов одного пользователя на товар.
        $existing = get_comments(array(
            'post_id' => $product_id,
            'user_id' => $user_id,
            'type'    => 'review',
            'status'  => 'all',
            'number'  => 1,
        ));

        if (! empty($existing)) {
            wp_send_json_error(
                array('message' => 'Вы уже отправляли отзыв на этот товар.'),
                409
            );
        }

        // Ограничение частоты отправки.
        $rate_key = 'pwvr_rate_' . $user_id . '_' . $product_id;

        if (get_transient($rate_key)) {
            wp_send_json_error(
                array('message' => 'Подождите немного перед повторной отправкой.'),
                429
            );
        }

        $user = get_userdata($user_id);

        $comment_data = array(
            'comment_post_ID'      => $product_id,
            'comment_author'       => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_author_url'   => '',
            'comment_content'      => $content,
            'user_ID'              => $user_id,
            'comment_type'         => 'review',
            'comment_approved'     => 0,
        );

        $comment_id = wp_new_comment($comment_data, true);

        if (is_wp_error($comment_id) || ! $comment_id) {
            wp_send_json_error(
                array('message' => 'Не удалось сохранить отзыв. Попробуйте позже.'),
                500
            );
        }

        wp_set_comment_status($comment_id, 'hold');

        if ($ratings_enabled && $rating >= 1 && $rating <= 5) {
            update_comment_meta($comment_id, 'rating', $rating);
        }

        set_transient($rate_key, 1, MINUTE_IN_SECONDS);

        wp_send_json_success(
            array(
                'message' => 'Спасибо за ваше мнение. После проверки модератором отзыв появится здесь.',
            )
        );
    }
}

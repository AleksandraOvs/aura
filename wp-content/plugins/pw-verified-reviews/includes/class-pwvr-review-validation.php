<?php

defined('ABSPATH') || exit;

class PWVR_Review_Validation
{

    /**
     * Подключает серверную проверку отзывов.
     */
    public static function init()
    {
        add_filter(
            'preprocess_comment',
            array(__CLASS__, 'validate_product_review')
        );
    }

    /**
     * Не позволяет отправить отзыв без подтверждённой покупки.
     */
    public static function validate_product_review($commentdata)
    {

        $product_id = absint(
            $commentdata['comment_post_ID'] ?? 0
        );

        if ('product' !== get_post_type($product_id)) {
            return $commentdata;
        }

        $user_id = get_current_user_id();

        if (
            ! $user_id
            || ! PWVR_Purchases::user_purchased_product(
                $user_id,
                $product_id
            )
        ) {
            wp_die(
                esc_html__(
                    'Оставить отзыв можно после выполнения заказа на этот товар.',
                    'pw-verified-reviews'
                ),
                esc_html__('Отзыв не отправлен', 'pw-verified-reviews'),
                array('response' => 403)
            );
        }

        return $commentdata;
    }
}

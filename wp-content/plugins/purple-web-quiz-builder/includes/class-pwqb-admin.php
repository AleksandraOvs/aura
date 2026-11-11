<?php

if (!defined('ABSPATH')) {
    exit;
}

class PWQB_Admin
{
    public static function init(): void
    {
        add_filter(
            'post_row_actions',
            [self::class, 'add_duplicate_action'],
            10,
            2
        );

        add_action(
            'admin_action_pwqb_duplicate_quiz',
            [self::class, 'duplicate_quiz']
        );

        add_action(
            'admin_notices',
            [self::class, 'admin_notices']
        );
    }

    /**
     * Добавляет действие «Дублировать» в список квизов.
     */
    public static function add_duplicate_action(array $actions, WP_Post $post): array
    {
        if (
            $post->post_type !== PWQB_Quiz::POST_TYPE ||
            !current_user_can('edit_post', $post->ID)
        ) {
            return $actions;
        }

        $url = wp_nonce_url(
            admin_url(
                'admin.php?action=pwqb_duplicate_quiz&post=' . $post->ID
            ),
            'pwqb_duplicate_quiz_' . $post->ID
        );

        $actions['duplicate'] = sprintf(
            '<a href="%s">Дублировать</a>',
            esc_url($url)
        );

        return $actions;
    }

    /**
     * Создаёт копию квиза.
     */
    public static function duplicate_quiz(): void
    {
        $post_id = isset($_GET['post'])
            ? absint($_GET['post'])
            : 0;

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_die('Недостаточно прав для дублирования квиза.');
        }

        check_admin_referer('pwqb_duplicate_quiz_' . $post_id);

        $source = get_post($post_id);

        if (
            !$source ||
            $source->post_type !== PWQB_Quiz::POST_TYPE
        ) {
            wp_die('Исходный квиз не найден.');
        }

        // Создаём новую запись как черновик.
        $new_id = wp_insert_post([
            'post_type'   => PWQB_Quiz::POST_TYPE,
            'post_status' => 'draft',
            'post_title'  => $source->post_title . ' (копия)',
            'post_author' => get_current_user_id(),
        ], true);

        if (is_wp_error($new_id)) {
            wp_die(esc_html($new_id->get_error_message()));
        }

        // Копируем настройки конструктора.
        $settings = get_post_meta(
            $post_id,
            PWQB_Quiz::META_SETTINGS,
            true
        );

        if (is_array($settings)) {
            update_post_meta(
                $new_id,
                PWQB_Quiz::META_SETTINGS,
                $settings
            );
        }

        // Сохраняем старое описание для совместимости.
        $description = get_post_meta(
            $post_id,
            PWQB_Quiz::META_DESCRIPTION,
            true
        );

        if ($description !== '') {
            update_post_meta(
                $new_id,
                PWQB_Quiz::META_DESCRIPTION,
                $description
            );
        }

        wp_safe_redirect(
            admin_url(
                'post.php?post=' . absint($new_id) .
                    '&action=edit&pwqb_duplicated=1'
            )
        );

        exit;
    }

    /**
     * Уведомление после успешного дублирования.
     */
    public static function admin_notices(): void
    {
        if (!isset($_GET['pwqb_duplicated'])) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>Квиз скопирован. Новая копия сохранена как черновик.</p>';
        echo '</div>';
    }
}

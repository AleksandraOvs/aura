<?php

if (!defined('ABSPATH')) {
    exit;
}

class PWQB_Quiz
{
    public const POST_TYPE = 'pwqb_quiz';

    /**
     * Старое метаполе описания.
     * Оставляем для совместимости с уже созданными квизами.
     */
    public const META_DESCRIPTION = '_pwqb_description';

    /**
     * Основные настройки квиза.
     */
    public const META_SETTINGS = '_pwqb_settings';

    public static function init(): void
    {
        add_action('init', [self::class, 'register_post_type']);

        add_filter(
            'manage_' . self::POST_TYPE . '_posts_columns',
            [self::class, 'admin_columns']
        );

        add_action(
            'manage_' . self::POST_TYPE . '_posts_custom_column',
            [self::class, 'render_admin_column'],
            10,
            2
        );
    }

    /**
     * Регистрация типа записи.
     */
    public static function register_post_type(): void
    {
        $labels = [
            'name'               => 'Квизы',
            'singular_name'      => 'Квиз',
            'add_new'            => 'Добавить квиз',
            'add_new_item'       => 'Добавить новый квиз',
            'edit_item'          => 'Редактировать квиз',
            'new_item'           => 'Новый квиз',
            'view_item'          => 'Просмотреть квиз',
            'search_items'       => 'Найти квизы',
            'not_found'          => 'Квизы не найдены',
            'not_found_in_trash' => 'В корзине квизов нет',
            'all_items'          => 'Все квизы',
            'menu_name'          => 'Квизы',
        ];

        register_post_type(self::POST_TYPE, [
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => false,
            'show_in_nav_menus'   => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'supports'            => ['title'],
            'menu_icon'           => 'dashicons-feedback',
            'menu_position'       => class_exists('WooCommerce') ? 56 : 25,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ]);
    }

    /**
     * Колонки в списке квизов.
     */
    public static function admin_columns(array $columns): array
    {
        $result = [];

        foreach ($columns as $key => $label) {
            $result[$key] = $label;

            if ($key === 'title') {
                $result['pwqb_shortcode'] = 'Шорткод';
            }
        }

        return $result;
    }

    /**
     * Вывод шорткода в списке квизов.
     */
    public static function render_admin_column(string $column, int $post_id): void
    {
        if ($column !== 'pwqb_shortcode') {
            return;
        }

        printf(
            '<code>[pw_quiz id="%d"]</code>',
            absint($post_id)
        );
    }
}

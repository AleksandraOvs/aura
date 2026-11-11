<?php

if (!defined('ABSPATH')) {
    exit;
}

class PWQB_Activator
{
    /**
     * Версия структуры базы данных.
     */
    private const DB_VERSION = '1.1.0';

    /**
     * Ключ версии БД в wp_options.
     */
    private const DB_VERSION_OPTION = 'pwqb_db_version';

    /**
     * Активация плагина.
     */
    public static function activate(): void
    {
        PWQB_Quiz::register_post_type();

        self::create_submissions_table();

        flush_rewrite_rules();
    }

    /**
     * Деактивация плагина.
     */
    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }

    /**
     * Проверка необходимости обновления структуры БД.
     *
     * Вызывается при загрузке WordPress.
     */
    public static function maybe_upgrade(): void
    {
        $installed_version = get_option(self::DB_VERSION_OPTION, '');

        if ($installed_version === self::DB_VERSION) {
            return;
        }

        self::create_submissions_table();
    }

    /**
     * Создание таблицы заявок.
     */
    private static function create_submissions_table(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name = $wpdb->prefix . 'pwqb_submissions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            quiz_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            name varchar(190) NOT NULL DEFAULT '',
            email varchar(190) NOT NULL DEFAULT '',
            phone varchar(50) NOT NULL DEFAULT '',
            send_to varchar(50) NOT NULL DEFAULT '',
            messengers longtext NOT NULL,
            comment text NOT NULL,
            answers longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY quiz_id (quiz_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta($sql);

        update_option(
            self::DB_VERSION_OPTION,
            self::DB_VERSION
        );
    }
}

// Автоматическое обновление структуры БД при обновлении плагина.
add_action(
    'plugins_loaded',
    [PWQB_Activator::class, 'maybe_upgrade']
);

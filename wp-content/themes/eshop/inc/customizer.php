<?php

/**
 * Eshop Theme Customizer
 *
 * Настройки темы в WordPress Customizer:
 * - логотипы Header / Footer;
 * - цвета темы;
 * - ширина основного контейнера;
 * - ширина фиксированного контейнера;
 * - единицы измерения контейнеров (px / %);
 * - внутренние отступы контейнера;
 * - CSS-переменные для фронтенда и Gutenberg.
 *
 * @package eshop
 */


/**
 * =========================================================
 * 1. ОСНОВНЫЕ НАСТРОЙКИ CUSTOMIZER
 * =========================================================
 *
 * Регистрируем:
 * - live preview для названия и описания сайта;
 * - логотип Header;
 * - логотип Footer;
 * - настройки контейнеров.
 *
 * @param WP_Customize_Manager $wp_customize Объект Customizer.
 */
function eshop_customize_register($wp_customize)
{
    /*
     * -----------------------------------------------------
     * Live Preview для названия и описания сайта
     * -----------------------------------------------------
     */

    $wp_customize->get_setting('blogname')->transport = 'postMessage';
    $wp_customize->get_setting('blogdescription')->transport = 'postMessage';
    $wp_customize->get_setting('header_textcolor')->transport = 'postMessage';


    /*
     * -----------------------------------------------------
     * Selective Refresh
     * -----------------------------------------------------
     *
     * Позволяет обновлять название и описание сайта
     * в предпросмотре Customizer без полной перезагрузки.
     */

    if (isset($wp_customize->selective_refresh)) {

        $wp_customize->selective_refresh->add_partial('blogname', [
            'selector'        => '.site-title a',
            'render_callback' => 'eshop_customize_partial_blogname',
        ]);

        $wp_customize->selective_refresh->add_partial('blogdescription', [
            'selector'        => '.site-description',
            'render_callback' => 'eshop_customize_partial_blogdescription',
        ]);
    }


    /*
     * =====================================================
     * ЛОГОТИПЫ
     * =====================================================
     */


    /*
     * -----------------------------------------------------
     * Логотип Header
     * -----------------------------------------------------
     */

    $wp_customize->add_setting('header_logo', [
        'default'           => '',
        'sanitize_callback' => 'absint',
    ]);

    $wp_customize->add_control(
        new WP_Customize_Media_Control(
            $wp_customize,
            'header_logo',
            [
                'section' => 'title_tagline',
                'label'   => 'Логотип Header',
            ]
        )
    );


    /*
     * -----------------------------------------------------
     * Логотип Footer
     * -----------------------------------------------------
     */

    $wp_customize->add_setting('footer_logo', [
        'default'           => '',
        'sanitize_callback' => 'absint',
    ]);

    $wp_customize->add_control(
        new WP_Customize_Media_Control(
            $wp_customize,
            'footer_logo',
            [
                'section' => 'title_tagline',
                'label'   => 'Логотип Footer',
            ]
        )
    );


    /*
     * =====================================================
     * КОНТЕЙНЕРЫ
     * =====================================================
     *
     * Создаем отдельную секцию "Контейнеры".
     */

    $wp_customize->add_section('eshop_containers', [
        'title'    => 'Контейнеры',
        'priority' => 30,
    ]);


    /*
     * -----------------------------------------------------
     * Основная ширина контейнера
     * -----------------------------------------------------
     *
     * Пример:
     *
     * 1920 + px = 1920px
     * 90 + %   = 90%
     *
     * CSS-переменная:
     *
     * --container-width
     */

    $wp_customize->add_setting('container_width', [
        'default'           => 1920,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('container_width', [
        'type'        => 'number',
        'label'       => 'Ширина основного контейнера',
        'description' => 'Укажите ширину контейнера.',
        'section'     => 'eshop_containers',
        'input_attrs' => [
            'min'  => 1,
            'step' => 1,
        ],
    ]);


    /*
     * -----------------------------------------------------
     * Единица измерения основного контейнера
     * -----------------------------------------------------
     *
     * Разрешены только:
     *
     * px
     * %
     */

    $wp_customize->add_setting('container_width_unit', [
        'default'           => 'px',
        'sanitize_callback' => 'eshop_sanitize_container_unit',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('container_width_unit', [
        'type'    => 'select',
        'label'   => 'Единица измерения',
        'section' => 'eshop_containers',
        'choices' => [
            'px' => 'Пиксели (px)',
            '%'  => 'Проценты (%)',
        ],
    ]);


    /*
     * -----------------------------------------------------
     * Ширина фиксированного контейнера
     * -----------------------------------------------------
     *
     * Пример:
     *
     * 1080 + px = 1080px
     * 90 + %   = 90%
     *
     * CSS-переменная:
     *
     * --fixed-container-width
     */

    $wp_customize->add_setting('fixed_container_width', [
        'default'           => 1080,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('fixed_container_width', [
        'type'        => 'number',
        'label'       => 'Ширина фиксированного контейнера',
        'description' => 'Укажите ширину фиксированного контейнера.',
        'section'     => 'eshop_containers',
        'input_attrs' => [
            'min'  => 1,
            'step' => 1,
        ],
    ]);


    /*
     * -----------------------------------------------------
     * Единица измерения фиксированного контейнера
     * -----------------------------------------------------
     */

    $wp_customize->add_setting('fixed_container_width_unit', [
        'default'           => 'px',
        'sanitize_callback' => 'eshop_sanitize_container_unit',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('fixed_container_width_unit', [
        'type'    => 'select',
        'label'   => 'Единица измерения',
        'section' => 'eshop_containers',
        'choices' => [
            'px' => 'Пиксели (px)',
            '%'  => 'Проценты (%)',
        ],
    ]);


    /*
     * -----------------------------------------------------
     * Внутренний padding контейнера
     * -----------------------------------------------------
     *
     * Этот параметр остается только в px.
     *
     * CSS:
     *
     * --container-padding
     */

    $wp_customize->add_setting('container_padding', [
        'default'           => 20,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ]);

    $wp_customize->add_control('container_padding', [
        'type'        => 'number',
        'label'       => 'Внутренний padding контейнера',
        'description' => 'Отступы слева и справа в px.',
        'section'     => 'eshop_containers',
        'input_attrs' => [
            'min'  => 0,
            'step' => 1,
        ],
    ]);
}

add_action('customize_register', 'eshop_customize_register');


/**
 * =========================================================
 * 2. SANITIZATION
 * =========================================================
 *
 * Проверяем единицы измерения контейнеров.
 *
 * Разрешены только:
 * - px
 * - %
 *
 * Если пришло другое значение — используем px.
 *
 * @param string $value Значение единицы измерения.
 * @return string
 */
function eshop_sanitize_container_unit($value)
{
    return in_array($value, ['px', '%'], true)
        ? $value
        : 'px';
}


/**
 * =========================================================
 * 3. SELECTIVE REFRESH
 * =========================================================
 *
 * Рендер названия сайта.
 */
function eshop_customize_partial_blogname()
{
    bloginfo('name');
}


/**
 * Рендер описания сайта.
 */
function eshop_customize_partial_blogdescription()
{
    bloginfo('description');
}


/**
 * =========================================================
 * 4. ЦВЕТА ТЕМЫ
 * =========================================================
 *
 * Регистрируем основные цвета темы.
 *
 * CSS-переменные:
 *
 * --theme-color-primary
 * --theme-color-secondary
 * --theme-color-accent
 * --theme-color-grey
 */
function eshop_customize_colors_register($wp_customize)
{
    /*
     * Цвета по умолчанию.
     */

    $color_settings = [

        'black' => [
            'default' => '#000000',
            'label'   => 'Черный цвет',
        ],

        'primary' => [
            'default' => '#0073aa',
            'label'   => 'Основной цвет',
        ],

        'secondary' => [
            'default' => '#005177',
            'label'   => 'Вторичный цвет',
        ],

        'accent' => [
            'default' => '#d54e21',
            'label'   => 'Акцентный цвет',
        ],

        'grey' => [
            'default' => '#656464',
            'label'   => 'Серый цвет',
        ],

    ];


    /*
     * Создаем настройки и color picker
     * для каждого цвета.
     */

    foreach ($color_settings as $key => $color) {

        $setting_id = "mytheme_{$key}_color";


        /*
         * Setting
         */

        $wp_customize->add_setting($setting_id, [
            'default'           => $color['default'],
            'transport'         => 'refresh',
            'sanitize_callback' => 'sanitize_hex_color',
        ]);


        /*
         * Color Picker
         */

        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                "{$setting_id}_control",
                [
                    'label'    => $color['label'],
                    'section'  => 'colors',
                    'settings' => $setting_id,
                ]
            )
        );
    }
}

add_action('customize_register', 'eshop_customize_colors_register');


/**
 * =========================================================
 * 5. ПОЛУЧЕНИЕ НАСТРОЕК ТЕМЫ
 * =========================================================
 *
 * Собираем все динамические настройки темы
 * в одном месте.
 *
 * @return array
 */
function eshop_get_theme_settings()
{
    return [

        /*
         * -------------------------------------------------
         * Цвета
         * -------------------------------------------------
         */

        'primary' => get_theme_mod(
            'mytheme_black_color',
            '#000000'
        ),


        'primary' => get_theme_mod(
            'mytheme_primary_color',
            '#0073aa'
        ),

        'secondary' => get_theme_mod(
            'mytheme_secondary_color',
            '#005177'
        ),

        'accent' => get_theme_mod(
            'mytheme_accent_color',
            '#d54e21'
        ),

        'grey' => get_theme_mod(
            'mytheme_grey_color',
            '#656464'
        ),


        /*
         * -------------------------------------------------
         * Основной контейнер
         * -------------------------------------------------
         */

        'container_width' => absint(
            get_theme_mod(
                'container_width',
                1920
            )
        ),

        'container_width_unit' => eshop_sanitize_container_unit(
            get_theme_mod(
                'container_width_unit',
                'px'
            )
        ),


        /*
         * -------------------------------------------------
         * Фиксированный контейнер
         * -------------------------------------------------
         */

        'fixed_container_width' => absint(
            get_theme_mod(
                'fixed_container_width',
                1080
            )
        ),

        'fixed_container_width_unit' => eshop_sanitize_container_unit(
            get_theme_mod(
                'fixed_container_width_unit',
                'px'
            )
        ),


        /*
         * -------------------------------------------------
         * Padding
         * -------------------------------------------------
         */

        'container_padding' => absint(
            get_theme_mod(
                'container_padding',
                20
            )
        ),

    ];
}


/**
 * =========================================================
 * 6. CSS-ПЕРЕМЕННЫЕ — FRONTEND
 * =========================================================
 *
 * Выводим динамические CSS-переменные в <head>.
 */
function eshop_output_custom_properties()
{
    $settings = eshop_get_theme_settings();


    /*
     * Формируем значения ширины вместе
     * с выбранной единицей измерения.
     */

    $container_width =
        $settings['container_width']
        . $settings['container_width_unit'];

    $fixed_container_width =
        $settings['fixed_container_width']
        . $settings['fixed_container_width_unit'];


    /*
     * Экранируем значения.
     */

    $black = esc_attr($settings['black']);
    $primary = esc_attr($settings['primary']);
    $secondary = esc_attr($settings['secondary']);
    $accent = esc_attr($settings['accent']);
    $grey = esc_attr($settings['grey']);

    $container_width = esc_attr($container_width);
    $fixed_container_width = esc_attr($fixed_container_width);
    $container_padding = esc_attr($settings['container_padding']);


    /*
     * Выводим CSS.
     */

    echo '<style id="eshop-theme-custom-properties">

        :root {

            /* Цвета темы */

             --theme-color-black: ' . $black . ';
            --theme-color-primary: ' . $primary . ';
            --theme-color-secondary: ' . $secondary . ';
            --theme-color-accent: ' . $accent . ';
            --theme-color-grey: ' . $grey . ';


            /* Контейнеры */

            --container-width: ' . $container_width . ';
            --fixed-container-width: ' . $fixed_container_width . ';
            --container-padding: ' . $container_padding . 'px;

        }

    </style>';
}

add_action('wp_head', 'eshop_output_custom_properties');


/**
 * =========================================================
 * 7. CSS-ПЕРЕМЕННЫЕ — GUTENBERG
 * =========================================================
 *
 * Gutenberg работает внутри iframe, поэтому передаем
 * CSS-переменные непосредственно в редактор.
 */
function eshop_editor_custom_properties()
{
    $settings = eshop_get_theme_settings();


    /*
     * Формируем ширину контейнеров
     * вместе с единицами измерения.
     */

    $container_width =
        $settings['container_width']
        . $settings['container_width_unit'];

    $fixed_container_width =
        $settings['fixed_container_width']
        . $settings['fixed_container_width_unit'];


    /*
     * Формируем CSS.
     */

    $css = '
        :root {

            /* Цвета темы */

             --theme-color-black: ' . esc_attr($settings['black']) . ';
            --theme-color-primary: ' . esc_attr($settings['primary']) . ';
            --theme-color-secondary: ' . esc_attr($settings['secondary']) . ';
            --theme-color-accent: ' . esc_attr($settings['accent']) . ';
            --theme-color-grey: ' . esc_attr($settings['grey']) . ';


            /* Контейнеры */

            --container-width: ' . esc_attr($container_width) . ';
            --fixed-container-width: ' . esc_attr($fixed_container_width) . ';
            --container-padding: ' . esc_attr($settings['container_padding']) . 'px;


            /* Gutenberg colors */

            --wp--preset--color--black: ' . esc_attr($settings['black']) . ';
            --wp--preset--color--primary: ' . esc_attr($settings['primary']) . ';
            --wp--preset--color--secondary: ' . esc_attr($settings['secondary']) . ';
            --wp--preset--color--accent: ' . esc_attr($settings['accent']) . ';
            --wp--preset--color--grey: ' . esc_attr($settings['grey']) . ';

        }
    ';


    /*
     * Передаем CSS в Gutenberg.
     */

    wp_add_inline_style(
        'wp-block-library',
        $css
    );
}

add_action(
    'enqueue_block_editor_assets',
    'eshop_editor_custom_properties'
);

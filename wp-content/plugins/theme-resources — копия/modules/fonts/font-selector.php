<?php

/**
 * TYPOGRAPHY MODULE VIA FOLDER SCAN (PRO VERSION)
 */

if (!defined('ABSPATH')) exit;

// ==============================
// 1. Админ-меню
// ==============================
add_action('admin_menu', function () {

    add_submenu_page(
        'theme-resources',
        'Типографика',
        'Типографика',
        'manage_options',
        'theme-resources-fonts',
        'render_plugin_fonts_page'
    );
});

// ==============================
// 2. Регистрация настроек
// ==============================
add_action('admin_init', function () {
    register_setting('plugin_fonts_group', 'plugin_fonts_selection');
});

// ==============================
// 3. Сканирование шрифтов
// ==============================
function plugin_scan_fonts()
{
    $base = THEME_RESOURCES_PATH . 'assets/fonts/';
    $families = [];

    $files = glob($base . '*.{woff2,woff,ttf,otf}', GLOB_BRACE);
    if (!$files) return [];

    foreach ($files as $file) {
        $filename = basename($file);
        $family = preg_replace('/[-_](thin|light|regular|medium|bold|semibold|extrabold|black|italic).*$/i', '', $filename);
        $families[$family][] = [
            'file' => $filename,
            'url'  => '/wp-content/plugins/theme-resources/assets/fonts/' . $filename,
        ];
    }

    return $families;
}

// ==============================
// 4. Определение веса и стиля
// ==============================
function plugin_detect_font_meta($filename)
{
    $name = strtolower($filename);

    $weight = 400;
    $style  = 'normal';

    if (strpos($name, 'thin') !== false) $weight = 100;
    elseif (strpos($name, 'extralight') !== false) $weight = 200;
    elseif (strpos($name, 'light') !== false) $weight = 300;
    elseif (strpos($name, 'medium') !== false) $weight = 500;
    elseif (strpos($name, 'semibold') !== false) $weight = 600;
    elseif (strpos($name, 'bold') !== false) $weight = 700;
    elseif (strpos($name, 'extrabold') !== false) $weight = 800;
    elseif (strpos($name, 'black') !== false) $weight = 900;

    if (strpos($name, 'italic') !== false) {
        $style = 'italic';
    }

    return [$weight, $style];
}

// ==============================
// 5. Админка
// ==============================
function render_plugin_fonts_page()
{
    $fonts = plugin_scan_fonts();
    $options = get_option('plugin_fonts_selection', []);

    $sections = [
        'heading' => 'Заголовки H1–H3',
        'body' => 'Основной текст',
        'accent' => 'Акцентный текст',
    ];
?>
    <div class="wrap">
        <h1>Типографика темы</h1>

        <form method="post" action="options.php">
            <?php settings_fields('plugin_fonts_group'); ?>

            <?php foreach ($sections as $key => $label): ?>
                <h2><?php echo esc_html($label); ?></h2>

                <!-- select шрифта для секции -->
                <select name="plugin_fonts_selection[<?php echo $key; ?>][family]"
                    class="font-family-select"
                    data-section="<?php echo esc_attr($key); ?>">
                    <option value="">— выбрать —</option>
                    <?php foreach ($fonts as $family => $files): ?>
                        <option value="<?php echo esc_attr($family); ?>"
                            <?php selected($options[$key]['family'] ?? '', $family); ?>>
                            <?php echo esc_html(str_replace(['-', '_'], ' ', $family)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- блок чекбоксов для начертаний этой секции -->
                <div class="font-weights-wrapper">
                    <?php foreach ($fonts as $family => $files): ?>
                        <div class="font-weights"
                            data-family="<?php echo esc_attr($family); ?>"
                            data-section="<?php echo esc_attr($key); ?>"
                            style="display:none; margin-top:5px;">
                            <?php foreach ($files as $font):
                                [$weight, $style] = plugin_detect_font_meta($font['file']);
                                $value = $font['file'];
                            ?>
                                <label style="display:block; margin:4px 0;">
                                    <input type="checkbox"
                                        name="plugin_fonts_selection[<?php echo $key; ?>][weights][]"
                                        value="<?php echo esc_attr($value); ?>"
                                        <?php checked(in_array($value, $options[$key]['weights'] ?? [])); ?>>
                                    <?php echo $weight . ($style === 'italic' ? ' Italic' : ''); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <?php submit_button(); ?>
        </form>
    </div>

    <script>
        document.querySelectorAll('.font-family-select').forEach(select => {
            const section = select.dataset.section;

            function update() {
                document.querySelectorAll(`.font-weights[data-section="${section}"]`).forEach(div => div.style.display = 'none');
                const active = document.querySelector(`.font-weights[data-section="${section}"][data-family="${select.value}"]`);
                if (active) active.style.display = 'block';
            }
            select.addEventListener('change', update);
            update();
        });
    </script>

<?php
}

// ==============================
// 6. Генерация CSS
// ==============================
add_action('update_option_plugin_fonts_selection', 'plugin_generate_fonts_css');

function plugin_generate_fonts_css()
{
    $options = get_option('plugin_fonts_selection', []);
    $fonts   = plugin_scan_fonts();

    if (!$options || !$fonts) return;

    $upload_dir = wp_upload_dir();
    $dir = $upload_dir['basedir'] . '/theme-resources/';

    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }

    // === собираем уникальные файлы ===
    $used_fonts = [];

    foreach ($options as $data) {
        foreach ($data['weights'] ?? [] as $file) {
            $used_fonts[$file] = true;
        }
    }

    $css = '';

    // === генерируем @font-face ===
    foreach ($fonts as $family => $family_fonts) {

        foreach ($family_fonts as $font) {

            if (!isset($used_fonts[$font['file']])) continue;

            [$weight, $style] = plugin_detect_font_meta($font['file']);

            $ext = pathinfo($font['file'], PATHINFO_EXTENSION);

            $formats = [
                'woff2' => 'woff2',
                'woff'  => 'woff',
                'ttf'   => 'truetype',
                'otf'   => 'opentype',
            ];

            $format = $formats[$ext] ?? 'woff2';
            $css_family = str_replace(['-', '_'], ' ', $family);

            $css .= "@font-face{
                font-family:'{$css_family}';
                src:url('{$font['url']}') format('{$format}');
                font-weight:{$weight};
                font-style:{$style};
                font-display:swap;
            }";
        }
    }

    // === применение ===
    foreach ($options as $section => $data) {

        if (empty($data['family'])) continue;

        $family = str_replace(['-', '_'], ' ', $data['family']);

        switch ($section) {
            case 'heading':
                $css .= "h1,h2,h3{font-family:'{$family}',sans-serif;}";
                break;
            case 'body':
                $css .= "body{font-family:'{$family}',sans-serif;}";
                break;
            case 'accent':
                $css .= ".accent,.button,strong{font-family:'{$family}',sans-serif;}";
                break;
        }
    }

    if (is_writable($dir)) {
        file_put_contents($dir . 'fonts.css', $css);
    }
}

// ==============================
// 7. Подключение CSS
// ==============================
add_action('wp_enqueue_scripts', function () {

    $upload_dir = wp_upload_dir();
    $css_file = $upload_dir['basedir'] . '/theme-resources/fonts.css';

    if (file_exists($css_file)) {
        wp_enqueue_style(
            'theme-fonts',
            $upload_dir['baseurl'] . '/theme-resources/fonts.css',
            [],
            filemtime($css_file)
        );
    }
});

// ==============================
// 8. Preload шрифтов
// ==============================
add_action('wp_head', function () {

    $options = get_option('plugin_fonts_selection', []);
    $fonts   = plugin_scan_fonts();

    if (!$options || !$fonts) return;

    $map = [];

    foreach ($fonts as $family) {
        foreach ($family as $font) {
            $map[$font['file']] = $font['url'];
        }
    }

    foreach ($options as $data) {
        foreach ($data['weights'] ?? [] as $file) {

            if (empty($map[$file])) continue;

            $type = 'font/woff2';
            if (str_ends_with($file, '.woff')) $type = 'font/woff';
            if (str_ends_with($file, '.ttf')) $type = 'font/ttf';

            echo "<link rel='preload' href='{$map[$file]}' as='font' type='{$type}' crossorigin>";
        }
    }
});

// ==============================
// 9. Подключение CSS в админке
// ==============================
add_action('admin_enqueue_scripts', function () {

    $upload_dir = wp_upload_dir();
    $css_file = $upload_dir['basedir'] . '/theme-resources/fonts.css';

    if (file_exists($css_file)) {
        wp_enqueue_style(
            'theme-fonts-admin',
            $upload_dir['baseurl'] . '/theme-resources/fonts.css',
            [],
            filemtime($css_file)
        );
    }
});

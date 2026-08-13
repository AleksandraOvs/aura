<?php

// ================================
// Поле изображения для pa_czvet
// ================================

add_action('pa_czvet_add_form_fields', function () {
?>
    <div class="form-field">
        <label>Изображение</label>
        <input type="hidden" name="czvet_image" id="czvet_image">
        <button class="button upload_image_button">Загрузить</button>
        <div id="czvet_preview"></div>
    </div>
<?php
});

add_action('pa_czvet_edit_form_fields', function ($term) {
    $image = get_term_meta($term->term_id, 'czvet_image', true);
?>
    <tr class="form-field">
        <th><label>Изображение</label></th>
        <td>
            <input type="hidden" name="czvet_image" id="czvet_image" value="<?php echo esc_attr($image); ?>">
            <button class="button upload_image_button">Загрузить</button>
            <div id="czvet_preview">
                <?php if ($image) echo wp_get_attachment_image($image, 'thumbnail'); ?>
            </div>
        </td>
    </tr>
<?php
});

add_action('created_pa_czvet', 'save_czvet_image');
add_action('edited_pa_czvet', 'save_czvet_image');

function save_czvet_image($term_id)
{
    if (isset($_POST['czvet_image'])) {
        update_term_meta($term_id, 'czvet_image', intval($_POST['czvet_image']));
    }
}


// ================================
// Media uploader
// ================================

add_action('admin_footer', function () {
?>
    <script>
        jQuery(function($) {

            let frame;

            $(document).on('click', '.upload_image_button', function(e) {
                e.preventDefault();

                if (frame) return frame.open();

                frame = wp.media({
                    title: 'Выбрать изображение',
                    button: {
                        text: 'Использовать'
                    },
                    multiple: false
                });

                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $('#czvet_image').val(attachment.id);
                    $('#czvet_preview').html('<img src="' + attachment.url + '" style="max-width:100px;">');
                });

                frame.open();
            });

        });
    </script>
<?php
});


// ================================
// Универсальный вывод вариаций
// ================================

add_filter('woocommerce_dropdown_variation_attribute_options_html', 'custom_variations_as_divs', 20, 2);

function custom_variations_as_divs($html, $args)
{
    $attribute = $args['attribute'];
    $product   = $args['product'];
    $options   = $args['options'];

    if (empty($options)) return $html;

    $selected = $args['selected'] ?: '';

    // дефолтные значения товара
    $default_attributes = $product->get_default_attributes();

    if (!$selected && isset($default_attributes[$attribute])) {
        $selected = $default_attributes[$attribute];
    }

    $terms = wc_get_product_terms($product->get_id(), $attribute, ['fields' => 'all']);

    ob_start();

    echo '<div class="wc-custom-attribute" data-attribute_name="attribute_' . esc_attr($attribute) . '">';

    foreach ($terms as $term) {

        if (!in_array($term->slug, $options)) continue;

        $active = ($selected === $term->slug) ? 'active' : '';

        if ($attribute === 'pa_czvet') {

            $image_id = get_term_meta($term->term_id, 'czvet_image', true);
            $image = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';

            echo '<div class="wc-attr-item czvet-item ' . $active . '" data-value="' . esc_attr($term->slug) . '">';

            if ($image) {
                echo '<img src="' . esc_url($image) . '" alt="">';
            }

            echo '<span>' . esc_html($term->name) . '</span>';
            echo '</div>';
        } else {

            echo '<div class="wc-attr-item generic-item ' . $active . '" data-value="' . esc_attr($term->slug) . '">';
            echo esc_html($term->name);
            echo '</div>';
        }
    }

    echo '</div>';

    return ob_get_clean() . $html;
}


// ================================
// JS логика + дефолты + цена
// ================================

add_action('wp_footer', function () {
?>
    <script>
        jQuery(function($) {

            function updateSelect($wrap, value) {
                const attr = $wrap.data('attribute_name');
                const $select = $('select[name="' + attr + '"]');

                $select.val(value).trigger('change');
            }

            $(document).on('click', '.wc-attr-item', function() {

                const $item = $(this);
                const $wrap = $item.closest('.wc-custom-attribute');

                $wrap.find('.wc-attr-item').removeClass('active');
                $item.addClass('active');

                updateSelect($wrap, $item.data('value'));
            });

            $('.wc-custom-attribute').each(function() {

                const $wrap = $(this);
                const $active = $wrap.find('.wc-attr-item.active');

                if ($active.length) {
                    updateSelect($wrap, $active.data('value'));
                }
            });

        });
    </script>

    <style>
        /* скрываем все WooCommerce селекты атрибутов */
        .single-product select[name^="attribute_"] {
            display: none !important;
        }

        .variations tbody {
            display: flex;
            align-items: flex-start;
            gap: 40px;
        }

        .variations tbody tr {
            max-width: calc(50% - 20px);
        }

        .variations tbody tr {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .woocommerce div.product form.cart .variations th {
            border: 0;
            line-height: 100%;
        }

        .variations tbody tr td.value .wc-custom-attribute {
            display: flex;
            gap: 10px;
            margin: 0;
        }

        /* контейнер атрибутов */
        .wc-custom-attribute {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin: 10px 0;
        }

        /* элементы */
        .wc-attr-item {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            user-select: none;
            text-align: center;
            flex-direction: column;
        }

        .wc-attr-item span {
            font-size: 12px;
        }

        /* hover */
        .wc-attr-item:hover {
            border-color: #000;
        }

        /* active */
        /* .wc-attr-item.active {
            border-color: #000;
            background: #f5f5f5;
        } */

        /* цветовые варианты */
        .czvet-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            margin-right: 0;
            border-radius: 100%;
        }

        .wc-attr-item.czvet-item.active img {
            border: 2px solid var(--theme-color-accent);
        }

        .wc-attr-item.czvet-item.active span {
            font-weight: 600;
        }

        .wc-attr-item.generic-item {
            padding: 5px;
            line-height: 1;
            border: 2px solid #cacaca;
            border-radius: 3px;
        }

        .wc-attr-item.generic-item.active {
            border-color: var(--theme-color-accent);
        }

        .woocommerce div.product form.cart .reset_variations {
            display: none !important;
        }

        @media (max-width: 480px) {

            .variations tbody {
                gap: 20px;
                flex-direction: column;
            }

            .variations tbody tr {
                /* flex-direction: row; */
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
<?php
});

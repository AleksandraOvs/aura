<?php

// Добавить поле при создании
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

// Добавить поле при редактировании
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

add_action('admin_footer', function () {
?>
    <script>
        jQuery(function($) {
            let frame;

            $(document).on('click', '.upload_image_button', function(e) {
                e.preventDefault();

                if (frame) {
                    frame.open();
                    return;
                }

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


add_filter('woocommerce_dropdown_variation_attribute_options_html', 'czvet_as_images', 10, 2);

function czvet_as_images($html, $args)
{

    // работаем только с нашим атрибутом
    if ($args['attribute'] !== 'pa_czvet') {
        return $html;
    }

    $options   = $args['options'];
    $product   = $args['product'];

    if (empty($options)) return $html;

    $terms = wc_get_product_terms($product->get_id(), 'pa_czvet', ['fields' => 'all']);

    ob_start();

    echo '<div class="czvet-variations" data-attribute_name="attribute_pa_czvet">';

    foreach ($terms as $term) {

        if (!in_array($term->slug, $options)) continue;

        $image_id = get_term_meta($term->term_id, 'czvet_image', true);
        $image = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';

        echo '<div class="czvet-item" data-value="' . esc_attr($term->slug) . '">';

        if ($image) {
            echo '<img src="' . esc_url($image) . '" alt="' . esc_attr($term->name) . '">';
        }

        echo '<span>' . esc_html($term->name) . '</span>';
        echo '</div>';
    }

    echo '</div>';

    // скрываем оригинальный select (но он нужен!)
    $html .= '<style>select[name="attribute_pa_czvet"]{display:none;}</style>';

    return ob_get_clean() . $html;
}

add_action('wp_footer', function () {
    if (!is_product()) return;
?>
    <script>
        jQuery(function($) {

            $('.czvet-item').on('click', function() {

                const value = $(this).data('value');
                const container = $(this).closest('.czvet-variations');
                const attribute = container.data('attribute_name');

                // активный класс
                container.find('.czvet-item').removeClass('active');
                $(this).addClass('active');

                // находим оригинальный select
                const select = $('select[name="' + attribute + '"]');

                select.val(value).trigger('change');
            });

        });
    </script>
    <style>
        select[name="attribute_pa_czvet"] {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .czvet-variations {
            display: flex;
            gap: 0;
        }

        .czvet-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 5px;
        }

        .variations_form.cart table.variations tbody tr label {
            position: absolute;
            display: none;
        }

        .woocommerce div.product form.cart.variations_form {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 10px;
        }

        .single_variation_wrap {
            display: flex;
            align-items: flex-start;
            gap: 40px;
        }

        .czvet-item img {
            width: 50px;
            height: 50px;
            display: block;
            overflow: hidden;
            border-radius: 100%;
            border: 2px solid rgba(0, 0, 0, .1);
            transition: all .5s linear;
        }

        .czvet-item.active img {
            border: 2px solid var(--theme-color-accent);
        }
    </style>
<?php
});

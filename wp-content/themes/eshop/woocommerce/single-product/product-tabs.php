<?php

global $product;

if (!$product instanceof WC_Product) {
    $product = wc_get_product(get_the_ID());
}

$product_id = $product ? $product->get_id() : 0;

// Получаем контент вкладок товара

//1. инструкция (поле в карточке товара)

$instructions = get_field('instruction');
$delivery_items = get_field('delivery_list', 'option');
$sum_faq_items = get_field('product_faq_list', 'option');
// FAQ из настроек сайта
$product_faq_items = get_field('products_faq_list', 'option');

// FAQ конкретного товара
$product_faq_items_product = get_field('product_faq_items');

$guarantee_content = get_field('guarantee_text', 'option');
?>

<div class="container">

    <div class="product-tabs">
        <div class="product-tabs__nav">
            <button class="product-tabs__btn active" data-tab="chars">Описание</button>
            <button class="product-tabs__btn" data-tab="reviews">Отзывы</button>
            <button class="product-tabs__btn" data-tab="instructions">Инструкция</button>
            <button class="product-tabs__btn" data-tab="delivery">Доставка</button>
            <button class="product-tabs__btn" data-tab="product-faq">Частые вопросы</button>
            <button class="product-tabs__btn" data-tab="guarantee">Гарантия и ремонт</button>
        </div>

        <div class="product-tabs__content">
            <!-- Описание и характеристики -->
            <div class="product-tabs__pane active" data-tab="chars">
                <?php
                /**
                 * =========================================================
                 * ОПИСАНИЕ ТОВАРА
                 * =========================================================
                 */
                $description = $product->get_description();

                if (!empty($description)) :
                ?>
                    <div class="product-description">
                        <?php echo apply_filters('the_content', $description); ?>
                    </div>
                <?php endif; ?>


                <?php
                /**
                 * =========================================================
                 * ГРУППИРОВАННЫЕ АТРИБУТЫ
                 * =========================================================
                 */

                $attribute_groups = get_field('product_attribute_groups', 'option');

                if (!empty($attribute_groups)) :

                    echo '<h3>Характеристики</h3>';

                    // Флаг — есть ли вообще характеристики у товара
                    $has_attributes = false;

                    ob_start();

                    foreach ($attribute_groups as $group) :

                        $group_title = $group['product_attribute_group_title'] ?? '';
                        $attributes  = $group['product_attribute_group_attributes'] ?? [];

                        if (empty($attributes)) {
                            continue;
                        }

                        $group_attributes = [];

                        foreach ($attributes as $attribute_row) :

                            $taxonomy = $attribute_row['attribute'] ?? '';

                            if (empty($taxonomy)) {
                                continue;
                            }

                            // Получаем значение атрибута у текущего товара
                            $value = $product->get_attribute($taxonomy);

                            // Если атрибут у товара не заполнен — пропускаем
                            if ($value === '') {
                                continue;
                            }

                            $label = wc_attribute_label($taxonomy);

                            $group_attributes[] = [
                                'label' => $label,
                                'value' => $value,
                            ];

                        endforeach;


                        // Если в этой группе нет заполненных атрибутов — группу не выводим
                        if (empty($group_attributes)) {
                            continue;
                        }

                        $has_attributes = true;
                ?>

                        <div class="product-attributes__group">

                            <?php if (!empty($group_title)) : ?>
                                <h3 class="product-attributes__group-title">
                                    <?php echo esc_html($group_title); ?>
                                </h3>
                            <?php endif; ?>

                            <div class="product-attributes__list">

                                <?php foreach ($group_attributes as $attribute) : ?>

                                    <div class="product-attributes__item">

                                        <div class="product-attributes__label">
                                            <?php echo esc_html($attribute['label']); ?>
                                        </div>

                                        <div class="product-attributes__value">
                                            <?php echo wp_kses_post($attribute['value']); ?>
                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php endforeach;

                    $attributes_html = ob_get_clean();

                    if ($has_attributes) :
                    ?>
                        <div class="product-attributes">
                            <?php echo $attributes_html; ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
            <!-- Отзывы (премодерация, зарегистрированные пользователи)-->
            <div class="product-tabs__pane " data-tab="reviews">
                Здесь будут отзывы
            </div>

            <!-- Инструкция (файлы/текст для отдельного товара) -->
            <div class="product-tabs__pane " data-tab="instructions">
                <?php
                $instructions = get_field('instruction');
                ?>

                <?php if ($instructions): ?>

                    <div class="product-instructions">

                        <?php foreach ($instructions as $instruction): ?>

                            <?php
                            $icon = $instruction['instruction_icon'] ?? '';
                            $file = $instruction['instruction_file'] ?? '';
                            $text = $instruction['instruction_text'] ?? '';
                            ?>

                            <div class="product-instructions__item">

                                <?php if ($text): ?>

                                    <div class="product-instructions__text">
                                        <?= wp_kses_post($text); ?>
                                    </div>

                                <?php endif; ?>

                                <?php if ($file): ?>

                                    <?php
                                    /*
                     * Получаем URL и название файла.
                     */
                                    if (is_array($file)) {

                                        $file_url = $file['url'] ?? '';
                                        $file_name = $file['title']
                                            ?? $file['filename']
                                            ?? 'Скачать файл';
                                    } elseif (is_numeric($file)) {

                                        $file_url = wp_get_attachment_url($file);
                                        $file_name = get_the_title($file);
                                    } else {

                                        $file_url = $file;
                                        $file_name = 'Скачать файл';
                                    }
                                    ?>


                                    <?php if ($file_url): ?>

                                        <a
                                            href="<?= esc_url($file_url); ?>"
                                            class="product-instructions__file"
                                            target="_blank"
                                            rel="noopener">

                                            <?php if ($icon) { ?>

                                                <?php
                                                if (is_array($icon)) {
                                                    $icon_url = $icon['url'] ?? '';
                                                    $icon_alt = $icon['alt'] ?? '';
                                                } elseif (is_numeric($icon)) {
                                                    $icon_url = wp_get_attachment_image_url(
                                                        $icon,
                                                        'full'
                                                    );
                                                    $icon_alt = get_post_meta(
                                                        $icon,
                                                        '_wp_attachment_image_alt',
                                                        true
                                                    );
                                                } else {
                                                    $icon_url = $icon;
                                                    $icon_alt = '';
                                                }
                                                ?>

                                                <?php if ($icon_url): ?>

                                                    <img
                                                        src="<?= esc_url($icon_url); ?>"
                                                        alt="<?= esc_attr($icon_alt); ?>"
                                                        class="product-instructions__icon">

                                                <?php endif; ?>

                                            <?php } else {
                                            ?>
                                                <img
                                                    src="<?php echo get_stylesheet_directory_uri() ?>/imgs/svg/download.svg"
                                                    alt="<?= esc_attr($icon_alt); ?>"
                                                    class="product-instructions__icon">
                                            <?php
                                            } ?>


                                            <span class="product-instructions__file-name">
                                                <?= esc_html($file_name); ?>
                                            </span>

                                        </a>

                                    <?php endif; ?>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


            </div>
            <!-- Доставка (общая информация) -->
            <div class="product-tabs__pane " data-tab="delivery">
                <?php if ($delivery_items): ?>
                    <ul class="delivery-items__list">
                        <?php foreach ($delivery_items as $delivery_item):
                            $delivery_content = $delivery_item['delivery_list_item'] ?? '';

                            echo '<li class="delivery-items__list__item">';
                            echo wp_kses_post($delivery_content);
                            echo '</li>';


                        endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <!-- Частые вопросы (общие + для отдельного товара) -->
            <div class="product-tabs__pane " data-tab="product-faq">
                <?php if ($product_faq_items || $product_faq_items_product): ?>

                    <ul class="faq-items__list">

                        <?php
                        /*
         * -----------------------------------------------------
         * Общие FAQ из настроек сайта
         * -----------------------------------------------------
         */
                        if ($product_faq_items):
                            foreach ($product_faq_items as $product_faq_item):

                                $question = $product_faq_item['products_faq_question'] ?? '';
                                $answer   = $product_faq_item['products_faq_answer'] ?? '';

                                if (!$question) {
                                    continue;
                                }
                        ?>

                                <li class="faq-items__item">

                                    <button
                                        type="button"
                                        class="faq-items__question"
                                        aria-expanded="false">
                                        <span class="faq-items__question__text">
                                            <?= esc_html($question); ?>
                                        </span>

                                        <span class="faq-items__question__icon">

                                        </span>
                                    </button>

                                    <div class="faq-items__answer">
                                        <?= wp_kses_post($answer); ?>
                                    </div>

                                </li>

                            <?php
                            endforeach;
                        endif;


                        /*
         * -----------------------------------------------------
         * FAQ текущего товара
         * -----------------------------------------------------
         */
                        if ($product_faq_items_product):
                            foreach ($product_faq_items_product as $product_faq_item):

                                $question = $product_faq_item['product_faq_item_question'] ?? '';
                                $answer   = $product_faq_item['product_faq_item_answer'] ?? '';

                                if (!$question) {
                                    continue;
                                }
                            ?>

                                <li class="faq-items__item">

                                    <button
                                        type="button"
                                        class="faq-items__question"
                                        aria-expanded="false">
                                        <span class="faq-items__question__text">
                                            <?= esc_html($question); ?>
                                        </span>

                                        <span class="faq-items__question__icon">

                                        </span>
                                    </button>

                                    <div class="faq-items__answer">
                                        <?= wp_kses_post($answer); ?>
                                    </div>

                                </li>

                        <?php
                            endforeach;
                        endif;
                        ?>

                    </ul>

                <?php endif; ?>
            </div>

            <!-- Гарантия и ремонт (общая информация) -->
            <div class="product-tabs__pane " data-tab="guarantee">
                <?php
                if ($guarantee_content) {
                    echo '<div class="guarantee__inner">';
                    echo wp_kses_post($guarantee_content);
                    echo '</div>';
                }
                ?>
            </div>



        </div>
    </div>
</div>
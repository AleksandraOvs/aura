<?php
$custom_delivery_regions = get_field('delivery_region', 'option');
?>

<?php if (!empty($custom_delivery_regions)) : ?>

    <div class="custom-delivery-methods">

        <?php foreach ($custom_delivery_regions as $region_index => $region) : ?>

            <?php
            $region_title = $region['custom_delivery_title'] ?? '';
            $methods      = $region['custom_delivery_method'] ?? [];

            ?>

            <div class="custom-delivery-region">

                <?php if ($region_title) : ?>

                    <h3 class="custom-delivery-region__title">
                        <?php echo esc_html($region_title); ?>
                    </h3>

                <?php endif; ?>


                <?php if (!empty($methods)) : ?>
                    <?php //print_r($methods);
                    ?>

                    <div class="custom-delivery-methods__list">

                        <?php foreach ($methods as $method_index => $method) : ?>

                            <?php
                            $logo = $method['custom_delivery_method_logo'] ?? [];
                            $description = $method['custom_delivery_method_description'] ?? '';
                            $method_name = $method['custom_delivery_method_name'] ?? '';
                            //  $delivery_types = $method['delivery_types'] ?? [];

                            $method_id = 'custom-delivery-method-' . $region_index . '-' . $method_index;
                            ?>

                            <div class="custom-delivery-method">

                                <label
                                    class="custom-delivery-method__label"
                                    for="<?php echo esc_attr($method_id); ?>">

                                    <input
                                        type="radio"
                                        id="<?php echo esc_attr($method_id); ?>"
                                        name="custom_delivery_method"
                                        value="<?php echo esc_attr($method_id); ?>">

                                    <div class="custom-delivery-method__label__content">

                                        <?php if (!empty($logo['ID'])) : ?>

                                            <span class="custom-delivery-method__logo">

                                                <?php
                                                echo wp_get_attachment_image(
                                                    $logo['ID'],
                                                    'medium',
                                                    false,
                                                    [
                                                        'alt' => $method_name,
                                                    ]
                                                );
                                                ?>

                                            </span>

                                        <?php endif; ?>


                                        <?php if ($description) : ?>

                                            <span class="custom-delivery-method__description">
                                                <?php echo wp_kses_post($description); ?>
                                            </span>

                                        <?php endif; ?>

                                        <?php
                                        $delivery_types = $method['delivery_types'] ?? [];
                                        ?>

                                        <?php if (!empty($delivery_types)) : ?>

                                            <div class="custom-delivery-method__types">

                                                <?php foreach ($delivery_types as $type_index => $type) : ?>

                                                    <?php
                                                    $type_title = $type['delivery_type'] ?? '';

                                                    if (!$type_title) {
                                                        continue;
                                                    }

                                                    $type_id = $method_id . '-type-' . $type_index;
                                                    ?>

                                                    <label
                                                        class="custom-delivery-type"
                                                        for="<?php echo esc_attr($type_id); ?>">

                                                        <input
                                                            type="checkbox"
                                                            id="<?php echo esc_attr($type_id); ?>"
                                                            name="custom_delivery_type[]"
                                                            value="<?php echo esc_attr($type_title); ?>">

                                                        <span class="custom-delivery-type__checkbox"></span>

                                                        <span class="custom-delivery-type__title">
                                                            <?php echo esc_html($type_title); ?>
                                                        </span>

                                                    </label>

                                                <?php endforeach; ?>

                                            </div>

                                        <?php endif; ?>


                                    </div>

                                </label>



                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>
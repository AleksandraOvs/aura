<?php

namespace Supplier_Importer\Admin;

use Supplier_Importer\Import\Mapping_Discovery_Repository;

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Mappings
{
    private $discovery_repository;

    public function __construct()
    {
        $this->discovery_repository = new Mapping_Discovery_Repository();
    }

    public function render()
    {
        $supplier = 'maytoni';

        $discovery = $this->discovery_repository->get($supplier);

        $categories = $discovery['categories'] ?? [];
        $attributes = $discovery['attributes'] ?? [];
        $updated_at = $discovery['updated_at'] ?? '';

?>
        <div class="supplier-importer__card">

            <h2>Сопоставления поставщика</h2>

            <p>
                Здесь будут отображаться категории и параметры,
                обнаруженные в файле поставщика.
            </p>

            <p>
                <strong>Поставщик:</strong> Maytoni
            </p>

            <?php if (empty($updated_at)) : ?>

                <div class="notice notice-warning inline">
                    <p>
                        Данные ещё не обнаружены.
                        Сначала необходимо обработать YML-файл поставщика.
                    </p>
                </div>

            <?php else : ?>

                <p class="description">
                    Последнее обновление:
                    <?php echo esc_html($updated_at); ?>
                </p>

            <?php endif; ?>

        </div>

        <div class="supplier-importer__card">

            <h2>Категории</h2>

            <?php if (empty($categories)) : ?>

                <p>Категории пока не обнаружены.</p>

            <?php else : ?>

                <table class="widefat striped">

                    <thead>
                        <tr>
                            <th>Категория поставщика</th>
                            <th>Товаров</th>
                            <th>Категория сайта</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($categories as $category) : ?>

                            <tr>
                                <td>
                                    <?php echo esc_html($category['source'] ?? ''); ?>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        number_format_i18n(
                                            (int) ($category['count'] ?? 0)
                                        )
                                    ); ?>
                                </td>

                                <td>
                                    <em>Сопоставление пока не настроено</em>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

        <div class="supplier-importer__card">

            <h2>Атрибуты</h2>

            <?php if (empty($attributes)) : ?>

                <p>Атрибуты пока не обнаружены.</p>

            <?php else : ?>

                <?php foreach ($attributes as $attribute) : ?>

                    <div class="supplier-importer__mapping-item">

                        <h3>
                            <?php echo esc_html($attribute['source'] ?? ''); ?>
                        </h3>

                        <p class="description">
                            Товаров с этим параметром:
                            <?php echo esc_html(
                                number_format_i18n(
                                    (int) ($attribute['count'] ?? 0)
                                )
                            ); ?>
                        </p>

                        <?php
                        $values = $attribute['values'] ?? [];
                        ?>

                        <?php if (!empty($values)) : ?>

                            <table class="widefat striped">

                                <thead>
                                    <tr>
                                        <th>Значение поставщика</th>
                                        <th>Товаров</th>
                                        <th>Значение сайта</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($values as $value) : ?>

                                        <tr>
                                            <td>
                                                <?php echo esc_html($value['value'] ?? ''); ?>
                                            </td>

                                            <td>
                                                <?php echo esc_html(
                                                    number_format_i18n(
                                                        (int) ($value['count'] ?? 0)
                                                    )
                                                ); ?>
                                            </td>

                                            <td>
                                                <em>Сопоставление пока не настроено</em>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>
<?php
    }
}

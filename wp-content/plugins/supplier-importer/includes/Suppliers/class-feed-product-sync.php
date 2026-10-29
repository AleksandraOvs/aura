<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

use Supplier_Importer\Product\Product_Meta;

class Feed_Product_Sync
{

    /**
     * Количество товаров за один запрос.
     */
    private const BATCH_SIZE = 100;
    /**
     * Метаданные товаров.
     *
     * @var Product_Meta
     */
    private $product_meta;

    public function __construct()
    {
        $this->product_meta = new Product_Meta();
    }

    /**
     * Обработать товары поставщика, отсутствующие в текущем фиде,
     * и восстановить публикацию товаров, ранее снятых синхронизацией.
     *
     * Вызывать только после успешного импорта всех записей.
     *
     * @param string $supplier_id ID поставщика.
     * @param string $run_id      Уникальный ID запуска.
     *
     * @return array
     */
    public function sync_missing_products($supplier_id, $run_id)
    {
        $supplier_id = sanitize_key($supplier_id);
        $run_id = trim((string) $run_id);

        if ($supplier_id === '' || $run_id === '') {
            throw new \InvalidArgumentException(
                'Для синхронизации необходимы ID поставщика и запуска.'
            );
        }

        $result = [
            'checked'     => 0,
            'unpublished' => 0,
            'republished' => 0,
        ];

        global $wpdb;

        $last_product_id = 0;

        while (true) {


            $sql = $wpdb->prepare(
                "SELECT p.ID
    FROM {$wpdb->posts} AS p
    INNER JOIN {$wpdb->postmeta} AS pm
        ON pm.post_id = p.ID
    WHERE p.ID > %d
        AND p.post_type IN (%s, %s)
        AND p.post_status IN (%s, %s, %s, %s)
        AND pm.meta_key = %s
        AND pm.meta_value = %s
    GROUP BY p.ID
    ORDER BY p.ID ASC
    LIMIT %d",
                $last_product_id,
                'product',
                'product_variation',
                'publish',
                'draft',
                'pending',
                'private',
                '_supplier',
                $supplier_id,
                self::BATCH_SIZE
            );

            $product_ids = $wpdb->get_col($sql);

            if ($wpdb->last_error !== '') {
                throw new \RuntimeException(
                    'Ошибка получения товаров для синхронизации: '
                        . $wpdb->last_error
                );
            }

            if (empty($product_ids)) {
                break;
            }


            foreach ($product_ids as $product_id) {
                $product_id = (int) $product_id;
                $last_product_id = $product_id;
                $product = wc_get_product($product_id);

                if (!$product) {
                    continue;
                }

                // Дополнительная проверка владельца товара.
                if (
                    $this->product_meta->get_supplier($product)
                    !== $supplier_id
                ) {
                    continue;
                }

                $result['checked']++;

                $last_run = $this->product_meta->get_feed_run($product);

                $was_unpublished =
                    $this->product_meta->was_unpublished_by_sync($product);

                /*
                 * Товар встретился в текущем фиде.
                 */
                if ($last_run === $run_id) {
                    if (!$was_unpublished) {
                        continue;
                    }

                    // Возвращаем публикацию только товарам,
                    // которые ранее сняла именно наша синхронизация.
                    if ($product->get_status() !== 'publish') {
                        $product->set_status('publish');
                        $product->save();

                        if ($product->get_status() !== 'publish') {
                            throw new \RuntimeException(
                                sprintf(
                                    'Не удалось опубликовать товар ID %d.',
                                    $product_id
                                )
                            );
                        }

                        $result['republished']++;
                    }

                    $this->product_meta->set_unpublished_by_sync(
                        $product,
                        false
                    );

                    if ($this->product_meta->was_unpublished_by_sync($product)) {
                        throw new \RuntimeException(
                            sprintf(
                                'Не удалось удалить маркер снятия с публикации для товара ID %d.',
                                $product_id
                            )
                        );
                    }

                    continue;
                }

                /*
                 * Товар отсутствует в текущем фиде.
                 * Снимаем с публикации только опубликованные товары.
                 */
                if ($product->get_status() !== 'publish') {
                    continue;
                }

                $product->set_status('draft');
                $product->save();

                if ($product->get_status() !== 'draft') {
                    throw new \RuntimeException(
                        sprintf(
                            'Не удалось снять с публикации товар ID %d.',
                            $product_id
                        )
                    );
                }

                $this->product_meta->set_unpublished_by_sync(
                    $product,
                    true
                );

                if (!$this->product_meta->was_unpublished_by_sync($product)) {
                    // Не оставляем товар в черновиках без маркера синхронизации.
                    $product->set_status('publish');
                    $product->save();

                    if ($product->get_status() !== 'publish') {
                        throw new \RuntimeException(
                            sprintf(
                                'Не удалось сохранить маркер и восстановить публикацию товара ID %d.',
                                $product_id
                            )
                        );
                    }

                    throw new \RuntimeException(
                        sprintf(
                            'Не удалось сохранить маркер снятия с публикации для товара ID %d. Публикация восстановлена.',
                            $product_id
                        )
                    );
                }

                $result['unpublished']++;
            }
        }

        return $result;
    }
}

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
     * Обработать одну порцию товаров поставщика.
     *
     * @param string $supplier_id   ID поставщика.
     * @param string $run_id        ID текущего запуска.
     * @param int    $last_product_id Последний обработанный ID.
     * @param int    $batch_size    Размер порции.
     *
     * @return array
     */
    public function sync_missing_products_batch(
        $supplier_id,
        $run_id,
        $last_product_id = 0,
        $batch_size = null
    ) {
        $supplier_id = sanitize_key($supplier_id);
        $run_id = trim((string) $run_id);

        $last_product_id = max(
            0,
            (int) $last_product_id
        );

        $batch_size = max(
            1,
            (int) ($batch_size ?: self::BATCH_SIZE)
        );

        if ($supplier_id === '' || $run_id === '') {
            throw new \InvalidArgumentException(
                'Для синхронизации необходимы ID поставщика и запуска.'
            );
        }

        $result = [
            'checked'     => 0,
            'unpublished' => 0,
            'republished' => 0,
            'last_product_id' => $last_product_id,
            'finished'    => false,
        ];

        global $wpdb;

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
            $batch_size
        );

        $product_ids = $wpdb->get_col($sql);

        if ($wpdb->last_error !== '') {
            throw new \RuntimeException(
                'Ошибка получения товаров для синхронизации: '
                    . $wpdb->last_error
            );
        }

        /*
         * Если получено меньше записей, следующей порции нет.
         *
         * Если получено ровно batch_size записей,
         * для определения окончания потребуется ещё один запрос.
         */
        $result['finished'] = count($product_ids) < $batch_size;

        foreach ($product_ids as $product_id) {
            $product_id = (int) $product_id;

            /*
             * Курсор обновляем до обработки товара.
             * При успешном завершении порции он будет сохранён
             * вместе с результатом.
             */
            $result['last_product_id'] = $product_id;

            $product = wc_get_product($product_id);

            if (!$product) {
                continue;
            }

            /*
             * Дополнительная проверка владельца товара.
             */
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
             * Товар присутствует в текущем фиде.
             */
            if ($last_run === $run_id) {
                /*
                 * Восстанавливаем публикацию только тех товаров,
                 * которые ранее сняла наша синхронизация.
                 */
                if ($was_unpublished) {
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

                    if (
                        !$this->product_meta->set_unpublished_by_sync(
                            $product,
                            false
                        )
                    ) {
                        throw new \RuntimeException(
                            sprintf(
                                'Не удалось удалить маркер снятия с публикации для товара ID %d.',
                                $product_id
                            )
                        );
                    }

                    $result['republished']++;
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

            /*
             * Сначала сохраняем маркер синхронизации.
             */
            if (
                !$this->product_meta->set_unpublished_by_sync(
                    $product,
                    true
                )
            ) {
                throw new \RuntimeException(
                    sprintf(
                        'Не удалось сохранить маркер снятия с публикации для товара ID %d.',
                        $product_id
                    )
                );
            }

            /*
             * Затем переводим товар в черновик.
             */
            $product->set_status('draft');
            $product->save();

            if ($product->get_status() !== 'draft') {
                $this->product_meta->set_unpublished_by_sync(
                    $product,
                    false
                );

                throw new \RuntimeException(
                    sprintf(
                        'Не удалось снять с публикации товар ID %d.',
                        $product_id
                    )
                );
            }

            $result['unpublished']++;
        }

        return $result;
    }

    /**
     * Синхронизировать все товары поставщика.
     *
     * Старый синхронный интерфейс оставлен для совместимости.
     * Для пакетного импорта использовать sync_missing_products_batch().
     *
     * @param string $supplier_id ID поставщика.
     * @param string $run_id      ID текущего запуска.
     *
     * @return array
     */
    public function sync_missing_products($supplier_id, $run_id)
    {
        $result = [
            'checked'     => 0,
            'unpublished' => 0,
            'republished' => 0,
        ];

        $last_product_id = 0;

        do {
            $batch = $this->sync_missing_products_batch(
                $supplier_id,
                $run_id,
                $last_product_id,
                self::BATCH_SIZE
            );

            $result['checked'] += $batch['checked'];
            $result['unpublished'] += $batch['unpublished'];
            $result['republished'] += $batch['republished'];

            $last_product_id = $batch['last_product_id'];
        } while (!$batch['finished']);

        return $result;
    }
}

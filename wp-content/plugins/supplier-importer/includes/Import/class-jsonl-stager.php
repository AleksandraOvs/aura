<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Jsonl_Stager
{
    /**
     * Преобразовать XML/YML в JSONL.
     *
     * Каждая запись товара сохраняется отдельной строкой.
     *
     * @param string $source_path Путь к исходному XML/YML.
     * @param string $target_path Путь к staging-файлу.
     *
     * @return int Количество сохранённых товаров.
     */
    public function stage($source_path, $target_path)
    {
        $reader = new Xml_Reader($source_path, 'offer');

        $handle = fopen($target_path, 'wb');

        if (!$handle) {
            throw new \RuntimeException(
                'Не удалось создать staging-файл.'
            );
        }

        $total = 0;

        try {
            $reader->open();

            while (true) {
                $product = $reader->read();

                if ($product === null) {
                    break;
                }

                $json = json_encode(
                    $product,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );

                if ($json === false) {
                    throw new \RuntimeException(
                        'Ошибка преобразования товара в JSON: '
                            . json_last_error_msg()
                    );
                }

                $written = fwrite($handle, $json . "\n");

                if ($written === false || $written !== strlen($json) + 1) {
                    throw new \RuntimeException(
                        'Не удалось записать товар в staging-файл.'
                    );
                }

                $total++;
            }
        } catch (\Throwable $e) {
            fclose($handle);
            $reader->close();

            @unlink($target_path);

            throw $e;
        }

        fclose($handle);
        $reader->close();

        return $total;
    }
}

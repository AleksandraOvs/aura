<?php

namespace SupplierImporter\Parsers;

use SupplierImporter\Normalizer\ValueCleaner;

class YmlParser
{
    /**
     * Разбирает YML/XML-файл и возвращает товары из <offer>.
     *
     * Парсер не знает ничего о конкретном поставщике.
     */
    public function parse(string $file): array
    {
        if (!file_exists($file)) {
            throw new \RuntimeException(
                'YML/XML-файл не найден: ' . $file
            );
        }

        if (!is_readable($file)) {
            throw new \RuntimeException(
                'YML/XML-файл недоступен для чтения: ' . $file
            );
        }

        libxml_use_internal_errors(true);

        $xml = simplexml_load_file(
            $file,
            'SimpleXMLElement',
            LIBXML_NOCDATA | LIBXML_NONET
        );

        if ($xml === false) {
            $errors = libxml_get_errors();

            libxml_clear_errors();

            $message = 'Не удалось разобрать XML/YML-файл.';

            if (!empty($errors)) {
                $message .= ' ' . trim($errors[0]->message);
            }

            throw new \RuntimeException($message);
        }

        $offers = $xml->xpath('//offer');

        if ($offers === false) {
            $offers = [];
        }


        /*
 * Получаем категории из:
 *
 * <shop>
 *     <categories>
 *         <category id="152">...</category>
 *     </categories>
 * </shop>
 */
        $categories = $this->parseCategories($xml);


        $rows = [];

        foreach ($offers as $offer) {

            $row = $this->parseOffer($offer);

            /*
     * categoryId из offer:
     *
     * <categoryId>18</categoryId>
     *
     * связываем с нашим справочником категорий.
     */
            if (
                isset($row['categoryId']) &&
                isset($categories[$row['categoryId']])
            ) {
                $row['_category'] = $categories[$row['categoryId']];
            }

            $rows[] = $row;
        }


        return [
            'categories' => $categories,
            'rows'       => $rows,
            'count'      => count($rows),
        ];
    }

    /**
     * Предпросмотр первых товаров.
     */
    public function preview(string $file, int $limit = 10): array
    {
        $result = $this->parse($file);

        return [
            'rows' => array_slice($result['rows'], 0, $limit),
            'count' => $result['count'],
        ];
    }

    private function parseCategories(
        \SimpleXMLElement $xml
    ): array {

        $result = [];

        $categories = $xml->xpath('//shop/categories/category');

        if ($categories === false) {
            return [];
        }

        foreach ($categories as $category) {

            $id = isset($category['id'])
                ? (string) $category['id']
                : null;

            if ($id === null || $id === '') {
                continue;
            }

            $parent_id = isset($category['parentId'])
                ? (string) $category['parentId']
                : null;

            $result[$id] = [
                'id' => $id,
                'parent_id' => $parent_id,
                'name' => ValueCleaner::clean(
                    (string) $category
                ),
            ];
        }

        return $result;
    }

    /**
     * Преобразует один <offer> в обычный PHP-массив.
     */
    private function parseOffer(\SimpleXMLElement $offer): array
    {
        $result = [];

        /*
         * Атрибуты самого <offer>
         *
         * Например:
         *
         * <offer
         *     id="13854"
         *     available="true"
         * >
         */
        foreach ($offer->attributes() as $name => $value) {
            $result[(string) $name] = ValueCleaner::clean(
                (string) $value
            );
        }

        /*
         * Обычные дочерние элементы:
         *
         * <name>...</name>
         * <price>...</price>
         * <vendor>...</vendor>
         * <picture>...</picture>
         */
        foreach ($offer->children() as $child) {

            $name = $child->getName();

            /*
             * <param name="...">...</param>
             */
            if ($name === 'param') {
                $this->parseParam($child, $result);
                continue;
            }

            /*
             * Если элемент повторяется,
             * превращаем значение в массив.
             *
             * Например:
             *
             * <picture>1.jpg</picture>
             * <picture>2.jpg</picture>
             *
             * станет:
             *
             * 'picture' => [
             *     '1.jpg',
             *     '2.jpg'
             * ]
             */
            $value = $this->parseNodeValue($child);

            if (!array_key_exists($name, $result)) {
                $result[$name] = $value;
                continue;
            }

            if (!is_array($result[$name])) {
                $result[$name] = [
                    $result[$name]
                ];
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    $result[$name][] = $item;
                }
            } else {
                $result[$name][] = $value;
            }
        }

        return $result;
    }

    /**
     * Обрабатывает:
     *
     * <param name="Мощность">8</param>
     */
    private function parseParam(
        \SimpleXMLElement $param,
        array &$result
    ): void {

        $name = isset($param['name'])
            ? ValueCleaner::clean((string) $param['name'])
            : null;

        $value = ValueCleaner::clean(
            (string) $param
        );

        if ($name === null || $value === null) {
            return;
        }

        if (!isset($result['param'])) {
            $result['param'] = [];
        }

        /*
         * Если параметр встретился один раз:
         *
         * 'Мощность' => '8'
         *
         * Если несколько раз:
         *
         * 'Цвет' => [
         *     'Белый',
         *     'Черный'
         * ]
         */
        if (!array_key_exists($name, $result['param'])) {
            $result['param'][$name] = $value;
            return;
        }

        if (!is_array($result['param'][$name])) {
            $result['param'][$name] = [
                $result['param'][$name]
            ];
        }

        $result['param'][$name][] = $value;
    }

    /**
     * Получает значение XML-ноды.
     */
    private function parseNodeValue(
        \SimpleXMLElement $node
    ) {

        /*
         * Если внутри нет дочерних элементов —
         * обычная строка.
         */
        if ($node->count() === 0) {
            return ValueCleaner::clean(
                (string) $node
            );
        }

        /*
         * Если внутри есть вложенные элементы,
         * превращаем их в массив.
         */
        $result = [];

        foreach ($node->children() as $child) {
            $name = $child->getName();
            $value = $this->parseNodeValue($child);

            if (!array_key_exists($name, $result)) {
                $result[$name] = $value;
                continue;
            }

            if (!is_array($result[$name])) {
                $result[$name] = [
                    $result[$name]
                ];
            }

            $result[$name][] = $value;
        }

        return $result;
    }
}

<?php

namespace SupplierImporter\Parsers;

use SupplierImporter\Normalizer\ValueCleaner;

class YmlParser
{
    /**
     * Разбирает весь YML/XML-файл и возвращает товары из <offer>.
     *
     * Используется для полного разбора файла.
     *
     * @param string $file
     *
     * @return array
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
            $message = $this->getXmlErrorMessage();

            libxml_clear_errors();

            throw new \RuntimeException(
                $message
            );
        }

        $categories = $this->parseCategories($xml);

        $offers = $xml->xpath('//offer');

        if ($offers === false) {
            $offers = [];
        }

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
                $row['_category'] =
                    $categories[$row['categoryId']];
            }

            $rows[] = $row;
        }

        libxml_clear_errors();

        return [
            'categories' => $categories,
            'rows'       => $rows,
            'count'      => count($rows),
        ];
    }

    /**
     * Потоковый порционный разбор XML/YML.
     *
     * В отличие от parse(), здесь весь XML не загружается
     * через simplexml_load_file().
     *
     * XMLReader читает документ последовательно и в память
     * одновременно попадает только текущий <offer>.
     *
     * @param string $file
     * @param int    $offset
     * @param int    $limit
     *
     * @return array
     */
    public function parseChunk(
        string $file,
        int $offset,
        int $limit
    ): array {

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

        $offset = max(0, $offset);
        $limit  = max(1, $limit);

        /*
         * XMLReader должен быть доступен.
         */
        if (!class_exists('\XMLReader')) {
            throw new \RuntimeException(
                'Расширение PHP XMLReader не установлено.'
            );
        }

        /*
         * Сначала получаем категории.
         *
         * Категории находятся в начале YML-файла
         * и нужны для связывания categoryId с нашими
         * данными категории.
         *
         * Важно:
         * parseCategoriesStream() не загружает весь XML
         * через SimpleXML.
         */
        $categories = $this->parseCategoriesStream($file);

        /*
         * Открываем XMLReader для потокового чтения offers.
         */
        $reader = new \XMLReader();

        libxml_use_internal_errors(true);

        $opened = $reader->open(
            $file,
            null,
            LIBXML_NOCDATA | LIBXML_NONET
        );

        if (!$opened) {

            $message = $this->getXmlErrorMessage();

            $reader->close();

            libxml_clear_errors();

            throw new \RuntimeException(
                'Не удалось открыть XML/YML-файл. ' . $message
            );
        }

        $rows = [];

        /*
         * Количество найденных offer.
         *
         * Считаем только реальные <offer>,
         * пустые/служебные элементы не учитываем.
         */
        $current_offer = 0;

        while ($reader->read()) {

            /*
             * Нас интересуют только элементы <offer>.
             *
             * Имя может быть:
             *
             * offer
             *
             * либо namespace:offer
             *
             * поэтому дополнительно смотрим localName.
             */
            if (
                $reader->nodeType !== \XMLReader::ELEMENT ||
                $reader->localName !== 'offer'
            ) {
                continue;
            }

            /*
             * Если offer находится до нужного offset,
             * просто пропускаем его.
             */
            if ($current_offer < $offset) {

                $current_offer++;

                /*
                 * Переходим на следующий offer.
                 *
                 * readOuterXML() позволяет не разбирать
                 * ненужный offer через SimpleXML.
                 */
                $reader->readOuterXML();

                continue;
            }

            /*
             * Получили нужный offer.
             */
            $offer_xml = $reader->readOuterXML();

            if ($offer_xml === '') {

                $current_offer++;

                continue;
            }

            /*
             * Превращаем только один текущий offer
             * в SimpleXMLElement.
             *
             * В памяти находится только этот offer,
             * а не весь YML-файл.
             */
            libxml_use_internal_errors(true);

            $offer = simplexml_load_string(
                $offer_xml,
                'SimpleXMLElement',
                LIBXML_NOCDATA | LIBXML_NONET
            );

            if ($offer === false) {

                $current_offer++;

                /*
                 * Ошибку конкретного offer не превращаем
                 * в падение всего импорта.
                 *
                 * Просто продолжаем читать XML.
                 */
                libxml_clear_errors();

                continue;
            }

            $row = $this->parseOffer($offer);

            /*
             * categoryId из offer связываем
             * с нашим справочником категорий.
             */
            if (
                isset($row['categoryId']) &&
                isset($categories[$row['categoryId']])
            ) {
                $row['_category'] =
                    $categories[$row['categoryId']];
            }

            $rows[] = $row;

            $current_offer++;

            /*
             * Получили необходимое количество товаров.
             */
            if (count($rows) >= $limit) {
                break;
            }
        }

        $reader->close();

        libxml_clear_errors();

        /*
         * Если получили меньше limit,
         * значит достигли конца файла.
         */
        $finished = count($rows) < $limit;

        $next_offset = $offset + count($rows);

        return [
            'categories'  => $categories,
            'rows'        => $rows,
            'count'       => count($rows),
            'offset'      => $offset,
            'limit'       => $limit,
            'next_offset' => $next_offset,
            'finished'    => $finished,
            'next'        => !$finished,
        ];
    }

    /**
     * Предпросмотр первых товаров.
     *
     * Использует потоковый парсер.
     */
    public function preview(
        string $file,
        int $limit = 10
    ): array {

        $result = $this->parseChunk(
            $file,
            0,
            $limit
        );

        return [
            'rows'  => $result['rows'],
            'count' => $result['count'],
        ];
    }

    /**
     * Потоковое чтение категорий.
     *
     * Не загружает весь XML через SimpleXML.
     *
     * Ищет:
     *
     * <category id="152">...</category>
     *
     * и сохраняет:
     *
     * [
     *     '152' => [
     *         'id' => '152',
     *         'parent_id' => null,
     *         'name' => '...'
     *     ]
     * ]
     *
     * @param string $file
     *
     * @return array
     */
    private function parseCategoriesStream(
        string $file
    ): array {

        $result = [];

        $reader = new \XMLReader();

        libxml_use_internal_errors(true);

        $opened = $reader->open(
            $file,
            null,
            LIBXML_NOCDATA | LIBXML_NONET
        );

        if (!$opened) {

            $message = $this->getXmlErrorMessage();

            $reader->close();

            libxml_clear_errors();

            throw new \RuntimeException(
                'Не удалось открыть XML/YML-файл для чтения категорий. '
                    . $message
            );
        }

        while ($reader->read()) {

            if (
                $reader->nodeType !== \XMLReader::ELEMENT ||
                $reader->localName !== 'category'
            ) {
                continue;
            }

            /*
             * Читаем только сам category.
             */
            $category_xml = $reader->readOuterXML();

            if ($category_xml === '') {
                continue;
            }

            $category = simplexml_load_string(
                $category_xml,
                'SimpleXMLElement',
                LIBXML_NOCDATA | LIBXML_NONET
            );

            if ($category === false) {

                libxml_clear_errors();

                continue;
            }

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
                'id'        => $id,
                'parent_id' => $parent_id,
                'name'      => ValueCleaner::clean(
                    (string) $category
                ),
            ];
        }

        $reader->close();

        libxml_clear_errors();

        return $result;
    }

    /**
     * Получает категории из SimpleXMLElement.
     *
     * Используется старым parse().
     *
     * @param \SimpleXMLElement $xml
     *
     * @return array
     */
    private function parseCategories(
        \SimpleXMLElement $xml
    ): array {

        $result = [];

        $categories = $xml->xpath(
            '//shop/categories/category'
        );

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
                'id'        => $id,
                'parent_id' => $parent_id,
                'name'      => ValueCleaner::clean(
                    (string) $category
                ),
            ];
        }

        return $result;
    }

    /**
     * Преобразует один <offer> в обычный PHP-массив.
     *
     * @param \SimpleXMLElement $offer
     *
     * @return array
     */
    private function parseOffer(
        \SimpleXMLElement $offer
    ): array {

        $result = [];

        /*
         * Атрибуты самого <offer>.
         *
         * Например:
         *
         * <offer
         *     id="13854"
         *     available="true"
         * >
         */
        foreach ($offer->attributes() as $name => $value) {

            $result[(string) $name] =
                ValueCleaner::clean(
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

                $this->parseParam(
                    $child,
                    $result
                );

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
            $value = $this->parseNodeValue(
                $child
            );

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
     *
     * @param \SimpleXMLElement $param
     * @param array             $result
     *
     * @return void
     */
    private function parseParam(
        \SimpleXMLElement $param,
        array &$result
    ): void {

        $name = isset($param['name'])
            ? ValueCleaner::clean(
                (string) $param['name']
            )
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
        if (!array_key_exists(
            $name,
            $result['param']
        )) {

            $result['param'][$name] = $value;

            return;
        }

        if (!is_array(
            $result['param'][$name]
        )) {

            $result['param'][$name] = [
                $result['param'][$name]
            ];
        }

        $result['param'][$name][] = $value;
    }

    /**
     * Получает значение XML-ноды.
     *
     * @param \SimpleXMLElement $node
     *
     * @return mixed
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

            $value = $this->parseNodeValue(
                $child
            );

            if (!array_key_exists(
                $name,
                $result
            )) {

                $result[$name] = $value;

                continue;
            }

            if (!is_array(
                $result[$name]
            )) {

                $result[$name] = [
                    $result[$name]
                ];
            }

            $result[$name][] = $value;
        }

        return $result;
    }

    /**
     * Получить текст первой ошибки libxml.
     *
     * @return string
     */
    private function getXmlErrorMessage(): string
    {
        $errors = libxml_get_errors();

        if (empty($errors)) {
            return 'Неизвестная ошибка XML.';
        }

        return trim(
            $errors[0]->message
        );
    }
}

<?php

namespace SupplierImporter\Normalizer;

use RuntimeException;

class ProductNormalizer
{
    /**
     * Нормализует одну строку поставщика
     *
     * @param array $row
     * @param array $config
     * @return array
     */
    public function normalize(array $row, array $config): array
    {
        $product = [
            'supplier'          => $config['supplier'] ?? '',
            'external_id'       => null,
            'sku'               => null,
            'barcode'           => null,
            'name'              => null,
            'description'       => null,
            'short_description' => null,

            'price'             => null,
            'old_price'         => null,
            'stock'             => null,
            'available'         => true,

            'brand'             => null,

            'categories'        => [],
            'images'            => [],
            'videos'            => [],
            'documents'         => [],

            'attributes'        => [],
            'meta'              => [],

            'source_url'        => null,
            'currency'          => null,
        ];

        /*
         * ---------------------------------------------------------
         * ОСНОВНЫЕ ПОЛЯ
         * ---------------------------------------------------------
         */

        $product['external_id'] = $this->getMappedValue(
            $row,
            $config,
            'external_id'
        );

        $product['sku'] = $this->getMappedValue(
            $row,
            $config,
            'sku'
        );

        /*
 * Если поставщик не передал external_id,
 * используем SKU как резервный идентификатор.
 */
        if (
            empty($product['external_id']) &&
            !empty($product['sku'])
        ) {
            $product['external_id'] = $product['sku'];
        }

        $product['barcode'] = $this->getMappedValue(
            $row,
            $config,
            'barcode'
        );

        $product['name'] = $this->getMappedValue(
            $row,
            $config,
            'name'
        );

        $product['description'] = $this->getMappedValue(
            $row,
            $config,
            'description'
        );

        $product['short_description'] = $this->getMappedValue(
            $row,
            $config,
            'short_description'
        );

        $product['brand'] = $this->getMappedValue(
            $row,
            $config,
            'brand'
        );

        $product['source_url'] = $this->getMappedValue(
            $row,
            $config,
            'source_url'
        );

        $product['currency'] = $this->getMappedValue(
            $row,
            $config,
            'currency'
        );

        /*
         * ---------------------------------------------------------
         * ЦЕНА
         * ---------------------------------------------------------
         */

        $price = $this->getMappedValue(
            $row,
            $config,
            'price'
        );

        if ($price !== null) {
            $product['price'] = ValueCleaner::number($price);
        }

        $oldPrice = $this->getMappedValue(
            $row,
            $config,
            'old_price'
        );

        if ($oldPrice !== null) {
            $product['old_price'] = ValueCleaner::number($oldPrice);
        }

        /*
         * ---------------------------------------------------------
         * ОСТАТОК
         * ---------------------------------------------------------
         */

        $stock = $this->getMappedValue(
            $row,
            $config,
            'stock'
        );

        if ($stock !== null) {
            $product['stock'] = ValueCleaner::number($stock);
        }

        /*
         * available может быть:
         *
         * - напрямую из колонки
         * - вычислен из stock
         * - задан статически в config
         */

        if (isset($config['fields']['available'])) {

            $available = $this->getMappedValue(
                $row,
                $config,
                'available'
            );

            if ($available !== null) {
                $boolean = ValueCleaner::boolean($available);

                if ($boolean !== null) {
                    $product['available'] = $boolean;
                }
            }
        }

        /*
         * Если available не задан напрямую,
         * но есть stock — определяем автоматически.
         */

        if (
            !isset($config['fields']['available']) &&
            $product['stock'] !== null
        ) {
            $product['available'] = $product['stock'] > 0;
        }

        /*
         * ---------------------------------------------------------
         * КАТЕГОРИИ
         * ---------------------------------------------------------
         */

        $product['categories'] = $this->getCategories(
            $row,
            $config
        );

        /*
         * ---------------------------------------------------------
         * ИЗОБРАЖЕНИЯ
         * ---------------------------------------------------------
         */

        $product['images'] = $this->getImages(
            $row,
            $config
        );

        /*
         * ---------------------------------------------------------
         * ВИДЕО
         * ---------------------------------------------------------
         */

        $product['videos'] = $this->getMedia(
            $row,
            $config,
            'videos'
        );

        /*
         * ---------------------------------------------------------
         * ДОКУМЕНТЫ
         * ---------------------------------------------------------
         */

        $product['documents'] = $this->getDocuments(
            $row,
            $config
        );

        /*
         * ---------------------------------------------------------
         * АТРИБУТЫ
         * ---------------------------------------------------------
         */

        $product['attributes'] = $this->getAttributes(
            $row,
            $config
        );

        /*
         * ---------------------------------------------------------
         * META
         * ---------------------------------------------------------
         */

        $product['meta'] = $this->getMeta(
            $row,
            $config
        );

        /*
         * ---------------------------------------------------------
         * ФИЛЬТРУЕМ ПУСТЫЕ СИСТЕМНЫЕ ПОЛЯ
         * ---------------------------------------------------------
         */

        foreach ($product as $key => $value) {

            if (
                $value === null ||
                $value === ''
            ) {
                continue;
            }

            if (
                is_array($value) &&
                empty($value)
            ) {
                continue;
            }
        }

        return $product;
    }

    /**
     * Получить значение поля согласно mapping.
     */
    private function getMappedValue(
        array $row,
        array $config,
        string $field
    ) {

        if (
            is_array($config['fields'][$field] ?? null) &&
            array_key_exists('value', $config['fields'][$field])
        ) {
            return ValueCleaner::clean(
                $config['fields'][$field]['value']
            );
        }

        if (!isset($config['fields'][$field])) {
            return null;
        }

        $mapping = $config['fields'][$field];

        if (is_string($mapping)) {
            return ValueCleaner::clean(
                $this->getValueByPath($row, $mapping)
            );
        }

        if (is_array($mapping)) {

            /*
         * Поддерживаем:
         *
         * [
         *     'source' => 'price',
         *     'type'   => 'number'
         * ]
         */
            $source = $mapping['source'] ?? null;

            if ($source === null) {
                return null;
            }

            $value = $this->getValueByPath(
                $row,
                $source
            );

            /*
         * Если XML содержит повторяющийся параметр:
         *
         * 'Цвет' => [
         *     'Белый',
         *     'Черный'
         * ]
         *
         * Для обычного поля берём первое значение.
         */
            if (is_array($value)) {
                $value = reset($value);
            }

            $type = $mapping['type'] ?? 'string';

            switch ($type) {

                case 'number':
                    return ValueCleaner::number($value);

                case 'boolean':
                    return ValueCleaner::boolean($value);

                case 'string':
                default:
                    return ValueCleaner::clean($value);
            }
        }

        return null;
    }

    private function getValueByPath(
        array $row,
        string $path
    ) {
        /*
     * Обычное поле:
     *
     * price
     * name
     * vendorCode
     */
        if (strpos($path, '.') === false) {
            return $row[$path] ?? null;
        }

        /*
     * Вложенное поле:
     *
     * param.Артикул
     * param.Мощность общая, Вт
     */
        $parts = explode('.', $path);

        $value = $row;

        foreach ($parts as $part) {

            if (!is_array($value)) {
                return null;
            }

            if (!array_key_exists($part, $value)) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }


    /**
     * Категории.
     */


    /**
     * Категории товара.
     *
     * Поддерживает:
     *
     * 1. Простой источник:
     *
     * 'categories' => [
     *     'source' => 'Категория',
     * ]
     *
     * 2. Строку с разделителем:
     *
     * 'categories' => [
     *     'source' => 'Категория',
     *     'separator' => '/',
     * ]
     *
     * 3. XML-категорию:
     *
     * 'categories' => [
     *     'source' => '_category',
     * ]
     *
     * где значение:
     *
     * [
     *     'id' => 18,
     *     'parent_id' => 152,
     *     'name' => 'Подвесные светильники'
     * ]
     *
     * 4. Callback.
     */
    private function getCategories(
        array $row,
        array $config
    ): array {

        /*
     * ---------------------------------------------------------
     * Получаем mapping категорий
     * ---------------------------------------------------------
     */

        $mapping = $config['categories'] ?? null;

        /*
     * Для совместимости разрешаем также:
     *
     * fields.categories
     */

        if ($mapping === null) {

            $mapping = $config['fields']['categories'] ?? null;
        }

        if ($mapping === null) {
            return [];
        }


        /*
     * ---------------------------------------------------------
     * CALLBACK
     * ---------------------------------------------------------
     */

        if (
            is_array($mapping) &&
            isset($mapping['callback']) &&
            is_callable($mapping['callback'])
        ) {

            $result = call_user_func(
                $mapping['callback'],
                $row
            );

            return $this->normalizeArray($result);
        }


        /*
     * ---------------------------------------------------------
     * SOURCE
     * ---------------------------------------------------------
     */

        if (
            is_array($mapping) &&
            isset($mapping['source'])
        ) {

            $source = $mapping['source'];

            $value = $this->getValueByPath(
                $row,
                $source
            );

            /*
         * Значение отсутствует.
         */

            if ($value === null) {
                return [];
            }


            /*
         * -----------------------------------------------------
         * XML-категория
         * -----------------------------------------------------
         *
         * Например Crystal Lux:
         *
         * [
         *     'id' => 18,
         *     'parent_id' => 152,
         *     'name' => 'Подвесные светильники'
         * ]
         */

            if (
                is_array($value) &&
                isset($value['name'])
            ) {

                $name = ValueCleaner::clean(
                    $value['name']
                );

                if ($name === null) {
                    return [];
                }

                return [
                    [
                        'id' => $value['id'] ?? null,
                        'parent_id' => $value['parent_id'] ?? null,
                        'name' => $name,
                    ]
                ];
            }


            /*
         * -----------------------------------------------------
         * Массив категорий
         * -----------------------------------------------------
         */

            if (is_array($value)) {

                $result = [];

                foreach ($value as $category) {

                    /*
                 * Если категория уже структурирована.
                 */

                    if (
                        is_array($category) &&
                        isset($category['name'])
                    ) {

                        $name = ValueCleaner::clean(
                            $category['name']
                        );

                        if ($name === null) {
                            continue;
                        }

                        $result[] = [
                            'id' => $category['id'] ?? null,
                            'parent_id' => $category['parent_id'] ?? null,
                            'name' => $name,
                        ];

                        continue;
                    }

                    /*
                 * Обычная строковая категория.
                 */

                    $category = ValueCleaner::clean(
                        $category
                    );

                    if ($category !== null) {
                        $result[] = $category;
                    }
                }

                return $result;
            }


            /*
         * -----------------------------------------------------
         * Обычная строка
         * -----------------------------------------------------
         */

            $value = ValueCleaner::clean($value);

            if ($value === null) {
                return [];
            }


            /*
         * -----------------------------------------------------
         * Разделитель
         * -----------------------------------------------------
         *
         * Denkirs:
         *
         * Свет для дома / Настенные бра
         *
         * =>
         *
         * [
         *     'Свет для дома',
         *     'Настенные бра'
         * ]
         */

            $separator = $mapping['separator'] ?? null;

            if (
                $separator !== null &&
                $separator !== ''
            ) {

                $parts = explode(
                    $separator,
                    $value
                );

                $result = [];

                foreach ($parts as $part) {

                    $part = ValueCleaner::clean(
                        $part
                    );

                    if ($part !== null) {
                        $result[] = $part;
                    }
                }

                return array_values(
                    array_unique($result)
                );
            }


            /*
         * Одна категория.
         */

            return [$value];
        }


        /*
     * ---------------------------------------------------------
     * Старый простой формат
     * ---------------------------------------------------------
     *
     * Например:
     *
     * 'categories' => 'Категория'
     */

        if (is_string($mapping)) {

            $value = $this->getValueByPath(
                $row,
                $mapping
            );

            $value = ValueCleaner::clean($value);

            if ($value === null) {
                return [];
            }

            return [$value];
        }


        return [];
    }


    /**
     * Атрибуты товара.
     */
    private function getAttributes(
        array $row,
        array $config
    ): array {

        if (
            !isset($config['attributes']) ||
            !is_array($config['attributes'])
        ) {
            return [];
        }

        $result = [];

        foreach (
            $config['attributes']
            as $attributeName => $mapping
        ) {

            /*
         * -------------------------------------------------
         * ПРОСТОЙ ФОРМАТ
         * -------------------------------------------------
         *
         * 'style' => 'стиль'
         */

            if (is_string($mapping)) {

                $value = $this->getValueByPath(
                    $row,
                    $mapping
                );

                /*
             * Если значение повторяется,
             * берём первое.
             */

                if (is_array($value)) {
                    $value = reset($value);
                }

                $value = ValueCleaner::clean($value);

                if ($value === null) {
                    continue;
                }

                $result[$attributeName] = $value;

                continue;
            }


            /*
         * -------------------------------------------------
         * РАСШИРЕННЫЙ ФОРМАТ
         * -------------------------------------------------
         *
         * [
         *     'source' => 'param.Стиль',
         *     'type'   => 'string'
         * ]
         */

            if (!is_array($mapping)) {
                continue;
            }

            $source = $mapping['source'] ?? null;

            if ($source === null) {
                continue;
            }

            /*
         * Используем getValueByPath(),
         * чтобы работать с вложенными значениями:
         *
         * param.Стиль
         * param.Вес
         * param.Мощность лампочек, Вт
         */

            $value = $this->getValueByPath(
                $row,
                $source
            );

            /*
         * XML может содержать несколько одинаковых
         * параметров.
         *
         * Для обычного атрибута берём первое значение.
         */

            if (is_array($value)) {
                $value = reset($value);
            }

            if ($value === null) {
                continue;
            }

            /*
         * Тип значения.
         */

            $type = $mapping['type'] ?? 'string';

            switch ($type) {

                case 'number':

                    $value = ValueCleaner::number(
                        $value
                    );

                    break;

                case 'boolean':

                    $value = ValueCleaner::boolean(
                        $value
                    );

                    break;

                case 'string':

                default:

                    $value = ValueCleaner::clean(
                        $value
                    );

                    break;
            }

            if ($value === null) {
                continue;
            }

            /*
         * -------------------------------------------------
         * ИГНОРИРОВАНИЕ НУЛЯ
         * -------------------------------------------------
         *
         * Например, у Maytoni:
         *
         * ЦветоваяТемпература = 0
         *
         * При этом реальные значения находятся
         * в ЦветоваяТемператураМин / Макс.
         *
         * Поэтому в конфиге можно указать:
         *
         * 'ignore_zero' => true
         *
         * Нули остальных характеристик при этом
         * не затрагиваются.
         */

            if (
                !empty($mapping['ignore_zero']) &&
                (
                    $value === 0 ||
                    $value === 0.0 ||
                    $value === '0'
                )
            ) {
                continue;
            }

            /*
         * Единица измерения.
         */

            if (
                isset($mapping['unit']) &&
                $mapping['unit'] !== ''
            ) {

                $result[$attributeName] = [
                    'value' => $value,
                    'unit'  => $mapping['unit'],
                ];
            } else {

                $result[$attributeName] = $value;
            }
        }

        return $result;
    }


    /**
     * Дополнительные meta-поля.
     */
    private function getMeta(
        array $row,
        array $config
    ): array {

        if (
            !isset($config['meta']) ||
            !is_array($config['meta'])
        ) {
            return [];
        }

        $result = [];

        foreach (
            $config['meta']
            as $metaName => $mapping
        ) {

            /*
         * -------------------------------------------------
         * ПРОСТОЙ ФОРМАТ
         * -------------------------------------------------
         */

            if (is_string($mapping)) {

                $value = $this->getValueByPath(
                    $row,
                    $mapping
                );

                if (is_array($value)) {
                    $value = reset($value);
                }

                $value = ValueCleaner::clean($value);
            }

            /*
         * -------------------------------------------------
         * РАСШИРЕННЫЙ ФОРМАТ
         * -------------------------------------------------
         */ elseif (is_array($mapping)) {

                $source = $mapping['source'] ?? null;

                if ($source === null) {
                    continue;
                }

                /*
             * ВАЖНО:
             *
             * Было:
             *
             * $row[$source] ?? null
             *
             * Теперь:
             *
             * getValueByPath()
             *
             * Поэтому работают:
             *
             * param.Код
             * param.Вес
             * param.Объём
             * param.ТН ВЭД
             */

                $value = $this->getValueByPath(
                    $row,
                    $source
                );

                if (is_array($value)) {
                    $value = reset($value);
                }

                $type = $mapping['type'] ?? 'string';

                switch ($type) {

                    case 'number':

                        $value = ValueCleaner::number(
                            $value
                        );

                        break;

                    case 'boolean':

                        $value = ValueCleaner::boolean(
                            $value
                        );

                        break;

                    default:

                        $value = ValueCleaner::clean(
                            $value
                        );

                        break;
                }
            } else {

                continue;
            }

            if ($value === null) {
                continue;
            }

            $result[$metaName] = $value;
        }

        return $result;
    }

    /**
     * Получение изображений.
     *
     * Поддерживает:
     *
     * Картинка
     * Картинка_2
     * Картинка_3
     * ...
     *
     * либо список полей в config.
     */
    private function getImages(
        array $row,
        array $config
    ): array {

        if (!isset($config['media']['images'])) {
            return [];
        }

        $mapping = $config['media']['images'];
        $result = [];

        /*
     * Явно указанные поля
     */
        if (
            isset($mapping['fields']) &&
            is_array($mapping['fields'])
        ) {

            foreach ($mapping['fields'] as $field) {

                $value = $this->getValueByPath(
                    $row,
                    $field
                );

                if ($value === null) {
                    continue;
                }

                /*
             * XML может вернуть массив picture.
             */
                if (is_array($value)) {

                    foreach ($value as $item) {

                        $urls = $this->splitMediaValue(
                            (string) $item,
                            $mapping
                        );

                        foreach ($urls as $url) {
                            $result[] = $url;
                        }
                    }

                    continue;
                }

                $urls = $this->splitMediaValue(
                    (string) $value,
                    $mapping
                );

                foreach ($urls as $url) {
                    $result[] = $url;
                }
            }
        }

        /*
     * Поля по регулярному выражению.
     */
        if (isset($mapping['pattern'])) {

            $pattern = $mapping['pattern'];

            foreach ($row as $key => $value) {

                if (!preg_match($pattern, $key)) {
                    continue;
                }

                if (is_array($value)) {
                    foreach ($value as $item) {

                        $urls = $this->splitMediaValue(
                            (string) $item,
                            $mapping
                        );

                        foreach ($urls as $url) {
                            $result[] = $url;
                        }
                    }

                    continue;
                }

                $urls = $this->splitMediaValue(
                    (string) $value,
                    $mapping
                );

                foreach ($urls as $url) {
                    $result[] = $url;
                }
            }
        }

        return $this->uniqueCleanValues($result);
    }

    /**
     * Видео или другие media-поля.
     */
    private function getMedia(
        array $row,
        array $config,
        string $type
    ): array {

        if (
            !isset($config['media'][$type])
        ) {
            return [];
        }

        $mapping = $config['media'][$type];

        $result = [];

        if (
            isset($mapping['fields']) &&
            is_array($mapping['fields'])
        ) {

            foreach ($mapping['fields'] as $field) {

                $value = ValueCleaner::clean(
                    $row[$field] ?? null
                );

                if ($value === null) {
                    continue;
                }

                $values = $this->splitMediaValue(
                    $value,
                    $mapping
                );

                foreach ($values as $item) {
                    $result[] = $item;
                }
            }
        }

        if (
            isset($mapping['pattern'])
        ) {

            foreach ($row as $key => $value) {

                if (
                    !preg_match(
                        $mapping['pattern'],
                        $key
                    )
                ) {
                    continue;
                }

                $value = ValueCleaner::clean($value);

                if ($value === null) {
                    continue;
                }

                $values = $this->splitMediaValue(
                    $value,
                    $mapping
                );

                foreach ($values as $item) {
                    $result[] = $item;
                }
            }
        }

        return $this->uniqueCleanValues($result);
    }

    /**
     * Документы товара.
     *
     * Результат:
     *
     * [
     *     'instruction' => 'https://...',
     *     '3d_model' => 'https://...',
     * ]
     */
    private function getDocuments(
        array $row,
        array $config
    ): array {

        if (
            !isset($config['media']['documents']) ||
            !is_array($config['media']['documents'])
        ) {
            return [];
        }

        $mapping = $config['media']['documents'];

        $result = [];

        foreach ($mapping as $documentKey => $documentMapping) {

            /*
         * -----------------------------------------------------
         * Простой формат:
         *
         * 'instruction' => 'Инструкция'
         * -----------------------------------------------------
         */

            if (is_string($documentMapping)) {

                $value = $this->getValueByPath(
                    $row,
                    $documentMapping
                );

                /*
             * Если каким-то поставщиком пришёл массив,
             * берём первое значение.
             */

                if (is_array($value)) {
                    $value = reset($value);
                }

                $value = ValueCleaner::clean($value);

                if ($value !== null) {
                    $result[$documentKey] = $value;
                }

                continue;
            }


            /*
         * -----------------------------------------------------
         * Расширенный формат:
         *
         * 'instruction' => [
         *     'source' => 'Инструкция'
         * ]
         * -----------------------------------------------------
         */

            if (is_array($documentMapping)) {

                $source = $documentMapping['source'] ?? null;

                if ($source === null) {
                    continue;
                }

                $value = $this->getValueByPath(
                    $row,
                    $source
                );

                if (is_array($value)) {
                    $value = reset($value);
                }

                $value = ValueCleaner::clean($value);

                if ($value !== null) {
                    $result[$documentKey] = $value;
                }
            }
        }

        return $result;
    }
    /**
     * Разделяет media-значение.
     *
     * Поддерживает:
     *
     * URL
     * URL;URL
     * URL,URL
     */
    private function splitMediaValue(
        $value,
        array $mapping = []
    ): array {

        if ($value === null) {
            return [];
        }

        /*
     * Если пришёл массив — обрабатываем каждый элемент отдельно.
     */
        if (is_array($value)) {

            $result = [];

            foreach ($value as $item) {

                $result = array_merge(
                    $result,
                    $this->splitMediaValue(
                        $item,
                        $mapping
                    )
                );
            }

            return $this->uniqueCleanValues($result);
        }

        $value = ValueCleaner::clean($value);

        if ($value === null) {
            return [];
        }

        /*
     * Если это полноценный URL — возвращаем его целиком.
     *
     * В URL могут быть:
     * ( )
     * пробелы в encoded-виде
     * тире
     * подчёркивания
     * точки
     *
     * Поэтому здесь НЕЛЬЗЯ обрезать URL регуляркой.
     */
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return [$value];
        }

        /*
     * Убираем Markdown:
     *
     * [текст](https://example.com/file.jpg)
     */
        $value = preg_replace(
            '/^\s*\[[^\]]*\]\((https?:\/\/.+)\)\s*$/u',
            '$1',
            $value
        );

        /*
     * Явно заданный разделитель.
     */
        $separator = $mapping['separator'] ?? null;

        if (
            $separator !== null &&
            $separator !== ''
        ) {

            $parts = explode(
                $separator,
                $value
            );
        } else {

            /*
         * Для обычных строк поддерживаем:
         *
         * ;
         * перевод строки
         */
            $parts = preg_split(
                '/[;\r\n]+/u',
                $value
            );
        }

        $result = [];

        foreach ($parts as $part) {

            $part = ValueCleaner::clean($part);

            if ($part === null) {
                continue;
            }

            /*
         * Иногда после разделения вокруг URL
         * могут остаться лишние пробелы.
         */
            $part = trim($part);

            if (
                filter_var(
                    $part,
                    FILTER_VALIDATE_URL
                )
            ) {
                $result[] = $part;
            }
        }

        return $this->uniqueCleanValues($result);
    }

    /**
     * Нормализует массив.
     */
    private function normalizeArray($value): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        return $this->uniqueCleanValues($value);
    }

    /**
     * Удаляет пустые значения и дубликаты.
     */
    private function uniqueCleanValues(
        array $values
    ): array {

        $result = [];

        foreach ($values as $value) {

            $value = ValueCleaner::clean($value);

            if ($value === null) {
                continue;
            }

            $result[] = $value;
        }

        return array_values(
            array_unique(
                $result,
                SORT_REGULAR
            )
        );
    }
}

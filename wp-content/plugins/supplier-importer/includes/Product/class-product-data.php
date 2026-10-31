<?php

namespace Supplier_Importer\Product;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;

class Product_Data
{
    private $data = [];

    /**
     * Обязательные поля нормализованного товара.
     */
    private const REQUIRED_FIELDS = [
        'supplier',
        'supplier_id',
        'sku',
        'name',
    ];

    /**
     * Поля, которые должны быть массивами.
     */
    private const ARRAY_FIELDS = [
        'images',
        'attributes',
        'meta',
    ];

    /**
     * Поля нормализованного товара.
     */
    private const DEFAULTS = [
        'supplier'    => '',
        'supplier_id' => '',
        'sku'         => '',
        'name'        => '',
        'type'        => '',
        'brand'       => '',
        'barcode'     => '',
        'category'    => '',
        'url'         => '',
        'description' => '',
        'price'       => null,
        'stock'       => null,
        'images'      => [],
        'attributes'  => [],
        'meta'        => [],
    ];

    /**
     * Создать Product_Data из массива.
     *
     * @param array $data
     *
     * @return self
     */
    public static function from_array($data)
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException(
                'Product_Data ожидает массив.'
            );
        }

        $product = new self();

        $product->data = array_merge(
            self::DEFAULTS,
            $data
        );

        $product->normalize();

        $product->validate();

        return $product;
    }

    /**
     * Нормализация значений.
     */
    private function normalize()
    {
        foreach ($this->data as $key => $value) {
            if (is_string($value)) {
                $this->data[$key] = trim($value);
            }
        }

        foreach (self::ARRAY_FIELDS as $field) {
            if (!is_array($this->data[$field])) {
                $this->data[$field] = [];
            }
        }

        if ($this->data['price'] !== null) {
            $this->data['price'] = $this->normalize_price(
                $this->data['price']
            );
        }

        if ($this->data['stock'] !== null) {
            $this->data['stock'] = $this->normalize_stock(
                $this->data['stock']
            );
        }

        if (!empty($this->data['images'])) {
            $this->data['images'] = array_values(
                array_unique(
                    array_filter(
                        $this->data['images'],
                        static function ($image) {
                            return is_string($image)
                                && trim($image) !== '';
                        }
                    )
                )
            );
        }
    }

    /**
     * Нормализация цены.
     *
     * @param mixed $value
     *
     * @return float|null
     */
    private function normalize_price($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace(
            ["\xC2\xA0", ' '],
            '',
            (string) $value
        );

        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            throw new InvalidArgumentException(
                'Некорректное значение цены: ' . $value
            );
        }

        $price = (float) $value;

        if (!is_finite($price)) {
            throw new InvalidArgumentException(
                'Цена выходит за допустимый числовой диапазон.'
            );
        }

        return $price;
    }

    /**
     * Нормализация остатка.
     *
     * @param mixed $value
     *
     * @return int|float|null
     */
    private function normalize_stock($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace(
            ["\xC2\xA0", ' '],
            '',
            (string) $value
        );

        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            throw new InvalidArgumentException(
                'Некорректное значение остатка: ' . $value
            );
        }

        $stock = (float) $value;

        if (!is_finite($stock)) {
            throw new InvalidArgumentException(
                'Остаток выходит за допустимый числовой диапазон.'
            );
        }

        if (floor($stock) === $stock) {
            return (int) $stock;
        }

        return $stock;
    }

    /**
     * Проверка обязательных полей.
     */
    private function validate()
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            $value = $this->data[$field] ?? null;

            if (
                !is_string($value)
                && !is_numeric($value)
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Обязательное поле "%s" должно быть строкой.',
                        $field
                    )
                );
            }

            if (trim((string) $value) === '') {
                throw new InvalidArgumentException(
                    sprintf(
                        'Обязательное поле "%s" не может быть пустым.',
                        $field
                    )
                );
            }
        }
    }

    /**
     * Получить значение поля.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return array_key_exists($key, $this->data)
            ? $this->data[$key]
            : $default;
    }

    /**
     * Проверить наличие поля.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Вернуть весь товар массивом.
     *
     * @return array
     */
    public function to_array()
    {
        return $this->data;
    }

    /**
     * SKU.
     *
     * @return string
     */
    public function get_sku()
    {
        return $this->data['sku'];
    }

    /**
     * Название.
     *
     * @return string
     */
    public function get_name()
    {
        return $this->data['name'];
    }

    /**
     * Поставщик.
     *
     * @return string
     */
    public function get_supplier()
    {
        return $this->data['supplier'];
    }

    /**
     * ID товара у поставщика.
     *
     * @return string
     */
    public function get_supplier_id()
    {
        return $this->data['supplier_id'];
    }

    /**
     * Цена.
     *
     * @return float|null
     */
    public function get_price()
    {
        return $this->data['price'];
    }

    /**
     * Остаток.
     *
     * @return int|float|null
     */
    public function get_stock()
    {
        return $this->data['stock'];
    }

    /**
     * Изображения.
     *
     * @return array
     */
    public function get_images()
    {
        return $this->data['images'];
    }

    /**
     * Атрибуты.
     *
     * @return array
     */
    public function get_attributes()
    {
        return $this->data['attributes'];
    }

    /**
     * Дополнительные данные.
     *
     * @return array
     */
    public function get_meta()
    {
        return $this->data['meta'];
    }

    /**
     * Проверка цены.
     *
     * Позволяет отличить:
     * null = поставщик цену не передал
     * 0    = поставщик реально передал нулевую цену
     */
    public function has_price()
    {
        return $this->data['price'] !== null;
    }

    /**
     * Проверка остатка.
     *
     * null = поставщик не передал остаток
     * 0    = товар реально имеет остаток 0
     */
    public function has_stock()
    {
        return $this->data['stock'] !== null;
    }
}

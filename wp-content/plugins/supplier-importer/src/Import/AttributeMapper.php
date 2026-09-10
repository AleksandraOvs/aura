<?php

namespace SupplierImporter\Import;

if (!defined('ABSPATH')) {
    exit;
}

class AttributeMapper
{
    /**
     * Соответствие нормализованных атрибутов
     * глобальным атрибутам WooCommerce.
     *
     * slug — slug таксономии БЕЗ pa_
     * name — человекочитаемое название атрибута
     */
    private array $map = [

        /*
         * Уже существующие атрибуты сайта
         */

        'collection' => [
            'slug' => 'collection',
            'name' => 'Коллекция',
        ],

        'diameter' => [
            'slug' => 'shirina-diametr',
            'name' => 'Ширина/диаметр',
        ],

        'width' => [
            'slug' => 'shirina-diametr',
            'name' => 'Ширина/диаметр',
        ],

        'length' => [
            'slug' => 'dlina-mm',
            'name' => 'Длина (мм)',
        ],

        'height' => [
            'slug' => 'vysota-mm',
            'name' => 'Высота (мм)',
        ],

        'mounting_hole_diameter' => [
            'slug' => 'diametr-mont-otv',
            'name' => 'Диаметр монтажного отверстия',
        ],

        'mounting_hole_length' => [
            'slug' => 'dlina-mont-otv',
            'name' => 'Длина монтажного отверстия',
        ],

        'mounting_hole_width' => [
            'slug' => 'shirina-mont-otv',
            'name' => 'Ширина монтажного отверстия',
        ],

        'shape' => [
            'slug' => 'forma',
            'name' => 'Форма',
        ],

        'mounting_hole_shape' => [
            'slug' => 'forma',
            'name' => 'Форма',
        ],

        'lamp_power' => [
            'slug' => 'moshhnost-lampochki-vt',
            'name' => 'Мощность лампочки, Вт',
        ],

        'power' => [
            'slug' => 'moshhnost-w',
            'name' => 'Мощность (W)',
        ],

        'voltage' => [
            'slug' => 'napryazhenie',
            'name' => 'Напряжение',
        ],

        'box_volume' => [
            'slug' => 'obem-korobki-m3',
            'name' => 'Объем коробки, м3',
        ],

        'manufacturer' => [
            'slug' => 'proizoditel',
            'name' => 'Производитель',
        ],

        'country' => [
            'slug' => 'strana-proishozhdenia',
            'name' => 'Страна происхождения',
        ],

        'mounting_method' => [
            'slug' => 'tip-krepleniya',
            'name' => 'Тип крепления',
        ],

        'mounting_type' => [
            'slug' => 'tip-krepleniya',
            'name' => 'Тип крепления',
        ],

        'lamp_type' => [
            'slug' => 'tip-lampochki',
            'name' => 'Тип лампочки',
        ],


        /*
         * Новые атрибуты.
         *
         * Если их нет в WooCommerce,
         * импортер создаст их автоматически.
         */

        'style' => [
            'slug' => 'style',
            'name' => 'Стиль',
        ],

        'base' => [
            'slug' => 'base',
            'name' => 'Цоколь',
        ],

        'ip' => [
            'slug' => 'ip',
            'name' => 'Степень защиты IP',
        ],

        'armature_material' => [
            'slug' => 'armature-material',
            'name' => 'Материал арматуры',
        ],

        'armature_color' => [
            'slug' => 'armature-color',
            'name' => 'Цвет арматуры',
        ],

        'armature_surface' => [
            'slug' => 'armature-surface',
            'name' => 'Поверхность арматуры',
        ],

        'shade_material' => [
            'slug' => 'shade-material',
            'name' => 'Материал плафона',
        ],

        'shade_color' => [
            'slug' => 'shade-color',
            'name' => 'Цвет плафона',
        ],

        'shade_surface' => [
            'slug' => 'shade-surface',
            'name' => 'Поверхность плафона',
        ],

        'lamp_count' => [
            'slug' => 'lamp-count',
            'name' => 'Количество ламп',
        ],

        'led_power' => [
            'slug' => 'led-power',
            'name' => 'Мощность LED',
        ],

        'light_flux' => [
            'slug' => 'light-flux',
            'name' => 'Световой поток',
        ],

        'color_temperature' => [
            'slug' => 'color-temperature',
            'name' => 'Цветовая температура',
        ],

        'cri' => [
            'slug' => 'cri',
            'name' => 'Индекс цветопередачи CRI',
        ],

        'dimmable' => [
            'slug' => 'dimmable',
            'name' => 'Диммируемость',
        ],

        'smart_home' => [
            'slug' => 'smart-home',
            'name' => 'Умный дом',
        ],

        'led' => [
            'slug' => 'led',
            'name' => 'Встроенные светодиоды',
        ],

        'depth' => [
            'slug' => 'depth',
            'name' => 'Глубина',
        ],

        'chain_length' => [
            'slug' => 'chain-length',
            'name' => 'Длина цепи',
        ],

        'height_with_chain' => [
            'slug' => 'height-with-chain',
            'name' => 'Высота с цепью/тросом',
        ],

        'recessed_mounting_type' => [
            'slug' => 'recessed-mounting-type',
            'name' => 'Тип монтажа встраиваемого светильника',
        ],

        'shade_shape' => [
            'slug' => 'shade-shape',
            'name' => 'Форма абажура',
        ],

        'shade_mounting_type' => [
            'slug' => 'shade-mounting-type',
            'name' => 'Тип крепления абажура',
        ],

        'warranty' => [
            'slug' => 'warranty',
            'name' => 'Гарантия',
        ],

        'operating_temperature' => [
            'slug' => 'operating-temperature',
            'name' => 'Рабочая температура',
        ],

        'led_type' => [
            'slug' => 'led-type',
            'name' => 'Тип светодиодов',
        ],

        'control_protocol' => [
            'slug' => 'control-protocol',
            'name' => 'Протокол управления',
        ],
    ];


    /**
     * Получить описание атрибута.
     */
    public function get(
        string $normalized_name
    ): ?array {

        return $this->map[$normalized_name] ?? null;
    }


    /**
     * Получить slug таксономии.
     */
    public function getTaxonomy(
        string $normalized_name
    ): ?string {

        $attribute = $this->get($normalized_name);

        if (!$attribute) {
            return null;
        }

        return $attribute['slug'];
    }


    /**
     * Получить название атрибута.
     */
    public function getName(
        string $normalized_name
    ): ?string {

        $attribute = $this->get($normalized_name);

        if (!$attribute) {
            return null;
        }

        return $attribute['name'];
    }


    /**
     * Есть ли атрибут в карте.
     */
    public function has(
        string $normalized_name
    ): bool {

        return isset(
            $this->map[$normalized_name]
        );
    }


    /**
     * Получить всю карту.
     */
    public function getMap(): array
    {
        return $this->map;
    }
}

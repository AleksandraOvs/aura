<?php

use Supplier_Importer\Suppliers\Denkirs\Denkirs_Mapper;

$mapper = new Denkirs_Mapper();

$row = [
    'Внутренний идентификатор' => 'TEST-MAPPED-001',
    'Артикул' => 'TEST-MAPPED-SKU',
    'Название товара' => 'Тест сопоставления',
    'Тип товара' => 'Товар',
    'Бренд' => 'Denkirs',
    'Штрихкод' => '',

    'Категория' => 'Свет для дома / Настенные бра',

    'стиль' => 'современный',

    'цвет арматуры' => 'черный',

    'цвет плафона' => 'черный',

    'Картинка 1' => '',
];

$product_data = $mapper->map($row);

print_r(
    $product_data->get('attributes')
);

<?php

use Supplier_Importer\Suppliers\Denkirs\Denkirs_Mapper;

$mapper = new Denkirs_Mapper();

$row = [
    'Внутренний идентификатор' => 'TEST-001',
    'Артикул' => 'TEST-SKU',
    'Название товара' => 'Тестовый товар',
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

print_r($product_data->get('attributes'));

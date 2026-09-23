<?php

use Supplier_Importer\Suppliers\Denkirs\Denkirs_Mapper;

$mapper = new Denkirs_Mapper();

$categories = [
    'Свет для дома / Настенные бра',
    'Свет для дома / Комплектующие / Коронки',
    'Свет для дома / Шинные и струнные системы / Комплектующие для трековых светильников / Ключи для демонтажа светильников',
];

foreach ($categories as $category) {

    $row = [
        'Внутренний идентификатор' => 'TEST-001',
        'Артикул' => 'TEST-SKU',
        'Название товара' => 'Тестовый товар',
        'Тип товара' => 'simple',
        'Бренд' => 'Denkirs',
        'Штрихкод' => '',
        'Категория' => $category,
        'Ссылка' => '',
    ];

    $product_data = $mapper->map($row);

    echo "\n";
    echo "SOURCE:\n";
    echo $category . "\n";

    echo "MAPPED:\n";
    echo $product_data->get('category', '') . "\n";

    echo "-------------------------\n";
}

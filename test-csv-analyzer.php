<?php

use Supplier_Importer\CSV\Csv_Reader;
use Supplier_Importer\CSV\Csv_Analyzer;

$csv_file = 'C:\OSPanel\home\aura.local\wp-content\uploads\supplier-import-test\denkirs.csv';

$reader = new Csv_Reader(
    $csv_file
);

$reader->open();

$analyzer = new Csv_Analyzer();

$result = $analyzer->analyze(
    $reader,
    [
        'category_fields' => [
            'Категория',
        ],

        'attribute_fields' => [
            'стиль',
            'форма',
            'цвет арматуры',
            'материал арматуры',
            'цвет плафона',
            'материал плафона',
            'ширина/диаметр',
            'длина',
            'высота',
            'тип лампы',
            'мощность общая',
            'напряжение',
            'тип цоколя',
            'степень защиты ip',
            'цвет свечения',
            'место установки',
            'страна происхождения',
            'коллекция',
            'Назначение помещения',
            'форма плафона',
            'лампы в комплекте',
            'площадь освещения',
            'световой поток',
        ],
    ]
);

$reader->close();

print_r($result);

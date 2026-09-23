<?php

$file = WP_CONTENT_DIR . '/uploads/supplier-importer/denkirs-21.csv';

if (!file_exists($file)) {
    die("Файл не найден: {$file}\n");
}

$handle = fopen($file, 'r');

if (!$handle) {
    die("Не удалось открыть CSV.\n");
}

$headers = fgetcsv(
    $handle,
    0,
    ';'
);

$row = fgetcsv(
    $handle,
    0,
    ';'
);

fclose($handle);

if (!$headers || !$row) {
    die("Не удалось прочитать заголовок или первую строку.\n");
}

$headers = array_map(
    'trim',
    $headers
);

$row = array_pad(
    $row,
    count($headers),
    ''
);

$data = array_combine(
    $headers,
    array_slice($row, 0, count($headers))
);

echo 'COLUMNS: ' . count($headers) . PHP_EOL;
echo PHP_EOL;

echo "=== REAL DENKIRS ROW ===" . PHP_EOL;

foreach ($data as $key => $value) {
    echo '[' . $key . '] => ' . $value . PHP_EOL;
}

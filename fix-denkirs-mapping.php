<?php

use Supplier_Importer\Import\Mapping_Repository;

$repository = new Mapping_Repository();

$repository->save(
    'denkirs',
    'category',
    'Свет для дома / Настенные бра',
    1336,
    ''
);

$repository->save(
    'denkirs',
    'category',
    'Свет для дома / Комплектующие / Коронки',
    1355,
    ''
);

$repository->save(
    'denkirs',
    'category',
    'Свет для дома / Шинные и струнные системы / Комплектующие для трековых светильников / Ключи для демонтажа светильников',
    1360,
    ''
);

echo "Mapping обновлён.\n";

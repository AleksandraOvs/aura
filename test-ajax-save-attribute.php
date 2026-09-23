<?php

use Supplier_Importer\AJAX\AJAX_Mapping;

$user = get_users([
    'number' => 1,
]);

if (empty($user)) {
    echo "Пользователь не найден.\n";
    exit;
}

wp_set_current_user(
    $user[0]->ID
);

if (!current_user_can('manage_woocommerce')) {
    echo "У пользователя нет capability manage_woocommerce.\n";
    echo "User ID: " . $user[0]->ID . "\n";
    exit;
}

$_POST['nonce'] = wp_create_nonce('supplier_import');

$_POST['supplier'] = 'denkirs';

$_POST['mappings'] = wp_json_encode([
    [
        'taxonomy'      => 'armature-color',
        'source_parent' => 'цвет арматуры',
        'source_value'  => 'белый',
        'target_id'     => 112,
    ],
]);

$ajax = new AJAX_Mapping();

$ajax->save_attribute_values();

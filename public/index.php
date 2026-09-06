<?php

require __DIR__ . '/../vendor/autoload.php';

$f3 = \Base::instance();

$f3->config('../config/config.ini');
$f3->config('../config/routes.ini');

// Expose app settings as simple template variables (app_name, app_version, app_author)
foreach ($f3->get('app') as $key => $value) {
    $f3->set('app_' . $key, $value);
}

// Desktop settings (config/desktop.ini) -> template variables
$f3->config('../config/desktop.ini');
$f3->set('desktop_name', $f3->get('desktop.name'));

// Parse flat context_menu keys: item_N_field (sorted by N)
$raw = $f3->get('context_menu');
$entries = [];
foreach ($raw as $key => $value) {
    if (!preg_match('/^item_(\d+)_(type|label|icon|href|action)$/', $key, $m)) {
        continue;
    }
    $entries[(int)$m[1]][$m[2]] = $value;
}
ksort($entries);

$contextMenu = [];
foreach ($entries as $n => $fields) {
    if (($fields['type'] ?? '') === 'separator') {
        $contextMenu[] = ['type' => 'separator'];
        continue;
    }
    $item = ['type' => $fields['type'] ?? 'link'];
    foreach (['label', 'icon', 'href', 'action'] as $field) {
        if (isset($fields[$field])) {
            $item[$field] = $fields[$field];
        }
    }
    $contextMenu[] = $item;
}
$f3->set('context_menu', $contextMenu);

// Database connection (settings stored under the [database] section)
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $f3->get('database.db_host'),
    $f3->get('database.db_port'),
    $f3->get('database.db_name')
);

$f3->set('DB', new \DB\SQL(
    $dsn,
    $f3->get('database.db_user'),
    $f3->get('database.db_pass')
));

$f3->run();

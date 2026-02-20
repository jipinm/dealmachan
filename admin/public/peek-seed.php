<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/env.php';
require_once ROOT_PATH . '/core/Database.php';
$db = Database::getInstance()->getConnection();

$queries = [
    'cities'      => 'SELECT id, name, state FROM cities ORDER BY id',
    'areas'       => 'SELECT id, name, city_id FROM areas ORDER BY city_id LIMIT 20',
    'locations'   => 'SELECT id, name, area_id FROM locations ORDER BY id',
    'professions' => 'SELECT id, name FROM professions ORDER BY id',
    'tags'        => 'SELECT id, name FROM tags ORDER BY id LIMIT 20',
    'labels'      => 'SELECT id, name, color FROM labels ORDER BY id',
    'day_types'   => 'SELECT id, name FROM day_types ORDER BY id',
    'merchants'   => 'SELECT id, business_name, category, city_id FROM merchants ORDER BY id',
    'stores'      => 'SELECT id, store_name, merchant_id, city_id FROM stores ORDER BY id',
];
foreach($queries as $t => $q){
    echo "\n=== $t ===\n";
    $rows = $db->query($q)->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $r) echo implode(' | ', array_map(fn($k,$v)=>"$k:$v", array_keys($r), $r))."\n";
}

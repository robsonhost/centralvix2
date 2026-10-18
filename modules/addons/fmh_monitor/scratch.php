<?php
require_once __DIR__ . '/../../../init.php';
use Illuminate\Database\Capsule\Manager as Capsule;
$metrics = Capsule::table('mod_fmh_monitor_server_metrics')->orderBy('id', 'desc')->limit(5)->get();
echo json_encode($metrics);

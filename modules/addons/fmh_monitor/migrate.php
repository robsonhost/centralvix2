<?php
require_once __DIR__ . '/../../../init.php';
use Illuminate\Database\Capsule\Manager as Capsule;

if (!Capsule::schema()->hasTable('mod_fmh_monitor_servers')) {
    Capsule::schema()->create('mod_fmh_monitor_servers', function ($table) {
        $table->increments('id');
        $table->string('name', 255);
        $table->string('token', 64)->unique();
        $table->string('ip', 100)->nullable();
        $table->string('os', 100)->nullable();
        $table->enum('status', ['UP', 'DOWN', 'PAUSED'])->default('DOWN');
        $table->string('channels', 255)->default('whatsapp,telegram,email,webhooks');
        $table->dateTime('last_check')->nullable();
    });
    echo "Table mod_fmh_monitor_servers created.\n";
}

if (!Capsule::schema()->hasTable('mod_fmh_monitor_server_metrics')) {
    Capsule::schema()->create('mod_fmh_monitor_server_metrics', function ($table) {
        $table->increments('id');
        $table->integer('server_id');
        $table->dateTime('created_at');
        $table->float('load_1')->nullable();
        $table->float('load_5')->nullable();
        $table->float('load_15')->nullable();
        $table->integer('mem_total')->nullable();
        $table->integer('mem_used')->nullable();
        $table->string('disk_total', 50)->nullable();
        $table->integer('disk_used_pct')->nullable();
        $table->integer('mail_queue')->nullable();
        $table->text('services_status')->nullable();
    });
    echo "Table mod_fmh_monitor_server_metrics created.\n";
}

if (!Capsule::schema()->hasTable('mod_fmh_monitor_server_thresholds')) {
    Capsule::schema()->create('mod_fmh_monitor_server_thresholds', function ($table) {
        $table->increments('id');
        $table->integer('server_id');
        $table->string('metric', 50); // e.g. load_1, mem_used_pct, disk_used_pct, mail_queue
        $table->string('condition', 10); // e.g. >, <, ==
        $table->float('value');
        $table->string('alert_channels', 255)->nullable();
    });
    echo "Table mod_fmh_monitor_server_thresholds created.\n";
}

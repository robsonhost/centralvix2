<?php

namespace Cvx\Catalog;

use WHMCS\Database\Capsule;

final class Schema
{
    public static function install(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('mod_cvxcatalog_group_meta')) {
            $schema->create('mod_cvxcatalog_group_meta', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('group_id')->unique();
                $table->string('slug', 80)->nullable();
                $table->string('segment', 60)->nullable();
                $table->string('icon', 80)->nullable();
                $table->string('title', 180)->nullable();
                $table->string('subtitle', 220)->nullable();
                $table->string('color', 20)->nullable();
                $table->string('image', 255)->nullable();
                $table->string('banner', 255)->nullable();
                $table->boolean('featured')->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->text('features_json')->nullable();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('mod_cvxcatalog_product_meta')) {
            $schema->create('mod_cvxcatalog_product_meta', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('product_id')->unique();
                $table->string('slug', 80)->nullable();
                $table->string('segment', 60)->nullable();
                $table->string('slogan', 220)->nullable();
                $table->string('badge', 80)->nullable();
                $table->string('icon', 80)->nullable();
                $table->string('image', 255)->nullable();
                $table->string('color', 20)->nullable();
                $table->string('seo_title', 180)->nullable();
                $table->string('seo_description', 255)->nullable();
                $table->string('seo_keywords', 255)->nullable();
                $table->string('og_image', 255)->nullable();
                $table->decimal('compare_monthly', 10, 2)->nullable();
                $table->decimal('compare_annually', 10, 2)->nullable();
                $table->string('status', 20)->default('published');
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('mod_cvxcatalog_features')) {
            $schema->create('mod_cvxcatalog_features', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('product_id')->index();
                $table->string('name', 160);
                $table->string('value', 160)->nullable();
                $table->string('icon', 80)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('highlight')->default(0);
            });
        }

        if (!$schema->hasTable('mod_cvxcatalog_logs')) {
            $schema->create('mod_cvxcatalog_logs', function ($table) {
                $table->increments('id');
                $table->string('batch_id', 40)->nullable()->index();
                $table->unsignedInteger('admin_id')->nullable();
                $table->string('action', 60);
                $table->unsignedInteger('product_id')->nullable();
                $table->string('product_name', 180)->nullable();
                $table->text('before_json')->nullable();
                $table->text('after_json')->nullable();
                $table->string('message', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        self::addColumn('mod_cvxcatalog_product_meta', 'location', 'VARCHAR(40) NULL');
        self::addColumn('mod_cvxcatalog_product_meta', 'location_label', 'VARCHAR(80) NULL');
        self::addColumn('mod_cvxcatalog_product_meta', 'discount_percentage', 'DECIMAL(5,2) NULL');
        self::addColumn('mod_cvxcatalog_product_meta', 'promotion_label', 'VARCHAR(40) NULL');
        self::addColumn('mod_cvxcatalog_product_meta', 'sort_order', 'INT UNSIGNED NOT NULL DEFAULT 0');
        self::addColumn('mod_cvxcatalog_features', 'unit', 'VARCHAR(40) NULL');

        if (!$schema->hasTable('mod_cvxcatalog_acl')) {
            $schema->create('mod_cvxcatalog_acl', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('role_id')->index();
                $table->string('permission', 80);
                $table->boolean('allowed')->default(1);
                $table->unique(['role_id', 'permission']);
            });
        }
    }

    private static function addColumn(string $table, string $column, string $definition): void
    {
        if (!Capsule::schema()->hasTable($table) || Capsule::schema()->hasColumn($table, $column)) {
            return;
        }
        try {
            Capsule::statement("ALTER TABLE `{$table}` ADD `{$column}` {$definition}");
        } catch (\Throwable $e) {
        }
    }
}

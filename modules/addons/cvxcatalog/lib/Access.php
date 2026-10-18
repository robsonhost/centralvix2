<?php

namespace Cvx\Catalog;

use WHMCS\Database\Capsule;

final class Access
{
    public const VIEW = 'centralvix_catalog_view';
    public const CREATE = 'centralvix_catalog_create';
    public const EDIT = 'centralvix_catalog_edit';
    public const DELETE = 'centralvix_catalog_delete';
    public const BULK = 'centralvix_catalog_bulk';
    public const PRICES = 'centralvix_catalog_prices';

    public static function all(): array
    {
        return [
            self::VIEW => 'Ver catálogo',
            self::CREATE => 'Criar produtos',
            self::EDIT => 'Editar produtos',
            self::DELETE => 'Excluir / descontinuar',
            self::BULK => 'Criação e ações em massa',
            self::PRICES => 'Alterar preços',
        ];
    }

    public static function can(string $permission): bool
    {
        $adminId = (int) ($_SESSION['adminid'] ?? 0);
        if ($adminId < 1) {
            return false;
        }
        $roleId = (int) Capsule::table('tbladmins')->where('id', $adminId)->value('roleid');
        if ($roleId === 1) {
            return true;
        }
        if (!Capsule::schema()->hasTable('mod_cvxcatalog_acl')) {
            return true;
        }
        $row = Capsule::table('mod_cvxcatalog_acl')
            ->where('role_id', $roleId)
            ->where('permission', $permission)
            ->first();
        return !$row || (int) $row->allowed === 1;
    }

    public static function require(string $permission): void
    {
        if (!self::can($permission)) {
            throw new \RuntimeException('Permissão negada: ' . $permission);
        }
    }
}

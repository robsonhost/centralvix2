<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixAutoDJStorageService
{
    public static function ensureStorageRoot()
    {
        $root = CentralVixAutoDJModule::storagePath();
        if (!is_dir($root)) {
            mkdir($root, 0750, true);
        }
        $ht = $root . '/.htaccess';
        if (!is_file($ht)) {
            file_put_contents($ht, "Deny from all\n");
        }
        $idx = $root . '/index.php';
        if (!is_file($idx)) {
            file_put_contents($idx, "<?php // Silence is golden.\n");
        }
        return $root;
    }

    public static function accountDir($accountId)
    {
        self::ensureStorageRoot();
        $id = (int) $accountId;
        if ($id < 1) {
            throw new InvalidArgumentException('Conta inválida');
        }
        $dir = self::ensureStorageRoot() . '/accounts/' . $id;
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
            file_put_contents($dir . '/.htaccess', "Deny from all\n");
            file_put_contents($dir . '/index.php', "<?php // Silence is golden.\n");
        }
        return $dir;
    }

    public static function storeFile($accountId, $clientId, array $file, $mediaType = 'music')
    {
        CentralVixAutoDJService::assertAccountOwner($accountId, $clientId, !empty($_SESSION['adminid']));
        $validation = CentralVixAutoDJAudioValidator::validateUpload($file);
        if (!$validation['valid']) {
            return $validation;
        }
        $account = CentralVixAutoDJService::getAccount($accountId, $clientId);
        if (!$account) {
            return ['valid' => false, 'message' => 'Conta não encontrada.'];
        }
        $usage = self::getUsageBytes($accountId);
        if ($usage + $validation['size'] > (int) $account->quota_mb * 1048576) {
            return ['valid' => false, 'message' => 'Quota de armazenamento excedida.'];
        }
        $hash = bin2hex(random_bytes(16));
        $stored = $hash . '.' . $validation['extension'];
        $dest = self::accountDir($accountId) . '/' . $stored;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['valid' => false, 'message' => 'Falha ao salvar arquivo.'];
        }
        chmod($dest, 0640);
        $mediaId = (int) Capsule::table('mod_centralvix_autodj_media')->insertGetId([
            'account_id' => (int) $accountId,
            'client_id' => (int) $clientId,
            'media_type' => preg_replace('/[^a-z_]/', '', $mediaType),
            'stored_name' => $stored,
            'original_name' => $validation['original_name'],
            'mime_type' => $validation['mime'],
            'size_bytes' => $validation['size'],
            'duration_seconds' => (int) $validation['duration'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixAutoDJMediaLibrary::syncToProvider($accountId, $mediaId);
        return ['valid' => true, 'media_id' => $mediaId, 'message' => 'Upload concluído.'];
    }

    public static function deleteFile($mediaId, $clientId)
    {
        $media = CentralVixAutoDJMediaLibrary::getMedia($mediaId, $clientId);
        if (!$media) {
            return ['success' => false, 'message' => 'Arquivo não encontrado.'];
        }
        $path = self::accountDir($media->account_id) . '/' . $media->stored_name;
        if (is_file($path) && CentralVixAutoDJSecurity::rejectPathTraversal($media->stored_name)) {
            @unlink($path);
        }
        Capsule::table('mod_centralvix_autodj_playlist_items')->where('media_id', $mediaId)->delete();
        Capsule::table('mod_centralvix_autodj_media')->where('id', $mediaId)->delete();
        CentralVixAutoDJMediaLibrary::deleteFromProvider($media->account_id, $media);
        return ['success' => true, 'message' => 'Arquivo removido.'];
    }

    public static function getUsageBytes($accountId)
    {
        return (int) Capsule::table('mod_centralvix_autodj_media')
            ->where('account_id', (int) $accountId)
            ->sum('size_bytes');
    }

    public static function quotaInfo($account)
    {
        $used = self::getUsageBytes($account->id);
        $total = (int) $account->quota_mb * 1048576;
        $pct = $total > 0 ? round(($used / $total) * 100, 1) : 0;
        return [
            'used_bytes' => $used,
            'total_bytes' => $total,
            'used_mb' => round($used / 1048576, 2),
            'total_mb' => (int) $account->quota_mb,
            'available_mb' => max(0, round(($total - $used) / 1048576, 2)),
            'percent' => min(100, $pct),
        ];
    }

    public static function absolutePath($media)
    {
        if (!$media || !CentralVixAutoDJSecurity::rejectPathTraversal($media->stored_name)) {
            return null;
        }
        $path = self::accountDir($media->account_id) . '/' . $media->stored_name;
        return is_file($path) ? $path : null;
    }
}

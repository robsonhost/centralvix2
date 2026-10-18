<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixMigMigrationService
{
    public static function generateReference()
    {
        return 'MIG-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    public static function create(array $data, $clientId)
    {
        $domain = strtolower(trim($data['domain'] ?? ''));
        if (!CentralVixMigSecurity::validateDomain($domain)) {
            return ['success' => false, 'message' => 'Domínio inválido.'];
        }

        $panel = CentralVixMigSecurity::validatePanel($data['current_panel'] ?? 'outro');
        $reference = self::generateReference();
        $now = date('Y-m-d H:i:s');

        $id = Capsule::table('mod_centralvix_migration_requests')->insertGetId([
            'client_id' => (int) $clientId,
            'reference' => $reference,
            'domain' => $domain,
            'current_hosting' => CentralVixMigSecurity::sanitizeText($data['current_hosting'] ?? '', 150),
            'current_panel' => $panel,
            'current_server' => CentralVixMigSecurity::sanitizeText($data['current_server'] ?? '', 150),
            'migration_type' => CentralVixMigSecurity::sanitizeText($data['migration_type'] ?? 'completa', 80),
            'estimated_size' => CentralVixMigSecurity::sanitizeText($data['estimated_size'] ?? '', 50),
            'database_count' => max(0, (int) ($data['database_count'] ?? 0)),
            'email_count' => max(0, (int) ($data['email_count'] ?? 0)),
            'notes' => CentralVixMigSecurity::sanitizeHtml($data['notes'] ?? '', 3000),
            'preferred_date' => self::parseDate($data['preferred_date'] ?? ''),
            'status' => 'solicitada',
            'progress_percent' => 0,
            'client_message' => '',
            'admin_notes' => '',
            'credentials_blob' => null,
            'credentials_provided' => 0,
            'credentials_purged_at' => null,
            'scheduled_date' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        CentralVixMigChecklistService::initForRequest($id);

        if (!empty($data['provide_credentials'])) {
            CentralVixMigCredentialVault::store($id, [
                'host' => $data['cred_host'] ?? '',
                'port' => $data['cred_port'] ?? 2083,
                'username' => $data['cred_username'] ?? '',
                'password' => $data['cred_password'] ?? '',
            ]);
        }

        CentralVixMigLogger::log($id, 'request_created', 'Solicitação criada', ['reference' => $reference], 'client', $clientId);
        $request = self::get($id);
        CentralVixMigNotificationService::notify('request_received', $request);

        return ['success' => true, 'id' => $id, 'reference' => $reference];
    }

    public static function parseDate($date)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }
        $ts = strtotime($date);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    public static function get($id)
    {
        return Capsule::table('mod_centralvix_migration_requests')->where('id', (int) $id)->first();
    }

    public static function getForClient($id, $clientId)
    {
        $row = self::get($id);
        if (!$row || (int) $row->client_id !== (int) $clientId) {
            return null;
        }
        return $row;
    }

    public static function listForClient($clientId)
    {
        return Capsule::table('mod_centralvix_migration_requests')
            ->where('client_id', (int) $clientId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function listAdmin(array $filters = [])
    {
        $q = Capsule::table('mod_centralvix_migration_requests as r')
            ->leftJoin('tblclients as c', 'c.id', '=', 'r.client_id')
            ->select('r.*', 'c.firstname', 'c.lastname', 'c.email');

        if (!empty($filters['status'])) {
            $q->where('r.status', $filters['status']);
        }
        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $q->where(function ($query) use ($term) {
                $query->where('r.domain', 'like', $term)
                    ->orWhere('r.reference', 'like', $term);
            });
        }

        return $q->orderBy('r.id', 'desc')->limit(200)->get();
    }

    public static function dashboardStats()
    {
        $all = Capsule::table('mod_centralvix_migration_requests')->get();
        $stats = [
            'open' => 0,
            'in_progress' => 0,
            'waiting_client' => 0,
            'scheduled' => 0,
            'completed' => 0,
            'problems' => 0,
            'total' => count($all),
        ];
        $progress = ['backup', 'migrando_arquivos', 'migrando_banco', 'migrando_emails', 'testando', 'aguardando_dns', 'monitorando'];
        foreach ($all as $r) {
            if ($r->status === 'solicitada' || $r->status === 'em_analise') {
                $stats['open']++;
            }
            if (in_array($r->status, $progress, true)) {
                $stats['in_progress']++;
            }
            if ($r->status === 'aguardando_cliente') {
                $stats['waiting_client']++;
            }
            if ($r->status === 'agendada') {
                $stats['scheduled']++;
            }
            if ($r->status === 'concluida') {
                $stats['completed']++;
            }
            if (in_array($r->status, ['falhou', 'cancelada'], true)) {
                $stats['problems']++;
            }
        }
        return $stats;
    }

    public static function changeStatus($requestId, $newStatus, $actorType = 'admin', $actorId = 0, $clientMessage = '')
    {
        $newStatus = CentralVixMigSecurity::validateStatus($newStatus);
        if (!$newStatus) {
            return ['success' => false, 'message' => 'Status inválido.'];
        }
        $request = self::get($requestId);
        if (!$request) {
            return ['success' => false, 'message' => 'Solicitação não encontrada.'];
        }

        $oldStatus = $request->status;
        $update = [
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($clientMessage !== '') {
            $update['client_message'] = CentralVixMigSecurity::sanitizeHtml($clientMessage, 2000);
        }

        Capsule::table('mod_centralvix_migration_requests')->where('id', (int) $requestId)->update($update);
        CentralVixMigLogger::log($requestId, 'status_change', $oldStatus . ' → ' . $newStatus, [], $actorType, $actorId);

        $request = self::get($requestId);
        $event = CentralVixMigNotificationService::mapStatusToEvent($oldStatus, $newStatus);
        if ($event) {
            CentralVixMigNotificationService::notify($event, $request);
        }

        CentralVixMigCredentialVault::purgeIfCompleted($request);

        $provider = CentralVixMigModule::migrationProvider();
        if ($newStatus === 'agendada' && $oldStatus !== 'agendada') {
            $provider->onScheduled($request);
        }
        if ($newStatus === 'backup') {
            $provider->onMigrationStart($request);
        }

        return ['success' => true];
    }

    public static function schedule($requestId, $scheduledDate, $adminId = 0)
    {
        $date = self::parseDate($scheduledDate);
        if (!$date) {
            return ['success' => false, 'message' => 'Data inválida.'];
        }
        Capsule::table('mod_centralvix_migration_requests')->where('id', (int) $requestId)->update([
            'scheduled_date' => $date . ' 00:00:00',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return self::changeStatus($requestId, 'agendada', 'admin', $adminId);
    }

    public static function addComment($requestId, $message, $authorType, $authorId, $authorName, $visibility = 'client')
    {
        $message = CentralVixMigSecurity::sanitizeHtml($message, 3000);
        if ($message === '') {
            return ['success' => false, 'message' => 'Mensagem vazia.'];
        }
        $visibility = $visibility === 'internal' ? 'internal' : 'client';
        Capsule::table('mod_centralvix_migration_comments')->insert([
            'request_id' => (int) $requestId,
            'author_type' => $authorType,
            'author_id' => (int) $authorId,
            'author_name' => CentralVixMigSecurity::sanitizeText($authorName, 150),
            'message' => $message,
            'visibility' => $visibility,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixMigLogger::log($requestId, 'comment', 'Novo comentário', ['visibility' => $visibility], $authorType, $authorId);
        return ['success' => true];
    }

    public static function commentsForRequest($requestId, $clientView = false)
    {
        $q = Capsule::table('mod_centralvix_migration_comments')
            ->where('request_id', (int) $requestId)
            ->orderBy('id', 'asc');
        if ($clientView) {
            $q->where('visibility', 'client');
        }
        return $q->get();
    }

    public static function clientPublicPayload($request)
    {
        return [
            'id' => $request->id,
            'reference' => $request->reference,
            'domain' => $request->domain,
            'current_panel' => CentralVixMigModule::panels()[$request->current_panel] ?? $request->current_panel,
            'migration_type' => $request->migration_type,
            'status' => $request->status,
            'status_label' => CentralVixMigModule::statuses()[$request->status] ?? $request->status,
            'progress_percent' => (int) $request->progress_percent,
            'preferred_date' => $request->preferred_date,
            'scheduled_date' => $request->scheduled_date,
            'client_message' => $request->client_message,
            'created_at' => $request->created_at,
            'updated_at' => $request->updated_at,
        ];
    }

    public static function timelineState($request)
    {
        $steps = CentralVixMigModule::clientTimelineSteps();
        $currentIndex = 0;
        $failed = in_array($request->status, ['falhou', 'cancelada'], true);
        foreach ($steps as $i => $step) {
            if (in_array($request->status, $step['statuses'], true)) {
                $currentIndex = $i;
            }
        }
        if ($request->status === 'concluida') {
            $currentIndex = count($steps) - 1;
        }
        return ['steps' => $steps, 'current' => $currentIndex, 'failed' => $failed];
    }
}

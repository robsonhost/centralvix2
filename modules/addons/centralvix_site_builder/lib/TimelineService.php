<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixSiteTimelineService
{
    public static function add($projectId, $eventType, $title, $description = '', $actorType = 'system', $actorId = 0, $actorName = 'Sistema', array $meta = [])
    {
        Capsule::table('mod_centralvix_site_timeline')->insert([
            'project_id' => (int) $projectId,
            'event_type' => $eventType,
            'title' => CentralVixSiteSecurity::sanitizeText($title, 200),
            'description' => CentralVixSiteSecurity::sanitizeText($description, 2000),
            'actor_type' => $actorType,
            'actor_id' => (int) $actorId,
            'actor_name' => CentralVixSiteSecurity::sanitizeText($actorName, 150),
            'meta_json' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function listByProject($projectId, $limit = 100)
    {
        return Capsule::table('mod_centralvix_site_timeline')
            ->where('project_id', (int) $projectId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function formatEntry($entry)
    {
        return [
            'id' => (int) $entry->id,
            'event_type' => $entry->event_type,
            'title' => $entry->title,
            'description' => $entry->description,
            'actor_name' => $entry->actor_name,
            'created_at' => $entry->created_at,
            'date' => date('d/m/Y', strtotime($entry->created_at)),
            'time' => date('H:i', strtotime($entry->created_at)),
        ];
    }
}

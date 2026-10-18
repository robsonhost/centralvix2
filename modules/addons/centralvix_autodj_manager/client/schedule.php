<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
if (!$account) { return; }
include __DIR__ . '/_nav.php';
$days = ['0'=>'Dom','1'=>'Seg','2'=>'Ter','3'=>'Qua','4'=>'Qui','5'=>'Sex','6'=>'Sáb'];
?>
<div class="cvix-adj-panel">
    <h3>Novo agendamento</h3>
    <form id="cvixScheduleForm" class="cvix-adj-form">
        <input type="hidden" name="token" value="<?php echo CentralVixAutoDJSecurity::e($csrfToken); ?>">
        <input type="hidden" name="account_id" value="<?php echo (int)$account->id; ?>">
        <label>Título<input name="title" required></label>
        <label>Tipo<select name="schedule_type"><option value="program">Programação</option><option value="clock">Relógio musical</option><option value="commercial">Bloco comercial</option><option value="jingle_hour">Vinheta horária</option><option value="programete">Programete</option></select></label>
        <label>Data específica (opcional)<input type="date" name="schedule_date"></label>
        <label>Dias da semana</label>
        <div class="cvix-adj-checks"><?php foreach ($days as $v=>$l): ?><label><input type="checkbox" name="days_of_week[]" value="<?php echo $v; ?>"> <?php echo $l; ?></label><?php endforeach; ?></div>
        <label>Início<input type="time" name="time_start" value="08:00" required></label>
        <label>Fim<input type="time" name="time_end" value="09:00" required></label>
        <label>Playlist<select name="playlist_id"><option value="0">—</option><?php foreach ($playlists as $p): ?><option value="<?php echo (int)$p->id; ?>"><?php echo CentralVixAutoDJSecurity::e($p->name); ?></option><?php endforeach; ?></select></label>
        <label>Prioridade<input type="number" name="priority" value="0"></label>
        <label><input type="checkbox" name="active" value="1" checked> Ativo</label>
        <button type="submit" class="cvix-adj-btn primary">Salvar agendamento</button>
    </form>
    <div id="cvixConflictAlert" class="cvix-adj-alert hidden"></div>
</div>
<div class="cvix-adj-panel">
    <h3>Programação</h3>
    <table class="cvix-adj-table">
        <thead><tr><th>Título</th><th>Horário</th><th>Playlist</th><th>Tipo</th><th>Prior.</th></tr></thead>
        <tbody>
        <?php foreach ($schedules as $s): ?>
        <tr>
            <td><?php echo CentralVixAutoDJSecurity::e($s->title); ?></td>
            <td><?php echo substr($s->time_start,0,5); ?> — <?php echo substr($s->time_end,0,5); ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e($s->playlist_name ?: '-'); ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e($s->schedule_type); ?></td>
            <td><?php echo (int)$s->priority; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

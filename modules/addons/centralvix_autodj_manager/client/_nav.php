<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
if (!$account) { return; }
$cur = $page ?? 'dashboard';
$links = [
    'dashboard' => ['Minha Rádio', ''],
    'library' => ['Biblioteca', 'library'],
    'playlists' => ['Playlists', 'playlists'],
    'schedule' => ['Programação', 'schedule'],
    'settings' => ['Uso', 'settings'],
];
?>
<nav class="cvix-adj-client-nav">
<?php foreach ($links as $key => $info): ?>
<a href="<?php echo CentralVixAutoDJSecurity::e($moduleLink . ($info[1] ? '&page=' . $info[1] : '')); ?>" class="<?php echo $cur === $key ? 'active' : ''; ?>"><?php echo CentralVixAutoDJSecurity::e($info[0]); ?></a>
<?php endforeach; ?>
</nav>

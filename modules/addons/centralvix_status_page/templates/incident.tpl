<?php
/** Compatibilidade — use public/incident.php?id=X */
header('Location: ../public/incident.php?id=' . (int)($_GET['id'] ?? 0));
exit;

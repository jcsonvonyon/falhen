<?php
// portfolio-single.php - Router redirecting to dedicated single portfolio views
require_once __DIR__ . '/includes/functions.php';

$masterPortfolioItems = getPortfolioRepo();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$targetItem = null;

foreach ($masterPortfolioItems as $item) {
    if ((int)($item['id'] ?? 0) === $id) {
        $targetItem = $item;
        break;
    }
}

$mediaType = $targetItem['media_type'] ?? 'photo';

if ($mediaType === 'video') {
    header('Location: /portfolio-video.php?id=' . $id);
} else if ($mediaType === 'project') {
    header('Location: /portfolio-project.php?id=' . $id);
} else {
    header('Location: /portfolio-photo.php?id=' . $id);
}
exit;

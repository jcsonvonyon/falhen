<?php
// project-single.php - Redirect handler to portfolio-project.php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
header('Location: /portfolio-project.php?id=' . $id);
exit;

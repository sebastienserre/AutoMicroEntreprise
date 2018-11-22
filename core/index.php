<?php
$urlback = dirname($_SERVER['PHP_SELF']);
$urlback = dirname($urlback).'/report/index.php';
header('Location: '.$urlback);
exit;
?>

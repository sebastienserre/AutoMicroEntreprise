<?php
$urlback = dirname($_SERVER['PHP_SELF']);
$urlback = dirname($urlback).'/index.php';
header('Location: '.$urlback);
exit;
?>

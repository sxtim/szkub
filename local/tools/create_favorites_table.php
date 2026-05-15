<?php

if (PHP_SAPI !== "cli") {
    die("CLI only\n");
}

$_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__ . "/../..");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

if (!function_exists("szcubeFavoritesEnsureTable")) {
    require_once $_SERVER["DOCUMENT_ROOT"] . "/local/include/favorites.php";
}

$ok = szcubeFavoritesEnsureTable();
echo $ok ? "Favorites table is ready\n" : "Favorites table was not created\n";

<?php
if (!defined("ADMIN_MODULE_NAME")) {
    return false;
}

$szcubeLeadsConfigPath = rtrim((string)($_SERVER["DOCUMENT_ROOT"] ?? ""), "/") . "/local/php_interface/szcube_leads.php";
if ($szcubeLeadsConfigPath !== "/local/php_interface/szcube_leads.php" && is_file($szcubeLeadsConfigPath)) {
    require_once $szcubeLeadsConfigPath;
}

$scopeMap = function_exists("szcubeLeadGetScopeMap") ? szcubeLeadGetScopeMap() : array();
$items = array();
foreach ($scopeMap as $scopeCode => $scopeConfig) {
    $items[] = array(
        "text" => isset($scopeConfig["title"]) ? (string)$scopeConfig["title"] : (string)$scopeCode,
        "title" => isset($scopeConfig["title"]) ? (string)$scopeConfig["title"] : (string)$scopeCode,
        "url" => "/bitrix/admin/szcube_leads.php?lang=ru&scope=" . urlencode((string)$scopeCode),
        "more_url" => array(
            "/bitrix/admin/szcube_leads.php",
        ),
    );
}

$aMenu = array(
    "parent_menu" => "global_menu_services",
    "section" => "szcube_leads",
    "sort" => 1500,
    "text" => "Заявки SZCUBE",
    "title" => "Заявки SZCUBE",
    "icon" => "form_menu_icon",
    "page_icon" => "form_page_icon",
    "items_id" => "menu_szcube_leads",
    "more_url" => array(
        "/bitrix/admin/szcube_leads.php",
    ),
    "items" => $items,
);

return $aMenu;

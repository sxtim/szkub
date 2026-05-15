<?php
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("PUBLIC_AJAX_MODE", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

header("Content-Type: application/json; charset=UTF-8");

if (!function_exists("szcubeFavoritesToggle")) {
    require_once $_SERVER["DOCUMENT_ROOT"] . "/local/include/favorites.php";
}

function szcubeFavoritesAjaxResponse(array $payload, int $statusCode = 200): void
{
    if (function_exists("http_response_code")) {
        http_response_code($statusCode);
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    die();
}

function szcubeFavoritesAjaxRequestValue(string $key, $default = "")
{
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }

    if (isset($_GET[$key])) {
        return $_GET[$key];
    }

    return $default;
}

try {
    $action = trim((string)szcubeFavoritesAjaxRequestValue("action", "state"));

    if ($action === "toggle") {
        $type = (string)szcubeFavoritesAjaxRequestValue("entity_type", "");
        $id = (int)szcubeFavoritesAjaxRequestValue("entity_id", 0);
        $result = szcubeFavoritesToggle($type, $id);
        szcubeFavoritesAjaxResponse($result, !empty($result["success"]) ? 200 : 400);
    }

    if ($action === "list") {
        szcubeFavoritesAjaxResponse(szcubeFavoritesList());
    }

    if ($action === "state") {
        $items = szcubeFavoritesAjaxRequestValue("items", array());
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            if (is_array($decoded)) {
                $items = $decoded;
            } else {
                $items = preg_split("/[,\s]+/", $items);
            }
        }
        if (!is_array($items)) {
            $items = array();
        }
        szcubeFavoritesAjaxResponse(szcubeFavoritesState($items));
    }

    szcubeFavoritesAjaxResponse(array("success" => false, "message" => "Неизвестное действие."), 400);
} catch (\Throwable $e) {
    szcubeFavoritesAjaxResponse(
        array(
            "success" => false,
            "message" => "Не удалось выполнить действие с избранным.",
        ),
        500
    );
}

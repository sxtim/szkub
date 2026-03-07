<?php

if (PHP_SAPI !== "cli" && !headers_sent()) {
    $host = strtolower((string)($_SERVER["HTTP_HOST"] ?? ""));
    $host = preg_replace("/:\\d+$/", "", $host);

    if (in_array($host, ["szcube.ru", "www.szcube.ru"], true)) {
        $isHttps = false;

        if (!empty($_SERVER["HTTPS"]) && strtolower((string)$_SERVER["HTTPS"]) !== "off") {
            $isHttps = true;
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_PROTO"])) {
            $isHttps = strtolower((string)$_SERVER["HTTP_X_FORWARDED_PROTO"]) === "https";
        }

        if ($host !== "szcube.ru" || !$isHttps) {
            $requestUri = (string)($_SERVER["REQUEST_URI"] ?? "/");
            if ($requestUri === "") {
                $requestUri = "/";
            }

            $method = strtoupper((string)($_SERVER["REQUEST_METHOD"] ?? "GET"));
            $statusCode = in_array($method, ["GET", "HEAD"], true) ? 301 : 308;

            header("Location: https://szcube.ru" . $requestUri, true, $statusCode);
            exit;
        }
    }
}

if (!function_exists("szcubeGetManagedProjectDynamicIblockIds")) {
    function szcubeGetManagedProjectDynamicIblockIds()
    {
        static $map = null;

        if ($map !== null) {
            return $map;
        }

        $map = array();

        if (!CModule::IncludeModule("iblock")) {
            return $map;
        }

        $codes = array(
            "project_advantages",
            "project_construction",
            "project_documents",
        );

        $res = CIBlock::GetList(array(), array("=CODE" => $codes), false);
        while ($row = $res->Fetch()) {
            $iblockId = (int)$row["ID"];
            if ($iblockId > 0) {
                $map[$iblockId] = (string)$row["CODE"];
            }
        }

        return $map;
    }
}

if (!function_exists("szcubeGetProjectCodeById")) {
    function szcubeGetProjectCodeById($projectId)
    {
        static $cache = array();

        $projectId = (int)$projectId;
        if ($projectId <= 0) {
            return "";
        }

        if (array_key_exists($projectId, $cache)) {
            return $cache[$projectId];
        }

        $cache[$projectId] = "";
        $res = CIBlockElement::GetList(
            array(),
            array("ID" => $projectId),
            false,
            false,
            array("ID", "CODE")
        );
        if ($row = $res->Fetch()) {
            $cache[$projectId] = trim((string)$row["CODE"]);
        }

        return $cache[$projectId];
    }
}

if (!function_exists("szcubeGetDynamicElementProjectId")) {
    function szcubeGetDynamicElementProjectId($elementId, $iblockId)
    {
        $elementId = (int)$elementId;
        $iblockId = (int)$iblockId;
        if ($elementId <= 0 || $iblockId <= 0) {
            return 0;
        }

        $res = CIBlockElement::GetProperty(
            $iblockId,
            $elementId,
            array("SORT" => "ASC", "ID" => "ASC"),
            array("CODE" => "PROJECT")
        );
        if ($row = $res->Fetch()) {
            return (int)$row["VALUE"];
        }

        return 0;
    }
}

if (!function_exists("szcubeGetDynamicSectionIdByProjectCode")) {
    function szcubeGetDynamicSectionIdByProjectCode($iblockId, $projectCode)
    {
        static $cache = array();

        $iblockId = (int)$iblockId;
        $projectCode = trim((string)$projectCode);
        if ($iblockId <= 0 || $projectCode === "") {
            return 0;
        }

        $cacheKey = $iblockId . ":" . mb_strtolower($projectCode);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = 0;
        $res = CIBlockSection::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "SECTION_ID" => false,
                "=CODE" => $projectCode,
            ),
            false,
            array("ID")
        );
        if ($row = $res->Fetch()) {
            $cache[$cacheKey] = (int)$row["ID"];
        }

        return $cache[$cacheKey];
    }
}

if (!function_exists("szcubeGetElementSectionIds")) {
    function szcubeGetElementSectionIds($elementId)
    {
        $elementId = (int)$elementId;
        if ($elementId <= 0) {
            return array();
        }

        $sectionIds = array();
        $res = CIBlockElement::GetElementGroups($elementId, true, array("ID"));
        while ($row = $res->Fetch()) {
            $sectionIds[] = (int)$row["ID"];
        }

        $sectionIds = array_values(array_unique(array_filter($sectionIds)));
        sort($sectionIds);

        return $sectionIds;
    }
}

if (!function_exists("szcubeSyncProjectDynamicElementSection")) {
    function szcubeSyncProjectDynamicElementSection(&$fields)
    {
        static $inProgress = array();

        $elementId = isset($fields["ID"]) ? (int)$fields["ID"] : 0;
        $iblockId = isset($fields["IBLOCK_ID"]) ? (int)$fields["IBLOCK_ID"] : 0;
        if ($elementId <= 0 || $iblockId <= 0) {
            return;
        }

        $managedIblocks = szcubeGetManagedProjectDynamicIblockIds();
        if (!isset($managedIblocks[$iblockId])) {
            return;
        }

        $lockKey = $iblockId . ":" . $elementId;
        if (isset($inProgress[$lockKey])) {
            return;
        }

        if (!CModule::IncludeModule("iblock")) {
            return;
        }

        $projectId = szcubeGetDynamicElementProjectId($elementId, $iblockId);
        if ($projectId <= 0) {
            return;
        }

        $projectCode = szcubeGetProjectCodeById($projectId);
        if ($projectCode === "") {
            return;
        }

        $targetSectionId = szcubeGetDynamicSectionIdByProjectCode($iblockId, $projectCode);
        if ($targetSectionId <= 0) {
            return;
        }

        $currentSectionIds = szcubeGetElementSectionIds($elementId);
        if (count($currentSectionIds) === 1 && (int)$currentSectionIds[0] === $targetSectionId) {
            return;
        }

        $inProgress[$lockKey] = true;
        CIBlockElement::SetElementSection($elementId, array($targetSectionId), false);
        unset($inProgress[$lockKey]);
    }
}

AddEventHandler("iblock", "OnAfterIBlockElementAdd", "szcubeSyncProjectDynamicElementSection");
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", "szcubeSyncProjectDynamicElementSection");

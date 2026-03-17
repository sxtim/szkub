<?php

$projectSelectorSceneConfigPath = rtrim((string)($_SERVER["DOCUMENT_ROOT"] ?? ""), "/") . "/local/php_interface/project_selector_scene_config.php";
if ($projectSelectorSceneConfigPath !== "/local/php_interface/project_selector_scene_config.php" && is_file($projectSelectorSceneConfigPath)) {
    require_once $projectSelectorSceneConfigPath;
}

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

if (!function_exists("szcubeGetIblockIdByCode")) {
    function szcubeGetIblockIdByCode($code)
    {
        static $cache = array();

        $code = trim((string)$code);
        if ($code === "") {
            return 0;
        }

        $cacheKey = mb_strtolower($code);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = 0;
        if (!CModule::IncludeModule("iblock")) {
            return $cache[$cacheKey];
        }

        $res = CIBlock::GetList(array(), array("=CODE" => $code), false);
        if ($row = $res->Fetch()) {
            $cache[$cacheKey] = (int)$row["ID"];
        }

        return $cache[$cacheKey];
    }
}

if (!function_exists("szcubeNormalizeApartmentCodePart")) {
    function szcubeNormalizeApartmentCodePart($value)
    {
        $value = trim((string)$value);
        $value = mb_strtolower($value);
        $value = preg_replace("/[^a-z0-9_-]+/u", "-", $value);
        $value = preg_replace("/-+/u", "-", $value);
        return trim((string)$value, "-");
    }
}

if (!function_exists("szcubeBuildApartmentCode")) {
    function szcubeBuildApartmentCode($projectCode, $apartmentNumber, $corpus = "")
    {
        $projectCode = szcubeNormalizeApartmentCodePart($projectCode);
        $apartmentNumber = szcubeNormalizeApartmentCodePart($apartmentNumber);
        $corpus = szcubeNormalizeApartmentCodePart($corpus);

        $parts = array($projectCode);
        if ($corpus !== "") {
            $parts[] = "c" . ltrim($corpus, "c");
        }
        $parts[] = $apartmentNumber;

        $parts = array_values(array_filter($parts, static function ($value) {
            return trim((string)$value) !== "";
        }));

        return implode("-", $parts);
    }
}

if (!function_exists("szcubeGetApartmentPropertyMap")) {
    function szcubeGetApartmentPropertyMap($iblockId)
    {
        static $cache = array();

        $iblockId = (int)$iblockId;
        if ($iblockId <= 0) {
            return array();
        }

        if (isset($cache[$iblockId])) {
            return $cache[$iblockId];
        }

        $cache[$iblockId] = array();
        if (!CModule::IncludeModule("iblock")) {
            return $cache[$iblockId];
        }

        $res = CIBlockProperty::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array("IBLOCK_ID" => $iblockId)
        );
        while ($row = $res->Fetch()) {
            $code = trim((string)$row["CODE"]);
            if ($code === "") {
                continue;
            }

            $cache[$iblockId][$code] = (int)$row["ID"];
        }

        return $cache[$iblockId];
    }
}

if (!function_exists("szcubeExtractSinglePropertyValueFromFields")) {
    function szcubeExtractSinglePropertyValueFromFields(array $fields, $propertyId)
    {
        $propertyId = (int)$propertyId;
        if ($propertyId <= 0 || !isset($fields["PROPERTY_VALUES"]) || !is_array($fields["PROPERTY_VALUES"])) {
            return null;
        }

        if (!array_key_exists($propertyId, $fields["PROPERTY_VALUES"])) {
            return null;
        }

        $value = $fields["PROPERTY_VALUES"][$propertyId];
        if (is_array($value) && array_key_exists("VALUE", $value)) {
            $value = $value["VALUE"];
        } elseif (is_array($value)) {
            foreach ($value as $item) {
                if (is_array($item) && array_key_exists("VALUE", $item)) {
                    $candidate = $item["VALUE"];
                } else {
                    $candidate = $item;
                }

                if ($candidate === null || $candidate === "" || $candidate === false) {
                    continue;
                }

                return trim((string)$candidate);
            }

            return "";
        }

        return trim((string)$value);
    }
}

if (!function_exists("szcubeGetElementPropertyValueByCode")) {
    function szcubeGetElementPropertyValueByCode($iblockId, $elementId, $propertyCode)
    {
        $iblockId = (int)$iblockId;
        $elementId = (int)$elementId;
        $propertyCode = trim((string)$propertyCode);
        if ($iblockId <= 0 || $elementId <= 0 || $propertyCode === "") {
            return "";
        }

        if (!CModule::IncludeModule("iblock")) {
            return "";
        }

        $res = CIBlockElement::GetProperty(
            $iblockId,
            $elementId,
            array("SORT" => "ASC", "ID" => "ASC"),
            array("CODE" => $propertyCode)
        );
        if ($row = $res->Fetch()) {
            return trim((string)$row["VALUE"]);
        }

        return "";
    }
}

if (!function_exists("szcubeResolveApartmentCodeFromFields")) {
    function szcubeResolveApartmentCodeFromFields(array $fields, $existingElementId = 0)
    {
        $identity = szcubeResolveApartmentIdentityFromFields($fields, $existingElementId);
        if (empty($identity)) {
            return "";
        }

        return szcubeBuildApartmentCode(
            isset($identity["project_code"]) ? $identity["project_code"] : "",
            isset($identity["apartment_number"]) ? $identity["apartment_number"] : "",
            isset($identity["corpus"]) ? $identity["corpus"] : ""
        );
    }
}

if (!function_exists("szcubeEnsureApartmentCodeBeforeSave")) {
    function szcubeEnsureApartmentCodeBeforeSave(&$fields)
    {
        $iblockId = isset($fields["IBLOCK_ID"]) ? (int)$fields["IBLOCK_ID"] : 0;
        if ($iblockId <= 0 || $iblockId !== szcubeGetIblockIdByCode("apartments")) {
            return;
        }

        $elementId = isset($fields["ID"]) ? (int)$fields["ID"] : 0;
        $generatedCode = szcubeResolveApartmentCodeFromFields($fields, $elementId);
        if ($generatedCode !== "") {
            $fields["CODE"] = $generatedCode;
            return;
        }

        if (isset($fields["CODE"]) && trim((string)$fields["CODE"]) !== "") {
            $fields["CODE"] = szcubeNormalizeApartmentCodePart($fields["CODE"]);
        }
    }
}

if (!function_exists("szcubeResolveApartmentIdentityFromFields")) {
    function szcubeResolveApartmentIdentityFromFields(array $fields, $existingElementId = 0)
    {
        $iblockId = isset($fields["IBLOCK_ID"]) ? (int)$fields["IBLOCK_ID"] : 0;
        if ($iblockId <= 0 || $iblockId !== szcubeGetIblockIdByCode("apartments")) {
            return array();
        }

        $propertyMap = szcubeGetApartmentPropertyMap($iblockId);
        $projectId = isset($propertyMap["PROJECT"]) ? (int)szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["PROJECT"]) : 0;
        $corpus = isset($propertyMap["CORPUS"]) ? (string)szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["CORPUS"]) : "";
        $entrance = isset($propertyMap["ENTRANCE"]) ? (string)szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["ENTRANCE"]) : "";
        $floor = isset($propertyMap["FLOOR"]) ? (int)szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["FLOOR"]) : 0;
        $apartmentNumber = isset($propertyMap["APARTMENT_NUMBER"]) ? (string)szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["APARTMENT_NUMBER"]) : "";

        $existingElementId = (int)$existingElementId;
        if ($existingElementId > 0) {
            if ($projectId <= 0) {
                $projectId = (int)szcubeGetElementPropertyValueByCode($iblockId, $existingElementId, "PROJECT");
            }
            if ($corpus === "") {
                $corpus = szcubeGetElementPropertyValueByCode($iblockId, $existingElementId, "CORPUS");
            }
            if ($entrance === "") {
                $entrance = szcubeGetElementPropertyValueByCode($iblockId, $existingElementId, "ENTRANCE");
            }
            if ($floor <= 0) {
                $floor = (int)szcubeGetElementPropertyValueByCode($iblockId, $existingElementId, "FLOOR");
            }
            if ($apartmentNumber === "") {
                $apartmentNumber = szcubeGetElementPropertyValueByCode($iblockId, $existingElementId, "APARTMENT_NUMBER");
            }
        }

        $projectCode = $projectId > 0 ? szcubeGetProjectCodeById($projectId) : "";

        return array(
            "iblock_id" => $iblockId,
            "project_id" => $projectId,
            "project_code" => $projectCode,
            "corpus" => trim((string)$corpus),
            "entrance" => trim((string)$entrance),
            "floor" => (int)$floor,
            "apartment_number" => trim((string)$apartmentNumber),
        );
    }
}

if (!function_exists("szcubeBuildApartmentXmlId")) {
    function szcubeBuildApartmentXmlId($projectCode, $entrance, $floor, $apartmentNumber, $corpus = "")
    {
        $projectCode = szcubeNormalizeApartmentCodePart($projectCode);
        $entrance = szcubeNormalizeApartmentCodePart($entrance);
        $apartmentNumber = szcubeNormalizeApartmentCodePart($apartmentNumber);
        $corpus = szcubeNormalizeApartmentCodePart($corpus);
        $floor = (int)$floor;

        $parts = array($projectCode);
        if ($corpus !== "") {
            $parts[] = "c" . ltrim($corpus, "c");
        }
        if ($entrance !== "") {
            $parts[] = $entrance;
        }
        if ($floor > 0) {
            $parts[] = (string)$floor;
        }
        if ($apartmentNumber !== "") {
            $parts[] = $apartmentNumber;
        }

        $parts = array_values(array_filter($parts, static function ($value) {
            return trim((string)$value) !== "";
        }));

        return implode("-", $parts);
    }
}

if (!function_exists("szcubeEnsureApartmentXmlIdBeforeSave")) {
    function szcubeEnsureApartmentXmlIdBeforeSave(&$fields)
    {
        $identity = szcubeResolveApartmentIdentityFromFields($fields, isset($fields["ID"]) ? (int)$fields["ID"] : 0);
        if (empty($identity)) {
            return;
        }

        $generatedXmlId = szcubeBuildApartmentXmlId(
            isset($identity["project_code"]) ? $identity["project_code"] : "",
            isset($identity["entrance"]) ? $identity["entrance"] : "",
            isset($identity["floor"]) ? (int)$identity["floor"] : 0,
            isset($identity["apartment_number"]) ? $identity["apartment_number"] : "",
            isset($identity["corpus"]) ? $identity["corpus"] : ""
        );
        if ($generatedXmlId !== "") {
            $fields["XML_ID"] = $generatedXmlId;
        }
    }
}

if (!function_exists("szcubeGetApartmentSectionEntityId")) {
    function szcubeGetApartmentSectionEntityId($iblockId)
    {
        $iblockId = (int)$iblockId;
        return $iblockId > 0 ? ("IBLOCK_" . $iblockId . "_SECTION") : "";
    }
}

if (!function_exists("szcubeGetUserFieldEnumIdByXmlId")) {
    function szcubeGetUserFieldEnumIdByXmlId($entityId, $fieldName, $xmlId)
    {
        static $cache = array();

        $entityId = trim((string)$entityId);
        $fieldName = trim((string)$fieldName);
        $xmlId = trim((string)$xmlId);
        if ($entityId === "" || $fieldName === "" || $xmlId === "") {
            return 0;
        }

        $cacheKey = $entityId . ":" . $fieldName . ":" . $xmlId;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = 0;
        $userFieldRes = CUserTypeEntity::GetList(
            array("ID" => "ASC"),
            array(
                "ENTITY_ID" => $entityId,
                "FIELD_NAME" => $fieldName,
            )
        );
        if (!($userField = $userFieldRes->Fetch())) {
            return 0;
        }

        $enumRes = CUserFieldEnum::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array("USER_FIELD_ID" => (int)$userField["ID"])
        );
        while ($row = $enumRes->Fetch()) {
            if (trim((string)$row["XML_ID"]) === $xmlId) {
                $cache[$cacheKey] = (int)$row["ID"];
                break;
            }
        }

        return $cache[$cacheKey];
    }
}

if (!function_exists("szcubeFindSectionByCodeAndParent")) {
    function szcubeFindSectionByCodeAndParent($iblockId, $parentId, $code)
    {
        $res = CIBlockSection::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => (int)$iblockId,
                "SECTION_ID" => (int)$parentId > 0 ? (int)$parentId : false,
                "=CODE" => (string)$code,
            ),
            false,
            array("ID", "NAME", "CODE", "UF_NODE_TYPE", "UF_ENTRANCE_NUMBER", "UF_FLOOR_NUMBER")
        );

        if ($row = $res->Fetch()) {
            return $row;
        }

        return null;
    }
}

if (!function_exists("szcubeGetProjectNameById")) {
    function szcubeGetProjectNameById($projectId)
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
            array("ID", "NAME")
        );
        if ($row = $res->Fetch()) {
            $cache[$projectId] = trim((string)$row["NAME"]);
        }

        return $cache[$projectId];
    }
}

if (!function_exists("szcubeEnsureApartmentSection")) {
    function szcubeEnsureApartmentSection($iblockId, $parentId, $code, $name, array $fields)
    {
        $iblockId = (int)$iblockId;
        $parentId = (int)$parentId;
        $code = trim((string)$code);
        $name = trim((string)$name);
        if ($iblockId <= 0 || $code === "" || $name === "") {
            return 0;
        }

        $sectionApi = new CIBlockSection();
        $existing = szcubeFindSectionByCodeAndParent($iblockId, $parentId, $code);
        $updateFields = array(
            "NAME" => $name,
            "ACTIVE" => "Y",
        ) + $fields;

        if (is_array($existing)) {
            $sectionApi->Update((int)$existing["ID"], $updateFields);
            return (int)$existing["ID"];
        }

        $newId = (int)$sectionApi->Add(array(
            "IBLOCK_ID" => $iblockId,
            "IBLOCK_SECTION_ID" => $parentId > 0 ? $parentId : false,
            "ACTIVE" => "Y",
            "NAME" => $name,
            "CODE" => $code,
        ) + $fields);

        return $newId > 0 ? $newId : 0;
    }
}

if (!function_exists("szcubeEnsureApartmentSectionBeforeSave")) {
    function szcubeEnsureApartmentSectionBeforeSave(&$fields)
    {
        $identity = szcubeResolveApartmentIdentityFromFields($fields, isset($fields["ID"]) ? (int)$fields["ID"] : 0);
        if (empty($identity)) {
            return;
        }

        $iblockId = isset($identity["iblock_id"]) ? (int)$identity["iblock_id"] : 0;
        $projectId = isset($identity["project_id"]) ? (int)$identity["project_id"] : 0;
        $projectCode = isset($identity["project_code"]) ? trim((string)$identity["project_code"]) : "";
        $entrance = isset($identity["entrance"]) ? trim((string)$identity["entrance"]) : "";
        $floor = isset($identity["floor"]) ? (int)$identity["floor"] : 0;
        if ($iblockId <= 0 || $projectId <= 0 || $projectCode === "" || $entrance === "" || $floor <= 0) {
            return;
        }

        $sectionEntityId = szcubeGetApartmentSectionEntityId($iblockId);
        $projectNodeTypeId = szcubeGetUserFieldEnumIdByXmlId($sectionEntityId, "UF_NODE_TYPE", "project");
        $entranceNodeTypeId = szcubeGetUserFieldEnumIdByXmlId($sectionEntityId, "UF_NODE_TYPE", "entrance");
        $floorNodeTypeId = szcubeGetUserFieldEnumIdByXmlId($sectionEntityId, "UF_NODE_TYPE", "floor");

        $projectSectionId = szcubeGetDynamicSectionIdByProjectCode($iblockId, $projectCode);
        if ($projectSectionId <= 0) {
            $projectSectionId = szcubeEnsureApartmentSection(
                $iblockId,
                0,
                $projectCode,
                szcubeGetProjectNameById($projectId) !== "" ? szcubeGetProjectNameById($projectId) : $projectCode,
                array(
                    "SORT" => 500,
                    "UF_NODE_TYPE" => $projectNodeTypeId > 0 ? $projectNodeTypeId : false,
                )
            );
        }
        if ($projectSectionId <= 0) {
            return;
        }

        $entranceSectionId = szcubeEnsureApartmentSection(
            $iblockId,
            $projectSectionId,
            "podezd-" . szcubeNormalizeApartmentCodePart($entrance),
            "Подъезд " . $entrance,
            array(
                "SORT" => ((int)$entrance > 0 ? (int)$entrance : 1) * 100,
                "UF_NODE_TYPE" => $entranceNodeTypeId > 0 ? $entranceNodeTypeId : false,
                "UF_ENTRANCE_NUMBER" => $entrance,
                "UF_PIN_LABEL" => $entrance . " подъезд",
            )
        );
        if ($entranceSectionId <= 0) {
            return;
        }

        $floorSectionId = szcubeEnsureApartmentSection(
            $iblockId,
            $entranceSectionId,
            sprintf("floor-%02d", $floor),
            $floor . " этаж",
            array(
                "SORT" => $floor * 100,
                "UF_NODE_TYPE" => $floorNodeTypeId > 0 ? $floorNodeTypeId : false,
                "UF_FLOOR_NUMBER" => $floor,
            )
        );
        if ($floorSectionId <= 0) {
            return;
        }

        $fields["IBLOCK_SECTION_ID"] = $floorSectionId;
        $fields["IBLOCK_SECTION"] = array($floorSectionId);
    }
}

if (!function_exists("szcubePrepareApartmentBeforeSave")) {
    function szcubePrepareApartmentBeforeSave(&$fields)
    {
        szcubeEnsureApartmentCodeBeforeSave($fields);
        szcubeEnsureApartmentXmlIdBeforeSave($fields);
        szcubeEnsureApartmentSectionBeforeSave($fields);
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

AddEventHandler("iblock", "OnBeforeIBlockElementAdd", "szcubePrepareApartmentBeforeSave");
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", "szcubePrepareApartmentBeforeSave");
AddEventHandler("iblock", "OnAfterIBlockElementAdd", "szcubeSyncProjectDynamicElementSection");
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", "szcubeSyncProjectDynamicElementSection");

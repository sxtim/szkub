<?php

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Cookie;

if (!defined("SZCUBE_FAVORITES_TABLE")) {
    define("SZCUBE_FAVORITES_TABLE", "b_szcube_favorite");
}

if (!defined("SZCUBE_FAVORITES_COOKIE")) {
    define("SZCUBE_FAVORITES_COOKIE", "SZCUBE_FAVORITES_UID");
}

if (!defined("SZCUBE_FAVORITES_COOKIE_TTL")) {
    define("SZCUBE_FAVORITES_COOKIE_TTL", 31536000);
}

if (!function_exists("szcubeFavoritesAllowedTypes")) {
    function szcubeFavoritesAllowedTypes()
    {
        return array("apartment", "commercial", "parking", "storeroom");
    }
}

if (!function_exists("szcubeFavoritesNormalizeEntityType")) {
    function szcubeFavoritesNormalizeEntityType($type)
    {
        $type = trim(mb_strtolower((string)$type));
        return in_array($type, szcubeFavoritesAllowedTypes(), true) ? $type : "";
    }
}

if (!function_exists("szcubeFavoritesNormalizeEntityId")) {
    function szcubeFavoritesNormalizeEntityId($id)
    {
        $id = (int)$id;
        return $id > 0 ? $id : 0;
    }
}

if (!function_exists("szcubeFavoritesBuildKey")) {
    function szcubeFavoritesBuildKey($type, $id)
    {
        $type = szcubeFavoritesNormalizeEntityType($type);
        $id = szcubeFavoritesNormalizeEntityId($id);
        return $type !== "" && $id > 0 ? $type . ":" . $id : "";
    }
}

if (!function_exists("szcubeFavoritesParseKey")) {
    function szcubeFavoritesParseKey($key)
    {
        $key = trim((string)$key);
        if ($key === "" || strpos($key, ":") === false) {
            return null;
        }

        list($type, $id) = explode(":", $key, 2);
        $type = szcubeFavoritesNormalizeEntityType($type);
        $id = szcubeFavoritesNormalizeEntityId($id);

        if ($type === "" || $id <= 0) {
            return null;
        }

        return array("type" => $type, "id" => $id, "key" => $type . ":" . $id);
    }
}

if (!function_exists("szcubeFavoritesSiteId")) {
    function szcubeFavoritesSiteId()
    {
        return defined("SITE_ID") && trim((string)SITE_ID) !== "" ? trim((string)SITE_ID) : "s1";
    }
}

if (!function_exists("szcubeFavoritesIsHttps")) {
    function szcubeFavoritesIsHttps()
    {
        if (!empty($_SERVER["HTTPS"]) && strtolower((string)$_SERVER["HTTPS"]) !== "off") {
            return true;
        }

        if (!empty($_SERVER["HTTP_X_FORWARDED_PROTO"])) {
            return strtolower((string)$_SERVER["HTTP_X_FORWARDED_PROTO"]) === "https";
        }

        return false;
    }
}

if (!function_exists("szcubeFavoritesGenerateToken")) {
    function szcubeFavoritesGenerateToken()
    {
        if (function_exists("random_bytes")) {
            return bin2hex(random_bytes(16));
        }

        return md5(uniqid((string)mt_rand(), true));
    }
}

if (!function_exists("szcubeFavoritesGetVisitorToken")) {
    function szcubeFavoritesGetVisitorToken($create = false)
    {
        static $token = null;

        if ($token !== null) {
            return $token;
        }

        $rawToken = "";
        try {
            $request = Context::getCurrent()->getRequest();
            $rawToken = (string)$request->getCookie(SZCUBE_FAVORITES_COOKIE);
        } catch (\Throwable $e) {
            $rawToken = "";
        }

        if ($rawToken === "" && isset($_COOKIE[SZCUBE_FAVORITES_COOKIE])) {
            $rawToken = (string)$_COOKIE[SZCUBE_FAVORITES_COOKIE];
        }

        $rawToken = preg_replace("/[^a-f0-9]/i", "", $rawToken);
        if (is_string($rawToken) && strlen($rawToken) >= 24 && strlen($rawToken) <= 64) {
            $token = strtolower($rawToken);
            return $token;
        }

        if (!$create) {
            return "";
        }

        $token = szcubeFavoritesGenerateToken();
        $_COOKIE[SZCUBE_FAVORITES_COOKIE] = $token;

        try {
            $cookie = new Cookie(SZCUBE_FAVORITES_COOKIE, $token, time() + SZCUBE_FAVORITES_COOKIE_TTL);
            $cookie->setPath("/");
            $cookie->setHttpOnly(true);
            $cookie->setSecure(szcubeFavoritesIsHttps());
            if (method_exists($cookie, "setSameSite")) {
                $cookie->setSameSite("Lax");
            }
            Context::getCurrent()->getResponse()->addCookie($cookie);
        } catch (\Throwable $e) {
            // Fallback below sends the cookie for direct AJAX scripts that terminate before Bitrix response finalization.
        }

        if (!headers_sent()) {
            if (defined("PHP_VERSION_ID") && PHP_VERSION_ID >= 70300) {
                setcookie(
                    SZCUBE_FAVORITES_COOKIE,
                    $token,
                    array(
                        "expires" => time() + SZCUBE_FAVORITES_COOKIE_TTL,
                        "path" => "/",
                        "secure" => szcubeFavoritesIsHttps(),
                        "httponly" => true,
                        "samesite" => "Lax",
                    )
                );
            } else {
                setcookie(
                    SZCUBE_FAVORITES_COOKIE,
                    $token,
                    time() + SZCUBE_FAVORITES_COOKIE_TTL,
                    "/; SameSite=Lax",
                    "",
                    szcubeFavoritesIsHttps(),
                    true
                );
            }
        }

        return $token;
    }
}

if (!function_exists("szcubeFavoritesConnection")) {
    function szcubeFavoritesConnection()
    {
        return Application::getConnection();
    }
}

if (!function_exists("szcubeFavoritesTableExists")) {
    function szcubeFavoritesTableExists()
    {
        try {
            return szcubeFavoritesConnection()->isTableExists(SZCUBE_FAVORITES_TABLE);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (!function_exists("szcubeFavoritesEnsureTable")) {
    function szcubeFavoritesEnsureTable()
    {
        $connection = szcubeFavoritesConnection();
        if ($connection->isTableExists(SZCUBE_FAVORITES_TABLE)) {
            return true;
        }

        $connection->queryExecute(
            "CREATE TABLE IF NOT EXISTS `" . SZCUBE_FAVORITES_TABLE . "` (" .
            "`ID` int(11) NOT NULL AUTO_INCREMENT, " .
            "`SITE_ID` char(2) NOT NULL DEFAULT '', " .
            "`VISITOR_TOKEN` varchar(64) NOT NULL, " .
            "`ENTITY_TYPE` varchar(32) NOT NULL, " .
            "`ENTITY_ID` int(11) NOT NULL, " .
            "`CREATED_AT` datetime NOT NULL, " .
            "`UPDATED_AT` datetime NOT NULL, " .
            "PRIMARY KEY (`ID`), " .
            "UNIQUE KEY `ux_szcube_favorite_item` (`SITE_ID`, `VISITOR_TOKEN`, `ENTITY_TYPE`, `ENTITY_ID`), " .
            "KEY `ix_szcube_favorite_visitor_updated` (`SITE_ID`, `VISITOR_TOKEN`, `UPDATED_AT`)" .
            ") ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );

        return true;
    }
}

if (!function_exists("szcubeFavoritesSqlValue")) {
    function szcubeFavoritesSqlValue($value)
    {
        return "'" . szcubeFavoritesConnection()->getSqlHelper()->forSql((string)$value) . "'";
    }
}

if (!function_exists("szcubeFavoritesCurrentRows")) {
    function szcubeFavoritesCurrentRows($token)
    {
        $token = trim((string)$token);
        if ($token === "" || !szcubeFavoritesTableExists()) {
            return array();
        }

        $siteId = szcubeFavoritesSiteId();
        $rows = array();
        $res = szcubeFavoritesConnection()->query(
            "SELECT `ID`, `ENTITY_TYPE`, `ENTITY_ID`, `UPDATED_AT` FROM `" . SZCUBE_FAVORITES_TABLE . "` " .
            "WHERE `SITE_ID` = " . szcubeFavoritesSqlValue($siteId) . " " .
            "AND `VISITOR_TOKEN` = " . szcubeFavoritesSqlValue($token) . " " .
            "ORDER BY `UPDATED_AT` DESC, `ID` DESC"
        );

        while ($row = $res->fetch()) {
            $type = szcubeFavoritesNormalizeEntityType($row["ENTITY_TYPE"]);
            $id = szcubeFavoritesNormalizeEntityId($row["ENTITY_ID"]);
            $key = szcubeFavoritesBuildKey($type, $id);
            if ($key === "") {
                continue;
            }
            $row["KEY"] = $key;
            $row["ENTITY_TYPE"] = $type;
            $row["ENTITY_ID"] = $id;
            $rows[] = $row;
        }

        return $rows;
    }
}

if (!function_exists("szcubeFavoritesCount")) {
    function szcubeFavoritesCount($token = null)
    {
        $token = $token === null ? szcubeFavoritesGetVisitorToken(false) : trim((string)$token);
        if ($token === "" || !szcubeFavoritesTableExists()) {
            return 0;
        }

        $siteId = szcubeFavoritesSiteId();
        $res = szcubeFavoritesConnection()->query(
            "SELECT COUNT(1) AS CNT FROM `" . SZCUBE_FAVORITES_TABLE . "` " .
            "WHERE `SITE_ID` = " . szcubeFavoritesSqlValue($siteId) . " " .
            "AND `VISITOR_TOKEN` = " . szcubeFavoritesSqlValue($token)
        );
        $row = $res->fetch();

        return isset($row["CNT"]) ? (int)$row["CNT"] : 0;
    }
}

if (!function_exists("szcubeFavoritesContains")) {
    function szcubeFavoritesContains($token, $type, $id)
    {
        $token = trim((string)$token);
        $type = szcubeFavoritesNormalizeEntityType($type);
        $id = szcubeFavoritesNormalizeEntityId($id);
        if ($token === "" || $type === "" || $id <= 0 || !szcubeFavoritesTableExists()) {
            return false;
        }

        $siteId = szcubeFavoritesSiteId();
        $res = szcubeFavoritesConnection()->query(
            "SELECT `ID` FROM `" . SZCUBE_FAVORITES_TABLE . "` " .
            "WHERE `SITE_ID` = " . szcubeFavoritesSqlValue($siteId) . " " .
            "AND `VISITOR_TOKEN` = " . szcubeFavoritesSqlValue($token) . " " .
            "AND `ENTITY_TYPE` = " . szcubeFavoritesSqlValue($type) . " " .
            "AND `ENTITY_ID` = " . (int)$id . " LIMIT 1"
        );

        return (bool)$res->fetch();
    }
}

if (!function_exists("szcubeFavoritesAdd")) {
    function szcubeFavoritesAdd($token, $type, $id)
    {
        $type = szcubeFavoritesNormalizeEntityType($type);
        $id = szcubeFavoritesNormalizeEntityId($id);
        $token = trim((string)$token);
        if ($token === "" || $type === "" || $id <= 0) {
            return false;
        }

        szcubeFavoritesEnsureTable();
        $siteId = szcubeFavoritesSiteId();
        $now = date("Y-m-d H:i:s");

        if (szcubeFavoritesContains($token, $type, $id)) {
            szcubeFavoritesConnection()->queryExecute(
                "UPDATE `" . SZCUBE_FAVORITES_TABLE . "` SET `UPDATED_AT` = " . szcubeFavoritesSqlValue($now) . " " .
                "WHERE `SITE_ID` = " . szcubeFavoritesSqlValue($siteId) . " " .
                "AND `VISITOR_TOKEN` = " . szcubeFavoritesSqlValue($token) . " " .
                "AND `ENTITY_TYPE` = " . szcubeFavoritesSqlValue($type) . " " .
                "AND `ENTITY_ID` = " . (int)$id
            );
            return true;
        }

        try {
            szcubeFavoritesConnection()->queryExecute(
                "INSERT INTO `" . SZCUBE_FAVORITES_TABLE . "` (`SITE_ID`, `VISITOR_TOKEN`, `ENTITY_TYPE`, `ENTITY_ID`, `CREATED_AT`, `UPDATED_AT`) " .
                "VALUES (" .
                szcubeFavoritesSqlValue($siteId) . ", " .
                szcubeFavoritesSqlValue($token) . ", " .
                szcubeFavoritesSqlValue($type) . ", " .
                (int)$id . ", " .
                szcubeFavoritesSqlValue($now) . ", " .
                szcubeFavoritesSqlValue($now) .
                ")"
            );
        } catch (\Throwable $e) {
            return szcubeFavoritesContains($token, $type, $id);
        }

        return true;
    }
}

if (!function_exists("szcubeFavoritesRemove")) {
    function szcubeFavoritesRemove($token, $type, $id)
    {
        $type = szcubeFavoritesNormalizeEntityType($type);
        $id = szcubeFavoritesNormalizeEntityId($id);
        $token = trim((string)$token);
        if ($token === "" || $type === "" || $id <= 0 || !szcubeFavoritesTableExists()) {
            return false;
        }

        $siteId = szcubeFavoritesSiteId();
        szcubeFavoritesConnection()->queryExecute(
            "DELETE FROM `" . SZCUBE_FAVORITES_TABLE . "` " .
            "WHERE `SITE_ID` = " . szcubeFavoritesSqlValue($siteId) . " " .
            "AND `VISITOR_TOKEN` = " . szcubeFavoritesSqlValue($token) . " " .
            "AND `ENTITY_TYPE` = " . szcubeFavoritesSqlValue($type) . " " .
            "AND `ENTITY_ID` = " . (int)$id
        );

        return true;
    }
}

if (!function_exists("szcubeFavoritesFormatMoney")) {
    function szcubeFavoritesFormatMoney($value)
    {
        $value = (float)$value;
        return $value > 0 ? number_format($value, 0, ".", " ") . " ₽" : "";
    }
}

if (!function_exists("szcubeFavoritesFormatArea")) {
    function szcubeFavoritesFormatArea($value)
    {
        $value = (float)$value;
        if ($value <= 0) {
            return "";
        }

        return rtrim(rtrim(number_format($value, 1, ".", " "), "0"), ".") . " м²";
    }
}

if (!function_exists("szcubeFavoritesPropertySingleValue")) {
    function szcubeFavoritesPropertySingleValue($property, $default = "")
    {
        if (!is_array($property) || !isset($property["VALUE"])) {
            return $default;
        }

        $value = is_array($property["VALUE"]) ? reset($property["VALUE"]) : $property["VALUE"];
        $value = trim((string)$value);

        return $value !== "" ? $value : $default;
    }
}

if (!function_exists("szcubeFavoritesPropertySingleXmlId")) {
    function szcubeFavoritesPropertySingleXmlId($property, $default = "")
    {
        if (!is_array($property) || !isset($property["VALUE_XML_ID"])) {
            return $default;
        }

        $value = is_array($property["VALUE_XML_ID"]) ? reset($property["VALUE_XML_ID"]) : $property["VALUE_XML_ID"];
        $value = trim((string)$value);

        return $value !== "" ? $value : $default;
    }
}

if (!function_exists("szcubeFavoritesPropertyMultipleValues")) {
    function szcubeFavoritesPropertyMultipleValues($property)
    {
        if (!is_array($property) || !isset($property["VALUE"])) {
            return array();
        }

        $values = is_array($property["VALUE"]) ? $property["VALUE"] : array($property["VALUE"]);
        $result = array();
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value !== "") {
                $result[] = $value;
            }
        }

        return array_values(array_unique($result));
    }
}

if (!function_exists("szcubeFavoritesPropertyFilePath")) {
    function szcubeFavoritesPropertyFilePath($value)
    {
        $fileId = (int)$value;
        if ($fileId <= 0 || !class_exists("CFile")) {
            return "";
        }

        $path = CFile::GetPath($fileId);
        return $path ? (string)$path : "";
    }
}

if (!function_exists("szcubeFavoritesIsHiddenStatus")) {
    function szcubeFavoritesIsHiddenStatus($statusKey, $statusLabel = "")
    {
        $statusKey = trim(mb_strtolower((string)$statusKey));
        $statusLabel = trim(mb_strtolower((string)$statusLabel));

        if ($statusKey === "sold") {
            return true;
        }

        return $statusLabel !== "" && preg_match("/^продан[а-я]*$/u", $statusLabel) === 1;
    }
}

if (!function_exists("szcubeFavoritesIblockByCode")) {
    function szcubeFavoritesIblockByCode($code)
    {
        static $cache = array();

        $code = trim((string)$code);
        if ($code === "") {
            return null;
        }

        if (isset($cache[$code])) {
            return $cache[$code];
        }

        $cache[$code] = null;
        if (!Loader::includeModule("iblock")) {
            return null;
        }

        $res = CIBlock::GetList(array(), array("=CODE" => $code), false);
        $row = $res ? $res->Fetch() : false;
        if (is_array($row)) {
            $cache[$code] = $row;
        }

        return $cache[$code];
    }
}

if (!function_exists("szcubeFavoritesProjectMap")) {
    function szcubeFavoritesProjectMap()
    {
        static $map = null;

        if ($map !== null) {
            return $map;
        }

        $map = array();
        $iblock = szcubeFavoritesIblockByCode("projects");
        if (!$iblock) {
            return $map;
        }

        $res = CIBlockElement::GetList(
            array(),
            array("IBLOCK_ID" => (int)$iblock["ID"], "ACTIVE" => "Y"),
            false,
            false,
            array("ID", "NAME", "CODE")
        );
        while ($element = $res->GetNextElement()) {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();
            $id = (int)$fields["ID"];
            $code = trim((string)$fields["CODE"]);
            $map[$id] = array(
                "id" => $id,
                "name" => trim((string)$fields["NAME"]),
                "code" => $code,
                "url" => $code !== "" ? "/projects/" . $code . "/" : "",
                "delivery" => isset($properties["DELIVERY_TEXT"]["VALUE"]) ? trim((string)$properties["DELIVERY_TEXT"]["VALUE"]) : "",
            );
        }

        return $map;
    }
}

if (!function_exists("szcubeFavoritesLoadEntities")) {
    function szcubeFavoritesLoadEntities($type, array $ids)
    {
        $type = szcubeFavoritesNormalizeEntityType($type);
        $ids = array_values(array_unique(array_filter(array_map("intval", $ids))));
        if ($type === "" || empty($ids) || !Loader::includeModule("iblock")) {
            return array();
        }

        $iblockCodes = array(
            "apartment" => "apartments",
            "commercial" => "commercial",
            "parking" => "parking",
            "storeroom" => "storerooms",
        );
        $iblock = szcubeFavoritesIblockByCode($iblockCodes[$type]);
        if (!$iblock) {
            return array();
        }

        $projects = szcubeFavoritesProjectMap();
        $items = array();
        $res = CIBlockElement::GetList(
            array("ID" => "ASC"),
            array("IBLOCK_ID" => (int)$iblock["ID"], "ACTIVE" => "Y", "ID" => $ids),
            false,
            false,
            array("ID", "IBLOCK_ID", "NAME", "CODE", "DETAIL_PAGE_URL", "PREVIEW_PICTURE")
        );

        while ($element = $res->GetNextElement()) {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();
            $id = (int)$fields["ID"];
            $projectId = isset($properties["PROJECT"]["VALUE"]) ? (int)$properties["PROJECT"]["VALUE"] : 0;
            $project = isset($projects[$projectId]) ? $projects[$projectId] : array("name" => "", "url" => "", "delivery" => "");
            $url = trim((string)$fields["DETAIL_PAGE_URL"]);
            if ($url === "" || strpos($url, "#") !== false) {
                $prefix = $type === "commercial" ? "/commerce/" : "/apartments/";
                if ($type === "parking") {
                    $url = "/parking/";
                } elseif ($type === "storeroom") {
                    $url = "/storerooms/";
                } else {
                    $url = $prefix . trim((string)$fields["CODE"]) . "/";
                }
            }

            $statusLabel = szcubeFavoritesPropertySingleValue(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
            $statusKey = szcubeFavoritesPropertySingleXmlId(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
            if (($type === "apartment" || $type === "commercial") && szcubeFavoritesIsHiddenStatus($statusKey, $statusLabel)) {
                continue;
            }
            $badges = szcubeFavoritesPropertyMultipleValues(isset($properties["BADGES"]) ? $properties["BADGES"] : array());

            $title = trim((string)$fields["NAME"]);
            $entityLabel = "";
            $area = 0.0;
            $price = 0.0;
            $priceOld = 0.0;
            $image = "";

            if ($type === "apartment") {
                $rooms = szcubeFavoritesPropertySingleValue(isset($properties["ROOMS"]) ? $properties["ROOMS"] : array(), "Квартира");
                $area = isset($properties["AREA_TOTAL"]["VALUE"]) ? (float)$properties["AREA_TOTAL"]["VALUE"] : 0.0;
                $entityLabel = trim($rooms . ($area > 0 ? ", " . szcubeFavoritesFormatArea($area) : ""));
                $price = isset($properties["PRICE_TOTAL"]["VALUE"]) ? (float)$properties["PRICE_TOTAL"]["VALUE"] : 0.0;
                $priceOld = isset($properties["PRICE_OLD"]["VALUE"]) ? (float)$properties["PRICE_OLD"]["VALUE"] : 0.0;
                $image = szcubeFavoritesPropertyFilePath(isset($properties["PLAN_IMAGE"]["VALUE"]) ? $properties["PLAN_IMAGE"]["VALUE"] : 0);
            } elseif ($type === "commercial") {
                $entityLabel = szcubeFavoritesPropertySingleValue(isset($properties["COMMERCIAL_TYPE"]) ? $properties["COMMERCIAL_TYPE"] : array(), "Коммерческое помещение");
                $area = isset($properties["AREA_TOTAL"]["VALUE"]) ? (float)$properties["AREA_TOTAL"]["VALUE"] : 0.0;
                if ($area > 0) {
                    $entityLabel .= ", " . szcubeFavoritesFormatArea($area);
                }
                $price = isset($properties["PRICE_TOTAL"]["VALUE"]) ? (float)$properties["PRICE_TOTAL"]["VALUE"] : 0.0;
                $priceOld = isset($properties["PRICE_OLD"]["VALUE"]) ? (float)$properties["PRICE_OLD"]["VALUE"] : 0.0;
                $image = szcubeFavoritesPropertyFilePath(isset($properties["PLAN_IMAGE"]["VALUE"]) ? $properties["PLAN_IMAGE"]["VALUE"] : 0);
                if ($image === "" && (int)$fields["PREVIEW_PICTURE"] > 0) {
                    $image = (string)CFile::GetPath((int)$fields["PREVIEW_PICTURE"]);
                }
            } elseif ($type === "parking") {
                $number = szcubeFavoritesPropertySingleValue(isset($properties["PARKING_NUMBER"]) ? $properties["PARKING_NUMBER"] : array(), $title);
                $title = preg_match("/№/u", $number) ? $number : "Парковочное место №" . $number;
                $entityLabel = szcubeFavoritesPropertySingleValue(isset($properties["PARKING_TYPE"]) ? $properties["PARKING_TYPE"] : array(), "Паркинг");
                $area = isset($properties["AREA_TOTAL"]["VALUE"]) ? (float)$properties["AREA_TOTAL"]["VALUE"] : 0.0;
                if ($area > 0) {
                    $entityLabel .= ", " . szcubeFavoritesFormatArea($area);
                }
                $price = isset($properties["PRICE_TOTAL"]["VALUE"]) ? (float)$properties["PRICE_TOTAL"]["VALUE"] : 0.0;
                $priceOld = isset($properties["PRICE_OLD"]["VALUE"]) ? (float)$properties["PRICE_OLD"]["VALUE"] : 0.0;
            } elseif ($type === "storeroom") {
                $number = szcubeFavoritesPropertySingleValue(isset($properties["STOREROOM_NUMBER"]) ? $properties["STOREROOM_NUMBER"] : array(), $title);
                $title = preg_match("/№/u", $number) || preg_match("/кладов/iu", $number) ? $number : "Кладовка №" . $number;
                $entityLabel = "Кладовое помещение";
                $area = isset($properties["AREA_TOTAL"]["VALUE"]) ? (float)$properties["AREA_TOTAL"]["VALUE"] : 0.0;
                if ($area > 0) {
                    $entityLabel .= ", " . szcubeFavoritesFormatArea($area);
                }
                $price = isset($properties["PRICE_TOTAL"]["VALUE"]) ? (float)$properties["PRICE_TOTAL"]["VALUE"] : 0.0;
                $priceOld = isset($properties["PRICE_OLD"]["VALUE"]) ? (float)$properties["PRICE_OLD"]["VALUE"] : 0.0;
            }

            $items[$id] = array(
                "key" => szcubeFavoritesBuildKey($type, $id),
                "entity_type" => $type,
                "entity_id" => $id,
                "code" => trim((string)$fields["CODE"]),
                "title" => $title,
                "label" => $entityLabel,
                "url" => $url,
                "project_name" => isset($project["name"]) ? $project["name"] : "",
                "project_code" => isset($project["code"]) ? $project["code"] : "",
                "project_url" => isset($project["url"]) ? $project["url"] : "",
                "project_filter_url" => $type === "apartment" && isset($project["code"]) && $project["code"] !== ""
                    ? "/projects/detail.php?code=" . rawurlencode((string)$project["code"])
                    : "",
                "project_delivery" => isset($project["delivery"]) ? $project["delivery"] : "",
                "status_label" => $statusLabel,
                "status_key" => $statusKey,
                "badges" => $badges,
                "price_total" => $price,
                "price_total_formatted" => szcubeFavoritesFormatMoney($price),
                "price_old" => $priceOld,
                "price_old_formatted" => szcubeFavoritesFormatMoney($priceOld),
                "image" => $image,
                "image_alt" => $title,
            );
        }

        return $items;
    }
}

if (!function_exists("szcubeFavoritesEntityExists")) {
    function szcubeFavoritesEntityExists($type, $id)
    {
        $items = szcubeFavoritesLoadEntities($type, array($id));
        return isset($items[(int)$id]);
    }
}

if (!function_exists("szcubeFavoritesToggle")) {
    function szcubeFavoritesToggle($type, $id)
    {
        $type = szcubeFavoritesNormalizeEntityType($type);
        $id = szcubeFavoritesNormalizeEntityId($id);
        if ($type === "" || $id <= 0) {
            return array("success" => false, "message" => "Некорректный объект избранного.");
        }

        if (!szcubeFavoritesEntityExists($type, $id)) {
            return array("success" => false, "message" => "Объект не найден или недоступен.");
        }

        $token = szcubeFavoritesGetVisitorToken(true);
        if (szcubeFavoritesContains($token, $type, $id)) {
            szcubeFavoritesRemove($token, $type, $id);
            $inFavorite = false;
        } else {
            szcubeFavoritesAdd($token, $type, $id);
            $inFavorite = true;
        }

        return array(
            "success" => true,
            "item" => array(
                "key" => szcubeFavoritesBuildKey($type, $id),
                "entity_type" => $type,
                "entity_id" => $id,
                "in_favorite" => $inFavorite,
            ),
            "count" => szcubeFavoritesCount($token),
        );
    }
}

if (!function_exists("szcubeFavoritesState")) {
    function szcubeFavoritesState(array $keys)
    {
        $normalized = array();
        foreach ($keys as $key) {
            $parsed = szcubeFavoritesParseKey($key);
            if ($parsed) {
                $normalized[$parsed["key"]] = $parsed;
            }
        }

        $token = szcubeFavoritesGetVisitorToken(false);
        $active = array();
        if ($token !== "") {
            foreach (szcubeFavoritesCurrentRows($token) as $row) {
                $active[$row["KEY"]] = true;
            }
        }

        $items = array();
        foreach ($normalized as $key => $parsed) {
            $items[$key] = array(
                "key" => $key,
                "entity_type" => $parsed["type"],
                "entity_id" => $parsed["id"],
                "in_favorite" => isset($active[$key]),
            );
        }

        return array(
            "success" => true,
            "items" => $items,
            "count" => $token !== "" ? szcubeFavoritesCount($token) : 0,
        );
    }
}

if (!function_exists("szcubeFavoritesCleanupRows")) {
    function szcubeFavoritesCleanupRows(array $rows, array $validKeys)
    {
        if (empty($rows) || !szcubeFavoritesTableExists()) {
            return;
        }

        $valid = array_fill_keys($validKeys, true);
        $ids = array();
        foreach ($rows as $row) {
            if (!isset($valid[$row["KEY"]])) {
                $ids[] = (int)$row["ID"];
            }
        }

        $ids = array_values(array_filter($ids));
        if (empty($ids)) {
            return;
        }

        szcubeFavoritesConnection()->queryExecute(
            "DELETE FROM `" . SZCUBE_FAVORITES_TABLE . "` WHERE `ID` IN (" . implode(",", $ids) . ")"
        );
    }
}

if (!function_exists("szcubeFavoritesList")) {
    function szcubeFavoritesList()
    {
        $token = szcubeFavoritesGetVisitorToken(false);
        if ($token === "") {
            return array("success" => true, "items" => array(), "groups" => array(), "count" => 0);
        }

        $rows = szcubeFavoritesCurrentRows($token);
        $idsByType = array();
        foreach ($rows as $row) {
            $idsByType[$row["ENTITY_TYPE"]][] = (int)$row["ENTITY_ID"];
        }

        $entitiesByKey = array();
        foreach ($idsByType as $type => $ids) {
            foreach (szcubeFavoritesLoadEntities($type, $ids) as $id => $item) {
                $entitiesByKey[szcubeFavoritesBuildKey($type, $id)] = $item;
            }
        }

        $items = array();
        foreach ($rows as $row) {
            if (isset($entitiesByKey[$row["KEY"]])) {
                $item = $entitiesByKey[$row["KEY"]];
                $item["updated_at"] = isset($row["UPDATED_AT"]) ? (string)$row["UPDATED_AT"] : "";
                $items[] = $item;
            }
        }

        szcubeFavoritesCleanupRows($rows, array_keys($entitiesByKey));

        $groups = array();
        foreach ($items as $item) {
            $groups[$item["entity_type"]][] = $item;
        }

        return array(
            "success" => true,
            "items" => $items,
            "groups" => $groups,
            "count" => count($items),
        );
    }
}

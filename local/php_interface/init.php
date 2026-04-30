<?php

$projectSelectorSceneConfigPath = rtrim((string)($_SERVER["DOCUMENT_ROOT"] ?? ""), "/") . "/local/php_interface/project_selector_scene_config.php";
if ($projectSelectorSceneConfigPath !== "/local/php_interface/project_selector_scene_config.php" && is_file($projectSelectorSceneConfigPath)) {
    require_once $projectSelectorSceneConfigPath;
}

$szcubeLeadsConfigPath = rtrim((string)($_SERVER["DOCUMENT_ROOT"] ?? ""), "/") . "/local/php_interface/szcube_leads.php";
if ($szcubeLeadsConfigPath !== "/local/php_interface/szcube_leads.php" && is_file($szcubeLeadsConfigPath)) {
    require_once $szcubeLeadsConfigPath;
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

if (!function_exists("szcubeGetActiveProjectNavigationItems")) {
    function szcubeGetActiveProjectNavigationItems()
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = array();

        if (!CModule::IncludeModule("iblock")) {
            return $cache;
        }

        $iblockId = szcubeGetIblockIdByCode("projects");
        if ($iblockId <= 0) {
            return $cache;
        }

        $detailTemplate = "";
        $iblockRes = CIBlock::GetList(array(), array("ID" => $iblockId), false);
        if ($iblock = $iblockRes->Fetch()) {
            $detailTemplate = isset($iblock["DETAIL_PAGE_URL"]) ? trim((string)$iblock["DETAIL_PAGE_URL"]) : "";
        }

        $projectRes = CIBlockElement::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "ACTIVE" => "Y",
            ),
            false,
            false,
            array("ID", "NAME", "CODE")
        );

        while ($project = $projectRes->Fetch()) {
            $code = trim((string)$project["CODE"]);
            $name = trim((string)$project["NAME"]);

            if ($code === "" || $name === "") {
                continue;
            }

            $url = $detailTemplate !== ""
                ? trim((string)CIBlock::ReplaceDetailUrl($detailTemplate, $project, false, "E"))
                : "";

            if ($url === "" || strpos($url, "#") !== false) {
                $url = "/projects/" . $code . "/";
            }

            $cache[] = array(
                "code" => $code,
                "label" => "ЖК " . $name,
                "href" => $url,
            );
        }

        return $cache;
    }
}

if (!function_exists("szcubeExtractHtmlPropertyText")) {
    function szcubeExtractHtmlPropertyText($property)
    {
        if (!is_array($property)) {
            return "";
        }

        $extractText = static function ($value) {
            if (is_array($value)) {
                if (isset($value["TEXT"])) {
                    return trim((string)$value["TEXT"]);
                }

                $firstValue = reset($value);
                if (is_array($firstValue) && isset($firstValue["TEXT"])) {
                    return trim((string)$firstValue["TEXT"]);
                }

                return "";
            }

            $value = trim((string)$value);
            if ($value === "") {
                return "";
            }

            $unserialized = @unserialize($value, array("allowed_classes" => false));
            if (is_array($unserialized) && isset($unserialized["TEXT"])) {
                return trim((string)$unserialized["TEXT"]);
            }

            return $value;
        };

        if (array_key_exists("~VALUE", $property)) {
            $rawValue = $extractText($property["~VALUE"]);
            if ($rawValue !== "") {
                return $rawValue;
            }
        }

        if (!array_key_exists("VALUE", $property)) {
            return "";
        }

        $value = $extractText($property["VALUE"]);
        if ($value === "") {
            return "";
        }

        return htmlspecialcharsback($value);
    }
}

if (!function_exists("szcubeGetSingletonElementPropertiesByCode")) {
    function szcubeGetSingletonElementPropertiesByCode($iblockCode, $elementCode)
    {
        static $cache = array();

        $iblockCode = trim((string)$iblockCode);
        $elementCode = trim((string)$elementCode);
        if ($iblockCode === "" || $elementCode === "") {
            return array();
        }

        $cacheKey = mb_strtolower($iblockCode . "::" . $elementCode);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = array();
        if (!CModule::IncludeModule("iblock")) {
            return $cache[$cacheKey];
        }

        $iblockId = szcubeGetIblockIdByCode($iblockCode);
        if ($iblockId <= 0) {
            return $cache[$cacheKey];
        }

        $elementRes = CIBlockElement::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "=CODE" => $elementCode,
                "ACTIVE" => "Y",
            ),
            false,
            array("nTopCount" => 1),
            array("ID", "IBLOCK_ID", "NAME", "CODE")
        );
        if ($element = $elementRes->GetNextElement()) {
            $cache[$cacheKey] = $element->GetProperties();
        }

        return $cache[$cacheKey];
    }
}

if (!function_exists("szcubeGetPageMapEmbedHtml")) {
    function szcubeGetPageMapEmbedHtml($pageCode)
    {
        $properties = szcubeGetSingletonElementPropertiesByCode("page_maps", $pageCode);
        if (empty($properties) || !isset($properties["MAP_EMBED"])) {
            return "";
        }

        return szcubeExtractHtmlPropertyText($properties["MAP_EMBED"]);
    }
}

if (!function_exists("szcubeBuildProjectMapPageCode")) {
    function szcubeBuildProjectMapPageCode($projectCode)
    {
        $projectCode = trim((string)$projectCode);
        if ($projectCode === "") {
            return "";
        }

        return "project-" . mb_strtolower($projectCode);
    }
}

if (!function_exists("szcubeGetProjectMapEmbedHtml")) {
    function szcubeGetProjectMapEmbedHtml($projectCode)
    {
        $elementCode = szcubeBuildProjectMapPageCode($projectCode);
        if ($elementCode === "") {
            return "";
        }

        return szcubeGetPageMapEmbedHtml($elementCode);
    }
}

if (!function_exists("szcubeAppendProjectFilterToExtraUrl")) {
    function szcubeAppendProjectFilterToExtraUrl($url, $projectCode)
    {
        $url = trim((string)$url);
        $projectCode = preg_replace("/[^a-z0-9_-]/i", "", trim((string)$projectCode));

        if ($url === "" || $projectCode === "") {
            return $url;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return $url;
        }

        $path = isset($parts["path"]) ? (string)$parts["path"] : "";
        $normalizedPath = $path !== "" ? "/" . ltrim(rtrim($path, "/"), "/") . "/" : "";
        if ($normalizedPath !== "/parking/" && $normalizedPath !== "/storerooms/" && $normalizedPath !== "/commerce/") {
            return $url;
        }

        $query = array();
        if (isset($parts["query"]) && trim((string)$parts["query"]) !== "") {
            parse_str((string)$parts["query"], $query);
        }
        $query["project"] = $projectCode;

        $result = "";
        if (isset($parts["scheme"]) && $parts["scheme"] !== "") {
            $result .= $parts["scheme"] . "://";
        }
        if (isset($parts["user"]) && $parts["user"] !== "") {
            $result .= $parts["user"];
            if (isset($parts["pass"]) && $parts["pass"] !== "") {
                $result .= ":" . $parts["pass"];
            }
            $result .= "@";
        }
        if (isset($parts["host"]) && $parts["host"] !== "") {
            $result .= $parts["host"];
        }
        if (isset($parts["port"]) && (int)$parts["port"] > 0) {
            $result .= ":" . (int)$parts["port"];
        }

        $result .= $path !== "" ? $path : $url;
        $queryString = http_build_query($query, "", "&");
        if ($queryString !== "") {
            $result .= "?" . $queryString;
        }
        if (isset($parts["fragment"]) && $parts["fragment"] !== "") {
            $result .= "#" . $parts["fragment"];
        }

        return $result;
    }
}

if (!function_exists("szcubeGetNavigationLinks")) {
    function szcubeGetNavigationLinks()
    {
        static $links = null;

        if ($links !== null) {
            return $links;
        }

        $links = array(
            "projects" => "/projects/",
            "apartments" => "/apartments/",
            "commerce" => "/commerce/",
            "consulting" => "/consulting/",
            "tenders" => "/tenders/",
            "about_company" => "/about-company/",
            "contacts" => "/#contacts",
            "promotions" => "/promotions/",
            "news" => "/news/",
            "parking" => "/parking/",
            "storerooms" => "/storerooms/",
            "mortgage" => "/mortgage/",
            "installment" => "/installment/",
            "maternal_capital" => "/maternal-capital/",
            "project_kollekciya" => "/projects/kollekciya/",
            "apartments_studio" => "/apartments/?rooms=studio",
            "apartments_1k" => "/apartments/?rooms=1k",
            "apartments_2k" => "/apartments/?rooms=2k",
            "apartments_3k" => "/apartments/?rooms=3k",
            "apartments_1e" => "#",
            "apartments_2e" => "/apartments/?rooms=2e",
            "apartments_3e" => "/apartments/?rooms=3e",
        );

        $links["footer_newbuildings"] = szcubeGetActiveProjectNavigationItems();

        $links["footer_realty"] = array(
            array("label" => "Студии", "href" => $links["apartments_studio"]),
            array("label" => "1-комнатные", "href" => $links["apartments_1k"]),
            array("label" => "2-комнатные", "href" => $links["apartments_2k"]),
            array("label" => "3-комнатные", "href" => $links["apartments_3k"]),
            array("label" => "Еврооднушка", "href" => $links["apartments_1e"]),
            array("label" => "Евродвушка", "href" => $links["apartments_2e"]),
            array("label" => "Евротрешка", "href" => $links["apartments_3e"]),
            array("label" => "Кладовые", "href" => $links["storerooms"]),
            array("label" => "Парковки", "href" => $links["parking"]),
        );

        $links["footer_purchase"] = array(
            array("label" => "Ипотека", "href" => $links["mortgage"]),
            array("label" => "Рассрочка", "href" => $links["installment"]),
            array("label" => "Материнский капитал", "href" => $links["maternal_capital"]),
        );

        $links["footer_clients"] = array(
            array("label" => "Проекты", "href" => $links["projects"]),
            array("label" => "Квартиры", "href" => $links["apartments"]),
            array("label" => "Тендеры", "href" => $links["tenders"]),
            array("label" => "Акции", "href" => $links["promotions"]),
            array("label" => "Новости", "href" => $links["news"]),
            array("label" => "Ипотека", "href" => $links["mortgage"]),
            array("label" => "О компании", "href" => $links["about_company"]),
            array("label" => "Коммерция", "href" => $links["commerce"]),
        );

        return $links;
    }
}

if (!function_exists("szcubeGetIblockSectionIdByCodePath")) {
    function szcubeGetIblockSectionIdByCodePath($iblockId, array $codePath)
    {
        static $cache = array();

        $iblockId = (int)$iblockId;
        if ($iblockId <= 0 || empty($codePath)) {
            return 0;
        }

        $normalizedPath = array();
        foreach ($codePath as $code) {
            $code = trim((string)$code);
            if ($code === "") {
                return 0;
            }
            $normalizedPath[] = mb_strtolower($code);
        }

        $cacheKey = $iblockId . "::" . implode("/", $normalizedPath);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = 0;
        if (!CModule::IncludeModule("iblock")) {
            return $cache[$cacheKey];
        }

        $parentSectionId = false;
        foreach ($normalizedPath as $code) {
            $filter = array(
                "IBLOCK_ID" => $iblockId,
                "=CODE" => $code,
                "ACTIVE" => "Y",
            );
            $filter["SECTION_ID"] = $parentSectionId === false ? false : (int)$parentSectionId;

            $sectionRes = CIBlockSection::GetList(
                array("SORT" => "ASC", "ID" => "ASC"),
                $filter,
                false,
                array("ID")
            );
            $section = $sectionRes ? $sectionRes->Fetch() : false;
            if (!is_array($section)) {
                return $cache[$cacheKey];
            }

            $parentSectionId = (int)$section["ID"];
            if ($parentSectionId <= 0) {
                return $cache[$cacheKey];
            }
        }

        $cache[$cacheKey] = (int)$parentSectionId;
        return $cache[$cacheKey];
    }
}

if (!function_exists("szcubeRequestCsvList")) {
    function szcubeRequestCsvList($key)
    {
        $raw = isset($_GET[$key]) ? $_GET[$key] : "";
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $raw = trim((string)$raw);
            $items = $raw !== "" ? explode(",", $raw) : array();
        }

        $result = array();
        foreach ($items as $item) {
            $item = trim((string)$item);
            if ($item !== "") {
                $result[$item] = $item;
            }
        }

        return array_values($result);
    }
}

if (!function_exists("szcubeRequestNumberValue")) {
    function szcubeRequestNumberValue($key)
    {
        if (!isset($_GET[$key])) {
            return null;
        }

        $value = trim((string)$_GET[$key]);
        if ($value === "") {
            return null;
        }

        $value = str_replace(array(" ", ","), array("", "."), $value);
        return is_numeric($value) ? (float)$value : null;
    }
}

if (!function_exists("szcubeResolveSelectedRange")) {
    function szcubeResolveSelectedRange(array $range, $fromKey, $toKey)
    {
        $min = isset($range["render_min"]) ? (float)$range["render_min"] : 0.0;
        $max = isset($range["render_max"]) ? (float)$range["render_max"] : $min;
        $precision = isset($range["precision"]) ? (int)$range["precision"] : 0;

        $selectedMin = szcubeRequestNumberValue($fromKey);
        $selectedMax = szcubeRequestNumberValue($toKey);

        if ($selectedMin === null) {
            $selectedMin = $min;
        }
        if ($selectedMax === null) {
            $selectedMax = $max;
        }

        $selectedMin = max($min, min($max, (float)$selectedMin));
        $selectedMax = max($min, min($max, (float)$selectedMax));

        if ($selectedMax < $selectedMin) {
            $selectedMax = $selectedMin;
        }

        $range["actual_min"] = round($selectedMin, $precision);
        $range["actual_max"] = round($selectedMax, $precision);

        return $range;
    }
}

if (!function_exists("szcubeBuildPaginationUrl")) {
    function szcubeBuildPaginationUrl($pageParam, $pageNumber)
    {
        $pageParam = trim((string)$pageParam);
        $pageNumber = (int)$pageNumber;
        if ($pageParam === "") {
            return (string)($_SERVER["REQUEST_URI"] ?? "/");
        }

        $requestUri = (string)($_SERVER["REQUEST_URI"] ?? "/");
        $path = $requestUri;
        $query = array();

        $parts = parse_url($requestUri);
        if (is_array($parts)) {
            $path = isset($parts["path"]) && $parts["path"] !== "" ? (string)$parts["path"] : "/";
            if (isset($parts["query"]) && trim((string)$parts["query"]) !== "") {
                parse_str((string)$parts["query"], $query);
            }
        }

        unset($query[$pageParam], $query["SHOWALL_1"], $query["SIZEN_1"]);
        if ($pageNumber > 1) {
            $query[$pageParam] = $pageNumber;
        }

        $queryString = http_build_query($query, "", "&");
        return $queryString !== "" ? ($path . "?" . $queryString) : $path;
    }
}

if (!function_exists("szcubeBuildArrayPagination")) {
    function szcubeBuildArrayPagination(array $items, $pageSize, $pageParam)
    {
        $pageSize = max(1, (int)$pageSize);
        $pageParam = trim((string)$pageParam) !== "" ? trim((string)$pageParam) : "PAGEN_1";
        $currentPage = isset($_GET[$pageParam]) ? max(1, (int)$_GET[$pageParam]) : 1;

        $nav = new CDBResult();
        $nav->InitFromArray(array_values($items));
        $nav->NavStart($pageSize, false, $currentPage);

        $pageItems = array();
        while ($row = $nav->Fetch()) {
            $pageItems[] = $row;
        }

        $totalPages = isset($nav->NavPageCount) ? (int)$nav->NavPageCount : 0;
        $current = isset($nav->NavPageNomer) ? (int)$nav->NavPageNomer : 1;
        $total = isset($nav->NavRecordCount) ? (int)$nav->NavRecordCount : count($items);

        $pagination = null;
        if ($totalPages > 1) {
            $visiblePages = array();
            if ($totalPages <= 7) {
                for ($page = 1; $page <= $totalPages; $page++) {
                    $visiblePages[] = $page;
                }
            } else {
                $visiblePages = array(1, $current - 1, $current, $current + 1, $totalPages);
                if ($current <= 3) {
                    $visiblePages[] = 2;
                    $visiblePages[] = 3;
                }
                if ($current >= $totalPages - 2) {
                    $visiblePages[] = $totalPages - 1;
                    $visiblePages[] = $totalPages - 2;
                }
            }

            $visiblePages = array_values(array_unique(array_filter($visiblePages, static function ($page) use ($totalPages) {
                return $page >= 1 && $page <= $totalPages;
            })));
            sort($visiblePages, SORT_NUMERIC);

            $pages = array();
            $lastPage = 0;
            foreach ($visiblePages as $page) {
                if ($lastPage > 0 && $page - $lastPage > 1) {
                    $pages[] = array("type" => "ellipsis");
                }

                $pages[] = array(
                    "type" => "page",
                    "number" => $page,
                    "current" => $page === $current,
                    "url" => szcubeBuildPaginationUrl($pageParam, $page),
                );
                $lastPage = $page;
            }

            $pagination = array(
                "page_param" => $pageParam,
                "current_page" => $current,
                "total_pages" => $totalPages,
                "total_items" => $total,
                "prev_url" => $current > 1 ? szcubeBuildPaginationUrl($pageParam, $current - 1) : "",
                "next_url" => $current < $totalPages ? szcubeBuildPaginationUrl($pageParam, $current + 1) : "",
                "pages" => $pages,
            );
        }

        return array(
            "items" => $pageItems,
            "count" => $total,
            "pagination" => $pagination,
        );
    }
}

if (!function_exists("szcubeGetExtraCards")) {
    function szcubeGetExtraCards($scope, $projectCode = "")
    {
        static $cache = array();

        $scope = mb_strtolower(trim((string)$scope));
        $projectCode = preg_replace("/[^a-z0-9_-]/i", "", trim((string)$projectCode));
        $cacheKey = $scope . "::" . mb_strtolower($projectCode);

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = array();
        if (!CModule::IncludeModule("iblock")) {
            return $cache[$cacheKey];
        }

        $iblockId = szcubeGetIblockIdByCode("extra_cards");
        if ($iblockId <= 0) {
            return $cache[$cacheKey];
        }

        if ($scope === "home") {
            $sectionCodePath = array("home");
        } elseif ($scope === "project" && $projectCode !== "") {
            $sectionCodePath = array("projects", $projectCode);
        } else {
            return $cache[$cacheKey];
        }

        $sectionId = szcubeGetIblockSectionIdByCodePath($iblockId, $sectionCodePath);
        if ($sectionId <= 0) {
            return $cache[$cacheKey];
        }

        $elementRes = CIBlockElement::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "SECTION_ID" => $sectionId,
                "INCLUDE_SUBSECTIONS" => "N",
                "ACTIVE" => "Y",
            ),
            false,
            false,
            array("ID", "IBLOCK_ID", "NAME", "CODE", "PREVIEW_PICTURE", "SORT")
        );

        while ($element = $elementRes->GetNextElement()) {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();

            $title = trim((string)$fields["NAME"]);
            $image = CFile::GetPath((int)$fields["PREVIEW_PICTURE"]);
            $url = isset($properties["LINK_URL"]["VALUE"]) ? trim((string)$properties["LINK_URL"]["VALUE"]) : "";

            if ($title === "" || !$image) {
                continue;
            }

            if ($scope === "project" && $projectCode !== "") {
                $url = szcubeAppendProjectFilterToExtraUrl($url, $projectCode);
            }

            $cache[$cacheKey][] = array(
                "title" => $title,
                "image" => (string)$image,
                "url" => $url,
            );
        }

        return $cache[$cacheKey];
    }
}

if (!function_exists("szcubeGetPurchasePages")) {
    function szcubeGetPurchasePages()
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = array();
        if (!CModule::IncludeModule("iblock")) {
            return $cache;
        }

        $iblockId = szcubeGetIblockIdByCode("purchase_pages");
        if ($iblockId <= 0) {
            return $cache;
        }

        $elementRes = CIBlockElement::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "ACTIVE" => "Y",
            ),
            false,
            false,
            array("ID", "IBLOCK_ID", "NAME", "CODE", "SORT")
        );

        while ($element = $elementRes->GetNextElement()) {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();

            $code = trim((string)$fields["CODE"]);
            $label = trim((string)$fields["NAME"]);
            $url = isset($properties["PAGE_URL"]["VALUE"]) ? trim((string)$properties["PAGE_URL"]["VALUE"]) : "";
            $title = isset($properties["HERO_TITLE"]["VALUE"]) ? trim((string)$properties["HERO_TITLE"]["VALUE"]) : "";
            $heroImage = CFile::GetPath((int)($properties["HERO_IMAGE"]["VALUE"] ?? 0));
            $heroImageAlt = isset($properties["HERO_IMAGE_ALT"]["VALUE"]) ? trim((string)$properties["HERO_IMAGE_ALT"]["VALUE"]) : "";
            $showCalculatorXml = isset($properties["SHOW_CALCULATOR"]["VALUE_XML_ID"]) ? trim((string)$properties["SHOW_CALCULATOR"]["VALUE_XML_ID"]) : "";
            $showCalculatorValue = isset($properties["SHOW_CALCULATOR"]["VALUE"]) ? trim((string)$properties["SHOW_CALCULATOR"]["VALUE"]) : "";

            if ($code === "" || $label === "" || $url === "" || $title === "") {
                continue;
            }

            $paragraphs = array();
            foreach (array("HERO_TEXT_1", "HERO_TEXT_2") as $propertyCode) {
                $paragraph = isset($properties[$propertyCode]["VALUE"]) ? trim((string)$properties[$propertyCode]["VALUE"]) : "";
                if ($paragraph !== "") {
                    $paragraphs[] = $paragraph;
                }
            }

            $showCalculator = false;
            if ($showCalculatorXml !== "") {
                $showCalculator = mb_strtoupper($showCalculatorXml) === "Y";
            } elseif ($showCalculatorValue !== "") {
                $showCalculator = in_array(mb_strtoupper($showCalculatorValue), array("Y", "YES", "ДА"), true);
            }

            $cache[] = array(
                "code" => $code,
                "sort" => (int)$fields["SORT"],
                "label" => $label,
                "url" => $url,
                "hero_title" => $title,
                "hero_paragraphs" => $paragraphs,
                "hero_image" => (string)$heroImage,
                "hero_image_alt" => $heroImageAlt,
                "primary_button_label" => isset($properties["PRIMARY_BUTTON_LABEL"]["VALUE"]) ? trim((string)$properties["PRIMARY_BUTTON_LABEL"]["VALUE"]) : "",
                "primary_button_title" => isset($properties["PRIMARY_BUTTON_TITLE"]["VALUE"]) ? trim((string)$properties["PRIMARY_BUTTON_TITLE"]["VALUE"]) : "",
                "primary_button_note" => isset($properties["PRIMARY_BUTTON_NOTE"]["VALUE"]) ? trim((string)$properties["PRIMARY_BUTTON_NOTE"]["VALUE"]) : "",
                "primary_button_source" => isset($properties["PRIMARY_BUTTON_SOURCE"]["VALUE"]) ? trim((string)$properties["PRIMARY_BUTTON_SOURCE"]["VALUE"]) : "",
                "secondary_button_label" => isset($properties["SECONDARY_BUTTON_LABEL"]["VALUE"]) ? trim((string)$properties["SECONDARY_BUTTON_LABEL"]["VALUE"]) : "",
                "secondary_button_url" => isset($properties["SECONDARY_BUTTON_URL"]["VALUE"]) ? trim((string)$properties["SECONDARY_BUTTON_URL"]["VALUE"]) : "",
                "show_calculator" => $showCalculator,
                "calculator_title" => isset($properties["CALCULATOR_TITLE"]["VALUE"]) ? trim((string)$properties["CALCULATOR_TITLE"]["VALUE"]) : "",
                "calculator_subtitle" => isset($properties["CALCULATOR_SUBTITLE"]["VALUE"]) ? trim((string)$properties["CALCULATOR_SUBTITLE"]["VALUE"]) : "",
            );
        }

        return $cache;
    }
}

if (!function_exists("szcubeGetPurchasePage")) {
    function szcubeGetPurchasePage($code)
    {
        $code = trim((string)$code);
        if ($code === "") {
            return array();
        }

        foreach (szcubeGetPurchasePages() as $page) {
            if (isset($page["code"]) && (string)$page["code"] === $code) {
                return $page;
            }
        }

        return array();
    }
}

if (!function_exists("szcubeGetCatalogPages")) {
    function szcubeGetCatalogPages()
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = array();

        if (!CModule::IncludeModule("iblock")) {
            return $cache;
        }

        $iblockId = szcubeGetIblockIdByCode("catalog_pages");
        if ($iblockId <= 0) {
            return $cache;
        }

        $res = CIBlockElement::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "ACTIVE" => "Y",
            ),
            false,
            false,
            array("ID", "IBLOCK_ID", "NAME", "CODE", "SORT")
        );

        while ($element = $res->GetNextElement()) {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();

            $imageId = 0;
            if (isset($properties["INTRO_IMAGE"]["VALUE"])) {
                $imageId = (int)$properties["INTRO_IMAGE"]["VALUE"];
            }

            $cache[] = array(
                "id" => (int)$fields["ID"],
                "code" => trim((string)$fields["CODE"]),
                "label" => trim((string)$fields["NAME"]),
                "sort" => (int)$fields["SORT"],
                "intro_text_1" => isset($properties["INTRO_TEXT_1"]["VALUE"]) ? trim((string)$properties["INTRO_TEXT_1"]["VALUE"]) : "",
                "intro_text_2" => isset($properties["INTRO_TEXT_2"]["VALUE"]) ? trim((string)$properties["INTRO_TEXT_2"]["VALUE"]) : "",
                "intro_image" => $imageId > 0 ? (string)CFile::GetPath($imageId) : "",
                "intro_image_alt" => isset($properties["INTRO_IMAGE_ALT"]["VALUE"]) ? trim((string)$properties["INTRO_IMAGE_ALT"]["VALUE"]) : "",
            );
        }

        return $cache;
    }
}

if (!function_exists("szcubeGetCatalogPage")) {
    function szcubeGetCatalogPage($code)
    {
        $code = trim((string)$code);
        if ($code === "") {
            return array();
        }

        foreach (szcubeGetCatalogPages() as $page) {
            if ((string)$page["code"] === $code) {
                return $page;
            }
        }

        return array();
    }
}

if (!function_exists("szcubeGetPurchaseCards")) {
    function szcubeGetPurchaseCards($pageCode)
    {
        static $cache = array();

        $pageCode = trim((string)$pageCode);
        if ($pageCode === "") {
            return array();
        }

        $cacheKey = mb_strtolower($pageCode);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = array();
        if (!CModule::IncludeModule("iblock")) {
            return $cache[$cacheKey];
        }

        $iblockId = szcubeGetIblockIdByCode("purchase_page_cards");
        if ($iblockId <= 0) {
            return $cache[$cacheKey];
        }

        $sectionId = szcubeGetIblockSectionIdByCodePath($iblockId, array($pageCode));
        if ($sectionId <= 0) {
            return $cache[$cacheKey];
        }

        $elementRes = CIBlockElement::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "SECTION_ID" => $sectionId,
                "INCLUDE_SUBSECTIONS" => "N",
                "ACTIVE" => "Y",
            ),
            false,
            false,
            array("ID", "IBLOCK_ID", "NAME", "CODE", "SORT", "PREVIEW_PICTURE", "PREVIEW_TEXT", "PREVIEW_TEXT_TYPE")
        );

        while ($element = $elementRes->GetNextElement()) {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();

            $title = trim((string)$fields["NAME"]);
            $layoutXml = isset($properties["CARD_LAYOUT"]["VALUE_XML_ID"]) ? trim((string)$properties["CARD_LAYOUT"]["VALUE_XML_ID"]) : "";
            $text = isset($fields["~PREVIEW_TEXT"]) ? trim((string)$fields["~PREVIEW_TEXT"]) : trim((string)$fields["PREVIEW_TEXT"]);

            if ($title === "") {
                continue;
            }

            $cache[$cacheKey][] = array(
                "id" => (int)$fields["ID"],
                "code" => trim((string)$fields["CODE"]),
                "title" => $title,
                "text" => $text,
                "image" => CFile::GetPath((int)$fields["PREVIEW_PICTURE"]),
                "image_alt" => isset($properties["IMAGE_ALT"]["VALUE"]) ? trim((string)$properties["IMAGE_ALT"]["VALUE"]) : "",
                "layout" => $layoutXml !== "" ? $layoutXml : "default",
            );
        }

        return $cache[$cacheKey];
    }
}

if (!function_exists("szcubeGetMortgageCalculatorPrograms")) {
    function szcubeGetMortgageCalculatorPrograms()
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = array();
        if (!CModule::IncludeModule("iblock")) {
            return $cache;
        }

        $iblockId = szcubeGetIblockIdByCode("mortgage_calculator_programs");
        if ($iblockId <= 0) {
            return $cache;
        }

        $elementRes = CIBlockElement::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "ACTIVE" => "Y",
            ),
            false,
            false,
            array("ID", "IBLOCK_ID", "NAME", "CODE", "SORT")
        );

        while ($element = $elementRes->GetNextElement()) {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();

            $code = trim((string)$fields["CODE"]);
            $label = trim((string)$fields["NAME"]);
            $rate = isset($properties["RATE"]["VALUE"]) ? (float)$properties["RATE"]["VALUE"] : 0;
            if ($code === "" || $label === "" || $rate <= 0) {
                continue;
            }

            $cache[] = array(
                "code" => $code,
                "label" => $label,
                "rate" => $rate,
            );
        }

        return $cache;
    }
}

if (!function_exists("szcubeGetMortgageCalculatorBanks")) {
    function szcubeGetMortgageCalculatorBanks()
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = array();
        if (!CModule::IncludeModule("iblock")) {
            return $cache;
        }

        $iblockId = szcubeGetIblockIdByCode("mortgage_calculator_banks");
        if ($iblockId <= 0) {
            return $cache;
        }

        $elementRes = CIBlockElement::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "ACTIVE" => "Y",
            ),
            false,
            false,
            array("ID", "IBLOCK_ID", "NAME", "CODE", "SORT")
        );

        while ($element = $elementRes->GetNextElement()) {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();

            $label = trim((string)$fields["NAME"]);
            if ($label === "") {
                continue;
            }

            $cache[] = array(
                "code" => trim((string)$fields["CODE"]),
                "label" => $label,
                "mark" => isset($properties["MARK"]["VALUE"]) ? trim((string)$properties["MARK"]["VALUE"]) : "",
                "tone" => isset($properties["TONE_COLOR"]["VALUE"]) ? trim((string)$properties["TONE_COLOR"]["VALUE"]) : "",
                "accent" => isset($properties["ACCENT_COLOR"]["VALUE"]) ? trim((string)$properties["ACCENT_COLOR"]["VALUE"]) : "",
            );
        }

        return $cache;
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

if (!function_exists("szcubeSetSinglePropertyValueInFields")) {
    function szcubeSetSinglePropertyValueInFields(array &$fields, $propertyId, $value)
    {
        $propertyId = (int)$propertyId;
        if ($propertyId <= 0) {
            return;
        }

        if (!isset($fields["PROPERTY_VALUES"]) || !is_array($fields["PROPERTY_VALUES"])) {
            $fields["PROPERTY_VALUES"] = array();
        }

        if ($value === false || $value === null || $value === "") {
            $fields["PROPERTY_VALUES"][$propertyId] = false;
            return;
        }

        $fields["PROPERTY_VALUES"][$propertyId] = array(
            "VALUE" => $value,
        );
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

if (!function_exists("szcubeGetElementPropertyXmlIdByCode")) {
    function szcubeGetElementPropertyXmlIdByCode($iblockId, $elementId, $propertyCode)
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
            return trim((string)$row["VALUE_XML_ID"]);
        }

        return "";
    }
}

if (!function_exists("szcubeGetPropertyEnumIdByXmlId")) {
    function szcubeGetPropertyEnumIdByXmlId($iblockId, $propertyCode, $xmlId)
    {
        static $cache = array();

        $iblockId = (int)$iblockId;
        $propertyCode = trim((string)$propertyCode);
        $xmlId = trim((string)$xmlId);
        if ($iblockId <= 0 || $propertyCode === "" || $xmlId === "") {
            return 0;
        }

        $cacheKey = $iblockId . ":" . $propertyCode . ":" . mb_strtolower($xmlId);
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = 0;
        if (!CModule::IncludeModule("iblock")) {
            return 0;
        }

        $res = CIBlockPropertyEnum::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "CODE" => $propertyCode,
            )
        );
        while ($row = $res->Fetch()) {
            if (trim((string)$row["XML_ID"]) === $xmlId) {
                $cache[$cacheKey] = (int)$row["ID"];
                break;
            }
        }

        return $cache[$cacheKey];
    }
}

if (!function_exists("szcubeResolvePropertyEnumXmlId")) {
    function szcubeResolvePropertyEnumXmlId($iblockId, $propertyCode, $rawValue)
    {
        $iblockId = (int)$iblockId;
        $propertyCode = trim((string)$propertyCode);
        $rawValue = trim((string)$rawValue);
        if ($iblockId <= 0 || $propertyCode === "" || $rawValue === "") {
            return "";
        }

        if (!ctype_digit($rawValue)) {
            return $rawValue;
        }

        if (!CModule::IncludeModule("iblock")) {
            return "";
        }

        $res = CIBlockPropertyEnum::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "CODE" => $propertyCode,
                "ID" => (int)$rawValue,
            )
        );
        if ($row = $res->Fetch()) {
            return trim((string)$row["XML_ID"]);
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

if (!function_exists("szcubeNormalizeApartmentDiscountNumeric")) {
    function szcubeNormalizeApartmentDiscountNumeric($value)
    {
        if (is_string($value)) {
            $value = str_replace(array(" ", "\xc2\xa0", ","), array("", "", "."), $value);
        }

        return (float)$value;
    }
}

if (!function_exists("szcubeResolveApartmentDiscountMode")) {
    function szcubeResolveApartmentDiscountMode($iblockId, $elementId, array $fields, array $propertyMap)
    {
        $rawMode = isset($propertyMap["DISCOUNT_MODE"])
            ? szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["DISCOUNT_MODE"])
            : "";
        $mode = szcubeResolvePropertyEnumXmlId($iblockId, "DISCOUNT_MODE", $rawMode);

        if ($mode !== "") {
            return $mode;
        }

        if ((int)$elementId > 0) {
            $existingMode = szcubeGetElementPropertyXmlIdByCode($iblockId, (int)$elementId, "DISCOUNT_MODE");
            if ($existingMode !== "") {
                return $existingMode;
            }
        }

        $priceOld = isset($propertyMap["PRICE_OLD"])
            ? szcubeNormalizeApartmentDiscountNumeric(szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["PRICE_OLD"]))
            : 0.0;
        $percent = isset($propertyMap["DISCOUNT_PERCENT"])
            ? szcubeNormalizeApartmentDiscountNumeric(szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["DISCOUNT_PERCENT"]))
            : 0.0;
        $amount = isset($propertyMap["DISCOUNT_AMOUNT"])
            ? szcubeNormalizeApartmentDiscountNumeric(szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["DISCOUNT_AMOUNT"]))
            : 0.0;

        if ($percent > 0) {
            return "percent";
        }
        if ($amount > 0) {
            return "amount";
        }
        if ($priceOld > 0) {
            return "old_price";
        }

        return "none";
    }
}

if (!function_exists("szcubePrepareApartmentDiscountBeforeSave")) {
    function szcubePrepareApartmentDiscountBeforeSave(&$fields)
    {
        $iblockId = isset($fields["IBLOCK_ID"]) ? (int)$fields["IBLOCK_ID"] : 0;
        if ($iblockId <= 0 || $iblockId !== szcubeGetIblockIdByCode("apartments")) {
            return;
        }

        $hasPropertyValuesPayload = isset($fields["PROPERTY_VALUES"]) && is_array($fields["PROPERTY_VALUES"]);
        if (!$hasPropertyValuesPayload) {
            return;
        }

        $propertyMap = szcubeGetApartmentPropertyMap($iblockId);
        if (empty($propertyMap)) {
            return;
        }

        $elementId = isset($fields["ID"]) ? (int)$fields["ID"] : 0;
        $priceTotal = isset($propertyMap["PRICE_TOTAL"])
            ? szcubeNormalizeApartmentDiscountNumeric(szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["PRICE_TOTAL"]))
            : 0.0;
        if ($priceTotal <= 0 && $elementId > 0) {
            $priceTotal = szcubeNormalizeApartmentDiscountNumeric(szcubeGetElementPropertyValueByCode($iblockId, $elementId, "PRICE_TOTAL"));
        }

        $priceOldInput = isset($propertyMap["PRICE_OLD"])
            ? szcubeNormalizeApartmentDiscountNumeric(szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["PRICE_OLD"]))
            : 0.0;
        if ($priceOldInput <= 0 && $elementId > 0) {
            $priceOldInput = szcubeNormalizeApartmentDiscountNumeric(szcubeGetElementPropertyValueByCode($iblockId, $elementId, "PRICE_OLD"));
        }

        $discountPercent = isset($propertyMap["DISCOUNT_PERCENT"])
            ? szcubeNormalizeApartmentDiscountNumeric(szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["DISCOUNT_PERCENT"]))
            : 0.0;
        if ($discountPercent <= 0 && $elementId > 0) {
            $discountPercent = szcubeNormalizeApartmentDiscountNumeric(szcubeGetElementPropertyValueByCode($iblockId, $elementId, "DISCOUNT_PERCENT"));
        }

        $discountAmount = isset($propertyMap["DISCOUNT_AMOUNT"])
            ? szcubeNormalizeApartmentDiscountNumeric(szcubeExtractSinglePropertyValueFromFields($fields, $propertyMap["DISCOUNT_AMOUNT"]))
            : 0.0;
        if ($discountAmount <= 0 && $elementId > 0) {
            $discountAmount = szcubeNormalizeApartmentDiscountNumeric(szcubeGetElementPropertyValueByCode($iblockId, $elementId, "DISCOUNT_AMOUNT"));
        }

        $mode = szcubeResolveApartmentDiscountMode($iblockId, $elementId, $fields, $propertyMap);
        $normalizedOldPrice = 0.0;

        if ($mode === "percent" && $priceTotal > 0 && $discountPercent > 0 && $discountPercent < 100) {
            $normalizedOldPrice = round($priceTotal / (1 - ($discountPercent / 100)));
        } elseif ($mode === "amount" && $priceTotal > 0 && $discountAmount > 0) {
            $normalizedOldPrice = round($priceTotal + $discountAmount);
        } elseif ($mode === "old_price" && $priceOldInput > 0) {
            $normalizedOldPrice = round($priceOldInput);
        }

        if ($normalizedOldPrice > 0 && $priceTotal > 0 && $normalizedOldPrice <= $priceTotal) {
            $normalizedOldPrice = 0.0;
        }

        if ($mode === "none" || $normalizedOldPrice <= 0) {
            $mode = "none";
        }

        if (isset($propertyMap["DISCOUNT_MODE"])) {
            $modeEnumId = szcubeGetPropertyEnumIdByXmlId($iblockId, "DISCOUNT_MODE", $mode);
            szcubeSetSinglePropertyValueInFields($fields, $propertyMap["DISCOUNT_MODE"], $modeEnumId > 0 ? $modeEnumId : false);
        }
        if (isset($propertyMap["PRICE_OLD"])) {
            szcubeSetSinglePropertyValueInFields($fields, $propertyMap["PRICE_OLD"], $normalizedOldPrice > 0 ? $normalizedOldPrice : false);
        }
        if (isset($propertyMap["DISCOUNT_PERCENT"])) {
            szcubeSetSinglePropertyValueInFields(
                $fields,
                $propertyMap["DISCOUNT_PERCENT"],
                ($mode === "percent" && $discountPercent > 0) ? $discountPercent : false
            );
        }
        if (isset($propertyMap["DISCOUNT_AMOUNT"])) {
            szcubeSetSinglePropertyValueInFields(
                $fields,
                $propertyMap["DISCOUNT_AMOUNT"],
                ($mode === "amount" && $discountAmount > 0) ? $discountAmount : false
            );
        }
    }
}

if (!function_exists("szcubePrepareApartmentBeforeSave")) {
    function szcubePrepareApartmentBeforeSave(&$fields)
    {
        szcubeEnsureApartmentCodeBeforeSave($fields);
        szcubeEnsureApartmentXmlIdBeforeSave($fields);
        szcubePrepareApartmentDiscountBeforeSave($fields);
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

if (!function_exists("szcubeGetSectionById")) {
    function szcubeGetSectionById($sectionId)
    {
        static $cache = array();

        $sectionId = (int)$sectionId;
        if ($sectionId <= 0) {
            return null;
        }

        if (array_key_exists($sectionId, $cache)) {
            return $cache[$sectionId];
        }

        $cache[$sectionId] = null;
        $res = CIBlockSection::GetList(
            array("ID" => "ASC"),
            array("ID" => $sectionId),
            false,
            array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "NAME", "CODE", "UF_*")
        );
        if ($row = $res->Fetch()) {
            $cache[$sectionId] = $row;
        }

        return $cache[$sectionId];
    }
}

if (!function_exists("szcubeResolveApartmentEntranceSectionId")) {
    function szcubeResolveApartmentEntranceSectionId($sectionId)
    {
        $sectionId = (int)$sectionId;
        if ($sectionId <= 0) {
            return 0;
        }

        $section = szcubeGetSectionById($sectionId);
        if (!is_array($section)) {
            return 0;
        }

        if (trim((string)$section["UF_ENTRANCE_NUMBER"]) !== "") {
            return (int)$section["ID"];
        }

        $parentSectionId = isset($section["IBLOCK_SECTION_ID"]) ? (int)$section["IBLOCK_SECTION_ID"] : 0;
        $code = trim((string)$section["CODE"]);
        $name = trim((string)$section["NAME"]);
        $isFloorSection = (int)$section["UF_FLOOR_NUMBER"] > 0
            || ($code !== "" && strpos($code, "floor-") === 0)
            || ($name !== "" && mb_stripos($name, "этаж") !== false);

        if ($isFloorSection && $parentSectionId > 0) {
            return $parentSectionId;
        }

        if ((int)$section["UF_FLOOR_NUMBER"] > 0 && $parentSectionId > 0) {
            return (int)$section["IBLOCK_SECTION_ID"];
        }

        if ($code !== "" && strpos($code, "podezd-") === 0) {
            return (int)$section["ID"];
        }

        return 0;
    }
}

if (!function_exists("szcubeGetApartmentEntranceSectionIdByElement")) {
    function szcubeGetApartmentEntranceSectionIdByElement($elementId)
    {
        $elementId = (int)$elementId;
        if ($elementId <= 0) {
            return 0;
        }

        $sectionIds = szcubeGetElementSectionIds($elementId);
        foreach ($sectionIds as $sectionId) {
            $entranceId = szcubeResolveApartmentEntranceSectionId($sectionId);
            if ($entranceId > 0) {
                return $entranceId;
            }
        }

        return 0;
    }
}

if (!function_exists("szcubeNormalizeApartmentChessRowLabel")) {
    function szcubeNormalizeApartmentChessRowLabel($value)
    {
        $value = trim((string)$value);
        if ($value === "") {
            return "";
        }

        if (preg_match("/^(\\d+)\\s*-\\s*(\\d+)$/", $value, $matches)) {
            return ((int)$matches[1]) . "-" . ((int)$matches[2]);
        }

        if (preg_match("/^\\d+$/", $value)) {
            return (string)((int)$value);
        }

        return "";
    }
}

if (!function_exists("szcubeBuildApartmentChessSlotId")) {
    function szcubeBuildApartmentChessSlotId($rowLabel, $columnNumber)
    {
        $rowLabel = szcubeNormalizeApartmentChessRowLabel($rowLabel);
        $columnNumber = (int)$columnNumber;
        if ($rowLabel === "" || $columnNumber <= 0) {
            return "";
        }

        return "r" . $rowLabel . "-c" . str_pad((string)$columnNumber, 2, "0", STR_PAD_LEFT);
    }
}

if (!function_exists("szcubeParseApartmentChessSlotId")) {
    function szcubeParseApartmentChessSlotId($slotId)
    {
        $slotId = trim((string)$slotId);
        if ($slotId === "") {
            return null;
        }

        if (!preg_match("/^r(\\d+(?:-\\d+)?)-c(\\d+)$/i", $slotId, $matches)) {
            return null;
        }

        $rowLabel = szcubeNormalizeApartmentChessRowLabel($matches[1]);
        $columnNumber = (int)$matches[2];
        if ($rowLabel === "" || $columnNumber <= 0) {
            return null;
        }

        return array(
            "slot_id" => szcubeBuildApartmentChessSlotId($rowLabel, $columnNumber),
            "row_label" => $rowLabel,
            "column" => $columnNumber,
        );
    }
}

if (!function_exists("szcubeBuildApartmentChessAdminUrl")) {
    function szcubeBuildApartmentChessAdminUrl($entranceId, $iblockId = 0, $backUrl = "")
    {
        $entranceId = (int)$entranceId;
        $iblockId = (int)$iblockId;
        if ($entranceId <= 0) {
            return "";
        }

        if ($iblockId <= 0) {
            $iblockId = szcubeGetIblockIdByCode("apartments");
        }

        $query = array(
            "lang" => defined("LANGUAGE_ID") ? LANGUAGE_ID : "ru",
            "IBLOCK_ID" => $iblockId,
            "ENTRANCE_ID" => $entranceId,
        );

        $backUrl = trim((string)$backUrl);
        if ($backUrl !== "") {
            $query["back_url"] = $backUrl;
        }

        return "/local/tools/apartment_chess_admin.php?" . http_build_query($query, "", "&");
    }
}

if (!function_exists("szcubeAddApartmentChessAdminContextButton")) {
    function szcubeAddApartmentChessAdminContextButton(&$items)
    {
        global $APPLICATION;

        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            return;
        }

        $currentPage = $APPLICATION->GetCurPage(true);
        if (!in_array($currentPage, array("/bitrix/admin/iblock_element_edit.php", "/bitrix/admin/iblock_section_edit.php"), true)) {
            return;
        }

        $iblockId = isset($_REQUEST["IBLOCK_ID"]) ? (int)$_REQUEST["IBLOCK_ID"] : 0;
        if ($iblockId <= 0 || $iblockId !== szcubeGetIblockIdByCode("apartments")) {
            return;
        }

        $entranceId = 0;
        if ($currentPage === "/bitrix/admin/iblock_element_edit.php") {
            $elementId = isset($_REQUEST["ID"]) ? (int)$_REQUEST["ID"] : 0;
            if ($elementId > 0) {
                $entranceId = szcubeGetApartmentEntranceSectionIdByElement($elementId);
            }
        } else {
            $sectionId = isset($_REQUEST["ID"]) ? (int)$_REQUEST["ID"] : 0;
            if ($sectionId <= 0 && isset($_REQUEST["find_section_section"])) {
                $sectionId = (int)$_REQUEST["find_section_section"];
            }
            if ($sectionId > 0) {
                $entranceId = szcubeResolveApartmentEntranceSectionId($sectionId);
            }
        }

        if ($entranceId <= 0) {
            return;
        }

        $backUrl = (string)($_SERVER["REQUEST_URI"] ?? "");
        $link = szcubeBuildApartmentChessAdminUrl($entranceId, $iblockId, $backUrl);
        if ($link === "") {
            return;
        }

        $items[] = array(
            "TEXT" => "Управление шахматкой",
            "TITLE" => "Открыть шахматку подъезда",
            "LINK" => $link,
            "ICON" => "btn_new",
        );
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

if (!function_exists("szcubeInjectApartmentSectionAdminUiTweaks")) {
    function szcubeInjectApartmentSectionAdminUiTweaks()
    {
        global $APPLICATION;

        if (!is_object($APPLICATION)) {
            return;
        }

        $currentPage = $APPLICATION->GetCurPage(true);
        if ($currentPage !== "/bitrix/admin/iblock_section_edit.php") {
            return;
        }

        $iblockId = isset($_REQUEST["IBLOCK_ID"]) ? (int)$_REQUEST["IBLOCK_ID"] : 0;
        if ($iblockId <= 0 || $iblockId !== szcubeGetIblockIdByCode("apartments")) {
            return;
        }

        $script = <<<'HTML'
<script>
BX.ready(function () {
    var hiddenCodes = ["UF_CHESS_SVG", "UF_CHESS_IMAGE", "UF_PIN_X", "UF_PIN_Y", "UF_PIN_LABEL"];
    var entranceOnlyCodes = ["UF_ENTRANCE_NUMBER"];
    var floorOnlyCodes = ["UF_FLOOR_NUMBER"];

    function findFieldRow(code) {
        var directRow = document.getElementById("tr_" + code);
        if (directRow) {
            return directRow;
        }

        var control = document.querySelector('[name="' + code + '"]')
            || document.querySelector('[name="' + code + '[]"]')
            || document.getElementById(code);
        if (!control) {
            return null;
        }

        return control.closest("tr")
            || control.closest(".adm-detail-content-row")
            || control.closest(".adm-detail-content-item-block")
            || control.parentElement;
    }

    function setFieldVisible(code, visible) {
        var row = findFieldRow(code);
        if (!row) {
            return;
        }

        row.style.display = visible ? "" : "none";
    }

    function getNodeTypeMode() {
        var select = document.querySelector('[name="UF_NODE_TYPE"]');
        if (!select) {
            return "";
        }

        var option = select.options && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
        var text = option && option.text ? option.text.toLowerCase() : "";

        if (text.indexOf("подъезд") !== -1) {
            return "entrance";
        }
        if (text.indexOf("этаж") !== -1) {
            return "floor";
        }

        return "";
    }

    function applyApartmentSectionUiTweaks() {
        hiddenCodes.forEach(function (code) {
            setFieldVisible(code, false);
        });

        var mode = getNodeTypeMode();
        entranceOnlyCodes.forEach(function (code) {
            setFieldVisible(code, mode === "entrance");
        });
        floorOnlyCodes.forEach(function (code) {
            setFieldVisible(code, mode === "floor");
        });
    }

    applyApartmentSectionUiTweaks();

    var nodeTypeSelect = document.querySelector('[name="UF_NODE_TYPE"]');
    if (nodeTypeSelect) {
        nodeTypeSelect.addEventListener("change", applyApartmentSectionUiTweaks);
    }
});
</script>
HTML;

        $APPLICATION->AddHeadString($script, true);
    }
}

if (!function_exists("szcubeInjectApartmentElementAdminUiTweaks")) {
    function szcubeInjectApartmentElementAdminUiTweaks()
    {
        global $APPLICATION;

        if (!is_object($APPLICATION)) {
            return;
        }

        $currentPage = $APPLICATION->GetCurPage(true);
        if ($currentPage !== "/bitrix/admin/iblock_element_edit.php") {
            return;
        }

        $iblockId = isset($_REQUEST["IBLOCK_ID"]) ? (int)$_REQUEST["IBLOCK_ID"] : 0;
        if ($iblockId <= 0 || $iblockId !== szcubeGetIblockIdByCode("apartments")) {
            return;
        }

        $propertyMap = szcubeGetApartmentPropertyMap($iblockId);
        $discountModePropertyId = isset($propertyMap["DISCOUNT_MODE"]) ? (int)$propertyMap["DISCOUNT_MODE"] : 0;
        $priceOldPropertyId = isset($propertyMap["PRICE_OLD"]) ? (int)$propertyMap["PRICE_OLD"] : 0;
        $discountPercentPropertyId = isset($propertyMap["DISCOUNT_PERCENT"]) ? (int)$propertyMap["DISCOUNT_PERCENT"] : 0;
        $discountAmountPropertyId = isset($propertyMap["DISCOUNT_AMOUNT"]) ? (int)$propertyMap["DISCOUNT_AMOUNT"] : 0;
        if ($discountModePropertyId <= 0 || $priceOldPropertyId <= 0 || $discountPercentPropertyId <= 0 || $discountAmountPropertyId <= 0) {
            return;
        }

        $modeValueToXml = array();
        $enumRes = CIBlockPropertyEnum::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "CODE" => "DISCOUNT_MODE",
            )
        );
        while ($enum = $enumRes->Fetch()) {
            $modeValueToXml[(string)$enum["ID"]] = trim((string)$enum["XML_ID"]);
        }

        $script = '<script>BX.ready(function(){'
            . 'var discountConfig=' . CUtil::PhpToJSObject(array(
                "modePropertyId" => $discountModePropertyId,
                "priceOldPropertyId" => $priceOldPropertyId,
                "discountPercentPropertyId" => $discountPercentPropertyId,
                "discountAmountPropertyId" => $discountAmountPropertyId,
                "modeMap" => $modeValueToXml,
            )) . ';'
            . 'function findPropertyRow(propertyId){'
                . 'var directRow=document.getElementById("tr_PROPERTY_"+propertyId);'
                . 'if(directRow){return directRow;}'
                . 'var control=document.querySelector(\'[name^="PROPERTY_\'+propertyId+\'"]\')||document.getElementById("PROPERTY_"+propertyId);'
                . 'if(!control){return null;}'
                . 'return control.closest("tr")||control.closest(".adm-detail-content-row")||control.closest(".adm-detail-content-item-block")||control.parentElement;'
            . '}'
            . 'function findPropertyControl(propertyId){'
                . 'return document.querySelector(\'[name^="PROPERTY_\'+propertyId+\'"]\')||document.getElementById("PROPERTY_"+propertyId);'
            . '}'
            . 'function setFieldState(propertyId, enabled){'
                . 'var row=findPropertyRow(propertyId);'
                . 'var control=findPropertyControl(propertyId);'
                . 'if(row){row.style.opacity=enabled?"":"0.45";}'
                . 'if(control){control.disabled=!enabled;}'
            . '}'
            . 'function inferMode(select){'
                . 'var oldControl=findPropertyControl(discountConfig.priceOldPropertyId);'
                . 'var percentControl=findPropertyControl(discountConfig.discountPercentPropertyId);'
                . 'var amountControl=findPropertyControl(discountConfig.discountAmountPropertyId);'
                . 'var currentMode=select? (discountConfig.modeMap[select.value]||"none") : "none";'
                . 'if(select && currentMode==="none"){'
                    . 'if(percentControl && percentControl.value){select.value=Object.keys(discountConfig.modeMap).find(function(key){return discountConfig.modeMap[key]==="percent";})||select.value;}'
                    . 'else if(amountControl && amountControl.value){select.value=Object.keys(discountConfig.modeMap).find(function(key){return discountConfig.modeMap[key]==="amount";})||select.value;}'
                    . 'else if(oldControl && oldControl.value){select.value=Object.keys(discountConfig.modeMap).find(function(key){return discountConfig.modeMap[key]==="old_price";})||select.value;}'
                . '}'
            . '}'
            . 'function applyDiscountUi(){'
                . 'var select=findPropertyControl(discountConfig.modePropertyId);'
                . 'if(!select){return;}'
                . 'inferMode(select);'
                . 'var mode=discountConfig.modeMap[select.value]||"none";'
                . 'setFieldState(discountConfig.priceOldPropertyId, mode==="old_price");'
                . 'setFieldState(discountConfig.discountPercentPropertyId, mode==="percent");'
                . 'setFieldState(discountConfig.discountAmountPropertyId, mode==="amount");'
            . '}'
            . 'applyDiscountUi();'
            . 'var modeSelect=findPropertyControl(discountConfig.modePropertyId);'
            . 'if(modeSelect){modeSelect.addEventListener("change",applyDiscountUi);}'
        . '});</script>';

        $APPLICATION->AddHeadString($script, true);
    }
}

if (!function_exists("szcubeInjectApartmentChessAdminToolbarButton")) {
    function szcubeInjectApartmentChessAdminToolbarButton()
    {
        global $APPLICATION;

        if (!is_object($APPLICATION)) {
            return;
        }

        $currentPage = $APPLICATION->GetCurPage(true);
        if (!in_array($currentPage, array("/bitrix/admin/iblock_element_edit.php", "/bitrix/admin/iblock_section_edit.php"), true)) {
            return;
        }

        $iblockId = isset($_REQUEST["IBLOCK_ID"]) ? (int)$_REQUEST["IBLOCK_ID"] : 0;
        if ($iblockId <= 0 || $iblockId !== szcubeGetIblockIdByCode("apartments")) {
            return;
        }

        $entranceId = 0;
        if ($currentPage === "/bitrix/admin/iblock_element_edit.php") {
            $elementId = isset($_REQUEST["ID"]) ? (int)$_REQUEST["ID"] : 0;
            if ($elementId > 0) {
                $entranceId = szcubeGetApartmentEntranceSectionIdByElement($elementId);
            }
        } else {
            $sectionId = isset($_REQUEST["ID"]) ? (int)$_REQUEST["ID"] : 0;
            if ($sectionId <= 0 && isset($_REQUEST["find_section_section"])) {
                $sectionId = (int)$_REQUEST["find_section_section"];
            }
            if ($sectionId > 0) {
                $entranceId = szcubeResolveApartmentEntranceSectionId($sectionId);
            }
        }

        if ($entranceId <= 0) {
            return;
        }

        $backUrl = (string)($_SERVER["REQUEST_URI"] ?? "");
        $link = szcubeBuildApartmentChessAdminUrl($entranceId, $iblockId, $backUrl);
        if ($link === "") {
            return;
        }

        $script = '<script>BX.ready(function(){'
            . 'var link=' . CUtil::PhpToJSObject($link) . ';'
            . 'if(!link || document.querySelector(".js-apartment-chess-toolbar-btn")){return;}'
            . 'var toolbar=document.querySelector(".adm-detail-toolbar");'
            . 'if(!toolbar){return;}'
            . 'var right=toolbar.querySelector(".adm-detail-toolbar-right")||toolbar;'
            . 'var host=right.querySelector(".adm-btns")||right;'
            . 'if(!host){return;}'
            . 'var hasNativeButton=Array.prototype.slice.call(host.querySelectorAll("a,button,span")).some(function(node){'
                . 'return (node.textContent||"").trim()==="Управление шахматкой";'
            . '});'
            . 'if(hasNativeButton){return;}'
            . 'var button=document.createElement("a");'
            . 'button.href=link;'
            . 'button.className="adm-btn js-apartment-chess-toolbar-btn";'
            . 'button.textContent="Управление шахматкой";'
            . 'button.title="Открыть шахматку подъезда";'
            . 'var actionsTarget=Array.prototype.slice.call(host.querySelectorAll("a,button,span")).find(function(node){'
                . 'var text=(node.textContent||"").trim().toLowerCase();'
                . 'return text==="действия";'
            . '});'
            . 'if(actionsTarget && actionsTarget.parentNode===host){'
                . 'host.insertBefore(button, actionsTarget);'
            . '}else{'
                . 'host.appendChild(button);'
            . '}'
        . '});</script>';

        $APPLICATION->AddHeadString($script, true);
    }
}

if (!function_exists("szcubeBuildLeadsAdminMenu")) {
    function szcubeBuildLeadsAdminMenu(&$aGlobalMenu, &$aModuleMenu)
    {
        global $USER;

        if (!is_object($USER) || !$USER->IsAdmin()) {
            return;
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

        $aModuleMenu[] = array(
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
    }
}

if (!function_exists("szcubeAddAdminFavicon")) {
    function szcubeAddAdminFavicon()
    {
        global $APPLICATION;

        if (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true || !is_object($APPLICATION)) {
            return;
        }

        $APPLICATION->AddHeadString('<link rel="icon" href="/local/templates/szcube/img/favicon.svg" type="image/svg+xml">', true);
        $APPLICATION->AddHeadString('<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">', true);
    }
}

AddEventHandler("iblock", "OnBeforeIBlockElementAdd", "szcubePrepareApartmentBeforeSave");
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", "szcubePrepareApartmentBeforeSave");
AddEventHandler("iblock", "OnAfterIBlockElementAdd", "szcubeSyncProjectDynamicElementSection");
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", "szcubeSyncProjectDynamicElementSection");
AddEventHandler("form", "onAfterResultAdd", "szcubeHandleLeadResultCreated");
AddEventHandler("main", "OnBuildGlobalMenu", "szcubeBuildLeadsAdminMenu");
AddEventHandler("main", "OnAdminPageBeforeShow", "szcubeAddAdminFavicon");
AddEventHandler("main", "OnAdminContextMenuShow", "szcubeAddApartmentChessAdminContextButton");
AddEventHandler("main", "OnProlog", "szcubeInjectApartmentSectionAdminUiTweaks");
AddEventHandler("main", "OnProlog", "szcubeInjectApartmentElementAdminUiTweaks");
AddEventHandler("main", "OnProlog", "szcubeInjectApartmentChessAdminToolbarButton");

<?php
define("COMMERCIAL_DETAIL_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

if (!CModule::IncludeModule("iblock")) {
    ShowError("Не удалось подключить модуль iblock");
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
    return;
}

if (!function_exists("commercialDetailAppendFact")) {
    function commercialDetailAppendFact(&$facts, $label, $value, $extra = array())
    {
        if ($value === null) {
            return;
        }

        $value = is_string($value) ? trim($value) : $value;
        if ($value === "") {
            return;
        }

        $facts[] = array_merge(
            array(
                "label" => (string)$label,
                "value" => $value,
            ),
            is_array($extra) ? $extra : array()
        );
    }
}

if (!function_exists("commercialDetailFindIblockByCode")) {
    function commercialDetailFindIblockByCode($code)
    {
        $res = CIBlock::GetList(array(), array("=CODE" => (string)$code, "ACTIVE" => "Y"), false);
        return $res->Fetch() ?: null;
    }
}

if (!function_exists("commercialDetailLoadProperties")) {
    function commercialDetailLoadProperties($iblockId, $elementId)
    {
        $result = array();
        $res = CIBlockElement::GetProperty(
            (int)$iblockId,
            (int)$elementId,
            array("SORT" => "ASC", "ID" => "ASC"),
            array()
        );

        while ($row = $res->Fetch()) {
            $code = trim((string)$row["CODE"]);
            if ($code === "") {
                continue;
            }

            if (!isset($result[$code])) {
                $result[$code] = array(
                    "CODE" => $code,
                    "NAME" => isset($row["NAME"]) ? (string)$row["NAME"] : $code,
                    "PROPERTY_TYPE" => isset($row["PROPERTY_TYPE"]) ? (string)$row["PROPERTY_TYPE"] : "",
                    "MULTIPLE" => isset($row["MULTIPLE"]) ? (string)$row["MULTIPLE"] : "N",
                    "VALUE" => $row["MULTIPLE"] === "Y" ? array() : "",
                    "VALUE_XML_ID" => $row["MULTIPLE"] === "Y" ? array() : "",
                    "VALUE_ENUM" => $row["MULTIPLE"] === "Y" ? array() : "",
                    "DESCRIPTION" => $row["MULTIPLE"] === "Y" ? array() : "",
                );
            }

            if ($row["MULTIPLE"] === "Y") {
                $result[$code]["VALUE"][] = $row["VALUE"];
                $result[$code]["VALUE_XML_ID"][] = $row["VALUE_XML_ID"];
                $result[$code]["VALUE_ENUM"][] = $row["VALUE_ENUM"];
                $result[$code]["DESCRIPTION"][] = $row["DESCRIPTION"];
                continue;
            }

            $result[$code]["VALUE"] = $row["VALUE"];
            $result[$code]["VALUE_XML_ID"] = $row["VALUE_XML_ID"];
            $result[$code]["VALUE_ENUM"] = $row["VALUE_ENUM"];
            $result[$code]["DESCRIPTION"] = $row["DESCRIPTION"];
        }

        return $result;
    }
}

if (!function_exists("commercialDetailPropertyScalar")) {
    function commercialDetailPropertyScalar(array $properties, $code, $default = "")
    {
        if (!isset($properties[$code])) {
            return (string)$default;
        }

        $value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : "";
        if (is_array($value)) {
            $value = reset($value);
        }

        $value = trim((string)$value);
        return $value !== "" ? $value : (string)$default;
    }
}

if (!function_exists("commercialDetailPropertyEnumXmlId")) {
    function commercialDetailPropertyEnumXmlId(array $properties, $code, $default = "")
    {
        if (!isset($properties[$code])) {
            return (string)$default;
        }

        $value = isset($properties[$code]["VALUE_XML_ID"]) ? $properties[$code]["VALUE_XML_ID"] : "";
        if (is_array($value)) {
            $value = reset($value);
        }

        $value = trim((string)$value);
        return $value !== "" ? $value : (string)$default;
    }
}

if (!function_exists("commercialDetailPropertyEnumLabel")) {
    function commercialDetailPropertyEnumLabel(array $properties, $code, $default = "")
    {
        if (!isset($properties[$code])) {
            return (string)$default;
        }

        $value = isset($properties[$code]["VALUE_ENUM"]) ? $properties[$code]["VALUE_ENUM"] : "";
        if (is_array($value)) {
            $value = reset($value);
        }

        $value = trim((string)$value);
        return $value !== "" ? $value : (string)$default;
    }
}

if (!function_exists("commercialDetailPropertyMultipleScalars")) {
    function commercialDetailPropertyMultipleScalars(array $properties, $code)
    {
        if (!isset($properties[$code])) {
            return array();
        }

        $value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : array();
        if (!is_array($value)) {
            $value = $value !== "" ? array($value) : array();
        }

        return array_values(array_filter(array_map("trim", $value), static function ($item) {
            return $item !== "";
        }));
    }
}

if (!function_exists("commercialDetailPropertyFileUrl")) {
    function commercialDetailPropertyFileUrl(array $properties, $code, $default = "")
    {
        if (!isset($properties[$code])) {
            return (string)$default;
        }

        $value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : 0;
        if (is_array($value)) {
            $value = reset($value);
        }

        $fileId = (int)$value;
        if ($fileId <= 0) {
            return (string)$default;
        }

        $path = CFile::GetPath($fileId);
        return $path ? (string)$path : (string)$default;
    }
}

if (!function_exists("commercialDetailFormatFloat")) {
    function commercialDetailFormatFloat($value, $precision = 1)
    {
        $value = trim((string)$value);
        if ($value === "") {
            return "";
        }

        $value = str_replace(array(" ", ","), array("", "."), $value);
        $number = (float)$value;
        $isInteger = abs($number - round($number)) < 0.00001;
        return $isInteger
            ? (string)(int)round($number)
            : number_format($number, (int)$precision, ".", "");
    }
}

if (!function_exists("commercialDetailFormatArea")) {
    function commercialDetailFormatArea($value)
    {
        $formatted = commercialDetailFormatFloat($value, 1);
        return $formatted !== "" ? $formatted . " м²" : "";
    }
}

if (!function_exists("commercialDetailFormatCeiling")) {
    function commercialDetailFormatCeiling($value)
    {
        $formatted = commercialDetailFormatFloat($value, 2);
        return $formatted !== "" ? $formatted . " м" : "";
    }
}

if (!function_exists("commercialDetailFormatMoney")) {
    function commercialDetailFormatMoney($value)
    {
        $value = (float)$value;
        if ($value <= 0) {
            return "";
        }

        return number_format($value, 0, ".", " ") . " ₽";
    }
}

if (!function_exists("commercialDetailFormatAddress")) {
    function commercialDetailFormatAddress($value)
    {
        $value = trim((string)$value);
        if ($value === "") {
            return "";
        }

        $value = (string)preg_replace(
            "/\\b(г\\.|ул\\.|пер\\.|пр-т|просп\\.|бул\\.|б-р|наб\\.|ш\\.)\\s+/u",
            "$1\xC2\xA0",
            $value
        );

        return (string)preg_replace(
            "/\\s+((?:дом\\s+)?[0-9]+[0-9A-Za-zА-Яа-я\\/-]*)$/u",
            "\xC2\xA0$1",
            $value
        );
    }
}

if (!function_exists("commercialDetailDiscountBadge")) {
    function commercialDetailDiscountBadge($priceTotal, $priceOld)
    {
        $priceTotal = (float)$priceTotal;
        $priceOld = (float)$priceOld;
        if ($priceOld <= 0 || $priceTotal <= 0 || $priceOld <= $priceTotal) {
            return "";
        }

        $discountPercent = (int)round((($priceOld - $priceTotal) / $priceOld) * 100);
        if ($discountPercent <= 0) {
            return "";
        }

        return "Скидка " . $discountPercent . "%";
    }
}

if (!function_exists("commercialDetailIsPubliclyHiddenStatus")) {
    function commercialDetailIsPubliclyHiddenStatus($statusKey, $statusLabel = "")
    {
        $statusKey = trim(mb_strtolower((string)$statusKey));
        $statusLabel = trim(mb_strtolower((string)$statusLabel));

        if ($statusKey === "sold") {
            return true;
        }

        return $statusLabel !== "" && preg_match("/^продан[а-я]*$/u", $statusLabel) === 1;
    }
}

if (!function_exists("commercialDetailFloorShortDisplay")) {
    function commercialDetailFloorShortDisplay($floor, $houseFloors)
    {
        $floor = (int)$floor;
        $houseFloors = (int)$houseFloors;

        if ($floor > 0 && $houseFloors > 0) {
            return $floor . "/" . $houseFloors . " этаж";
        }
        if ($floor > 0) {
            return $floor . " этаж";
        }

        return "";
    }
}

if (!function_exists("commercialDetailElementUrl")) {
    function commercialDetailElementUrl($template, array $fields, $fallbackPrefix)
    {
        $template = trim((string)$template);
        if ($template !== "") {
            $url = (string)CIBlock::ReplaceDetailUrl($template, $fields, false, "E");
            if ($url !== "") {
                return $url;
            }
        }

        $code = isset($fields["CODE"]) ? trim((string)$fields["CODE"]) : "";
        if ($code === "") {
            return "";
        }

        return rtrim((string)$fallbackPrefix, "/") . "/" . $code . "/";
    }
}

if (!function_exists("commercialDetailBuildSlidesFromProperties")) {
    function commercialDetailBuildSlidesFromProperties(array $fields, array $properties)
    {
        $previewImage = (int)$fields["PREVIEW_PICTURE"] > 0 ? (string)CFile::GetPath((int)$fields["PREVIEW_PICTURE"]) : "";
        $lotCode = trim((string)$fields["CODE"]);

        $definitions = array(
            array(
                "label" => "Планировка",
                "kind" => "plan",
                "bearing" => 214,
                "image" => commercialDetailPropertyFileUrl($properties, "PLAN_IMAGE", $previewImage),
                "title_code" => "PLAN_TITLE",
                "text_code" => "PLAN_TEXT",
                "alt_code" => "PLAN_ALT",
                "default_title" => "Планировка",
                "default_alt" => $lotCode !== "" ? "Планировка помещения " . $lotCode : "Планировка помещения",
            ),
            array(
                "label" => "На этаже",
                "kind" => "scheme",
                "bearing" => 228,
                "image" => commercialDetailPropertyFileUrl($properties, "FLOOR_SLIDE_IMAGE", ""),
                "title_code" => "FLOOR_SLIDE_TITLE",
                "text_code" => "FLOOR_SLIDE_TEXT",
                "alt_code" => "FLOOR_SLIDE_ALT",
                "default_title" => "На этаже",
                "default_alt" => "Схема расположения помещения на этаже",
            ),
            array(
                "label" => "В корпусе",
                "kind" => "render",
                "bearing" => 206,
                "image" => commercialDetailPropertyFileUrl($properties, "BUILDING_SLIDE_IMAGE", ""),
                "title_code" => "BUILDING_SLIDE_TITLE",
                "text_code" => "BUILDING_SLIDE_TEXT",
                "alt_code" => "BUILDING_SLIDE_ALT",
                "default_title" => "В корпусе",
                "default_alt" => "Положение помещения в корпусе",
            ),
            array(
                "label" => "Вид",
                "kind" => "photo",
                "bearing" => 247,
                "image" => commercialDetailPropertyFileUrl($properties, "VIEW_SLIDE_IMAGE", ""),
                "title_code" => "VIEW_SLIDE_TITLE",
                "text_code" => "VIEW_SLIDE_TEXT",
                "alt_code" => "VIEW_SLIDE_ALT",
                "default_title" => "Вид",
                "default_alt" => "Вид помещения",
            ),
            array(
                "label" => "Визуализация",
                "kind" => "render",
                "bearing" => 214,
                "image" => commercialDetailPropertyFileUrl($properties, "RENDER_SLIDE_IMAGE", ""),
                "title_code" => "RENDER_SLIDE_TITLE",
                "text_code" => "RENDER_SLIDE_TEXT",
                "alt_code" => "RENDER_SLIDE_ALT",
                "default_title" => "Визуализация",
                "default_alt" => "Визуализация помещения",
            ),
        );

        $slides = array();
        foreach ($definitions as $definition) {
            $image = trim((string)$definition["image"]);
            if ($image === "") {
                continue;
            }

            $slides[] = array(
                "label" => $definition["label"],
                "title" => commercialDetailPropertyScalar($properties, $definition["title_code"], $definition["default_title"]),
                "description" => commercialDetailPropertyScalar($properties, $definition["text_code"], ""),
                "image" => $image,
                "alt" => commercialDetailPropertyScalar($properties, $definition["alt_code"], $definition["default_alt"]),
                "bearing" => (int)$definition["bearing"],
                "kind" => $definition["kind"],
            );
        }

        return $slides;
    }
}

$code = isset($_GET["code"]) ? trim((string)$_GET["code"]) : "";
$commercial = null;
$commercialId = 0;
$commercialIblockId = 0;
$projectId = 0;
$projectCode = "";
$projectExtraItems = array();
$similarCommercials = array();

$projectAdvantagesIblockId = function_exists("szcubeGetIblockIdByCode") ? (int)szcubeGetIblockIdByCode("project_advantages") : 0;
$projectAdvantagesIblockType = "";
if ($projectAdvantagesIblockId > 0) {
    $iblockMeta = CIBlock::GetByID($projectAdvantagesIblockId)->Fetch();
    if ($iblockMeta) {
        $projectAdvantagesIblockType = (string)$iblockMeta["IBLOCK_TYPE_ID"];
    }
}

$homePromotionsIblockId = function_exists("szcubeGetIblockIdByCode") ? (int)szcubeGetIblockIdByCode("promotions") : 0;
$homePromotionsIblockType = "";
if ($homePromotionsIblockId > 0) {
    $iblockMeta = CIBlock::GetByID($homePromotionsIblockId)->Fetch();
    if ($iblockMeta) {
        $homePromotionsIblockType = (string)$iblockMeta["IBLOCK_TYPE_ID"];
    }
}

$commercialIblock = commercialDetailFindIblockByCode("commercial");
$projectsIblock = commercialDetailFindIblockByCode("projects");

if ($commercialIblock && $code !== "") {
    $commercialIblockId = (int)$commercialIblock["ID"];
    $commercialRes = CIBlockElement::GetList(
        array(),
        array(
            "IBLOCK_ID" => $commercialIblockId,
            "=CODE" => $code,
            "ACTIVE" => "Y",
        ),
        false,
        false,
        array("ID", "IBLOCK_ID", "NAME", "CODE", "PREVIEW_PICTURE", "DETAIL_PAGE_URL", "SORT")
    );

    if ($commercialFields = $commercialRes->Fetch()) {
        $commercialId = (int)$commercialFields["ID"];
        $commercialProperties = commercialDetailLoadProperties($commercialIblockId, $commercialId);

        $projectId = (int)commercialDetailPropertyScalar($commercialProperties, "PROJECT", 0);
        $projectName = "";
        $projectUrl = "";
        $street = "";
        $handover = "";

        if ($projectsIblock && $projectId > 0) {
            $projectRes = CIBlockElement::GetList(
                array(),
                array("IBLOCK_ID" => (int)$projectsIblock["ID"], "ID" => $projectId, "ACTIVE" => "Y"),
                false,
                false,
                array("ID", "IBLOCK_ID", "NAME", "CODE")
            );
            if ($projectFields = $projectRes->Fetch()) {
                $projectProperties = commercialDetailLoadProperties((int)$projectsIblock["ID"], (int)$projectFields["ID"]);
                $projectName = trim((string)$projectFields["NAME"]);
                $projectCode = trim((string)$projectFields["CODE"]);
                $projectUrl = $projectCode !== "" ? "/projects/" . $projectCode . "/" : "";
                $street = commercialDetailFormatAddress(commercialDetailPropertyScalar($projectProperties, "ADDRESS", ""));
                $handover = commercialDetailPropertyScalar($projectProperties, "DELIVERY_TEXT", "");
                if (function_exists("szcubeGetExtraCards")) {
                    $projectExtraItems = szcubeGetExtraCards("project", $projectCode);
                }
            }
        }

        $typeLabel = commercialDetailPropertyEnumLabel($commercialProperties, "COMMERCIAL_TYPE", "Коммерческое помещение");
        $typeKey = commercialDetailPropertyEnumXmlId($commercialProperties, "COMMERCIAL_TYPE", "");
        $number = commercialDetailPropertyScalar($commercialProperties, "COMMERCIAL_NUMBER", trim((string)$commercialFields["NAME"]));
        $floor = commercialDetailPropertyScalar($commercialProperties, "FLOOR", "");
        $houseFloors = commercialDetailPropertyScalar($commercialProperties, "HOUSE_FLOORS", "");
        $areaTotalRaw = (float)commercialDetailPropertyScalar($commercialProperties, "AREA_TOTAL", 0);
        $priceTotalRaw = (float)commercialDetailPropertyScalar($commercialProperties, "PRICE_TOTAL", 0);
        $priceOldRaw = (float)commercialDetailPropertyScalar($commercialProperties, "PRICE_OLD", 0);
        $priceM2Raw = (float)commercialDetailPropertyScalar($commercialProperties, "PRICE_M2", 0);
        if ($priceM2Raw <= 0 && $areaTotalRaw > 0 && $priceTotalRaw > 0) {
            $priceM2Raw = round($priceTotalRaw / $areaTotalRaw, 0);
        }

        $statusKey = commercialDetailPropertyEnumXmlId($commercialProperties, "STATUS", "");
        $statusLabel = commercialDetailPropertyEnumLabel($commercialProperties, "STATUS", "");
        $discountBadge = commercialDetailDiscountBadge($priceTotalRaw, $priceOldRaw);
        $badges = commercialDetailPropertyMultipleScalars($commercialProperties, "BADGES");
        if ($discountBadge !== "" && !in_array($discountBadge, $badges, true)) {
            $badges[] = $discountBadge;
        }

        $primaryFacts = array();
        commercialDetailAppendFact($primaryFacts, "ЖК", $projectName, array("url" => $projectUrl));
        commercialDetailAppendFact($primaryFacts, "Адрес", $street);
        commercialDetailAppendFact($primaryFacts, "Корпус", commercialDetailPropertyScalar($commercialProperties, "CORPUS", ""));
        commercialDetailAppendFact($primaryFacts, "Этаж", commercialDetailFloorShortDisplay($floor, $houseFloors));
        commercialDetailAppendFact($primaryFacts, "Сдача", $handover);

        $detailFacts = array();
        commercialDetailAppendFact($detailFacts, "Тип помещения", $typeLabel);
        commercialDetailAppendFact($detailFacts, "Номер", $number);
        commercialDetailAppendFact($detailFacts, "Площадь", commercialDetailFormatArea($areaTotalRaw));
        commercialDetailAppendFact($detailFacts, "Цена за м²", commercialDetailFormatMoney($priceM2Raw));
        commercialDetailAppendFact($detailFacts, "Высота потолков", commercialDetailFormatCeiling(commercialDetailPropertyScalar($commercialProperties, "CEILING", "")));
        commercialDetailAppendFact($detailFacts, "Вход / секция", commercialDetailPropertyScalar($commercialProperties, "ENTRANCE", ""));
        commercialDetailAppendFact($detailFacts, "Отдельный вход", commercialDetailPropertyEnumLabel($commercialProperties, "SEPARATE_ENTRANCE", ""));
        commercialDetailAppendFact($detailFacts, "Витринные окна", commercialDetailPropertyEnumLabel($commercialProperties, "SHOWCASE_WINDOWS", ""));
        commercialDetailAppendFact($detailFacts, "Мокрая точка", commercialDetailPropertyEnumLabel($commercialProperties, "WET_POINT", ""));
        $powerKw = commercialDetailPropertyScalar($commercialProperties, "POWER_KW", "");
        commercialDetailAppendFact($detailFacts, "Мощность", $powerKw !== "" ? commercialDetailFormatFloat($powerKw, 0) . " кВт" : "");
        commercialDetailAppendFact($detailFacts, "Отделка", commercialDetailPropertyEnumLabel($commercialProperties, "FINISH", ""));

        $commercial = array(
            "number" => $number,
            "title_line_1" => $typeLabel,
            "title_line_2" => commercialDetailFormatArea($areaTotalRaw),
            "description" => commercialDetailPropertyScalar($commercialProperties, "DESCRIPTION", ""),
            "feature_tags" => commercialDetailPropertyMultipleScalars($commercialProperties, "FEATURE_TAGS"),
            "badges" => $badges,
            "availability_status" => $statusKey,
            "availability_label" => $statusLabel,
            "availability_badges" => $statusKey !== "" && $statusLabel !== "" ? array(array("status" => $statusKey, "label" => $statusLabel)) : array(),
            "discount" => $discountBadge,
            "price_total" => commercialDetailFormatMoney($priceTotalRaw),
            "price_old" => commercialDetailFormatMoney($priceOldRaw),
            "price_meter" => commercialDetailFormatMoney($priceM2Raw),
            "primary_facts" => $primaryFacts,
            "detail_facts" => $detailFacts,
            "slides" => commercialDetailBuildSlidesFromProperties($commercialFields, $commercialProperties),
        );

        if ($commercialIblockId > 0 && $projectId > 0) {
            $similarCandidates = array();
            $similarRes = CIBlockElement::GetList(
                array("SORT" => "ASC", "ID" => "ASC"),
                array(
                    "IBLOCK_ID" => $commercialIblockId,
                    "ACTIVE" => "Y",
                    "PROPERTY_PROJECT" => $projectId,
                    "!ID" => $commercialId,
                ),
                false,
                false,
                array("ID", "IBLOCK_ID", "NAME", "CODE", "PREVIEW_PICTURE", "DETAIL_PAGE_URL", "SORT")
            );

            while ($similarFields = $similarRes->Fetch()) {
                $similarProperties = commercialDetailLoadProperties($commercialIblockId, (int)$similarFields["ID"]);
                $similarStatusKey = commercialDetailPropertyEnumXmlId($similarProperties, "STATUS", "");
                $similarStatusLabel = commercialDetailPropertyEnumLabel($similarProperties, "STATUS", "");
                if (commercialDetailIsPubliclyHiddenStatus($similarStatusKey, $similarStatusLabel)) {
                    continue;
                }

                $similarTypeKey = commercialDetailPropertyEnumXmlId($similarProperties, "COMMERCIAL_TYPE", "");
                $similarTypeLabel = commercialDetailPropertyEnumLabel($similarProperties, "COMMERCIAL_TYPE", "Коммерческое помещение");
                $similarAreaRaw = (float)commercialDetailPropertyScalar($similarProperties, "AREA_TOTAL", 0);
                $similarPriceRaw = (float)commercialDetailPropertyScalar($similarProperties, "PRICE_TOTAL", 0);
                $similarPriceOldRaw = (float)commercialDetailPropertyScalar($similarProperties, "PRICE_OLD", 0);
                $similarFloor = commercialDetailPropertyScalar($similarProperties, "FLOOR", "");
                $similarHouseFloors = commercialDetailPropertyScalar($similarProperties, "HOUSE_FLOORS", "");
                $similarPlanImage = commercialDetailPropertyFileUrl($similarProperties, "PLAN_IMAGE", "");
                if ($similarPlanImage === "" && (int)$similarFields["PREVIEW_PICTURE"] > 0) {
                    $similarPlanImage = (string)CFile::GetPath((int)$similarFields["PREVIEW_PICTURE"]);
                }

                $similarBadges = commercialDetailPropertyMultipleScalars($similarProperties, "BADGES");
                $similarDiscountBadge = commercialDetailDiscountBadge($similarPriceRaw, $similarPriceOldRaw);
                if ($similarDiscountBadge !== "" && !in_array($similarDiscountBadge, $similarBadges, true)) {
                    $similarBadges[] = $similarDiscountBadge;
                }

                $similarUrl = trim((string)$similarFields["DETAIL_PAGE_URL"]);
                if ($similarUrl === "" || strpos($similarUrl, "#") !== false) {
                    $similarUrl = commercialDetailElementUrl(isset($commercialIblock["DETAIL_PAGE_URL"]) ? (string)$commercialIblock["DETAIL_PAGE_URL"] : "", $similarFields, "/commerce");
                }

                $listMeta = array();
                if ($similarAreaRaw > 0) {
                    $listMeta[] = commercialDetailFormatArea($similarAreaRaw);
                }
                $floorShort = commercialDetailFloorShortDisplay($similarFloor, $similarHouseFloors);
                if ($floorShort !== "") {
                    $listMeta[] = $floorShort;
                }

                $similarCandidates[] = array(
                    "sort" => isset($similarFields["SORT"]) ? (int)$similarFields["SORT"] : 500,
                    "type_match" => $typeKey !== "" && $similarTypeKey === $typeKey ? 1 : 0,
                    "area_diff" => abs($similarAreaRaw - $areaTotalRaw),
                    "price_diff" => abs($similarPriceRaw - $priceTotalRaw),
                    "card" => array(
                        "url" => $similarUrl,
                        "project_name" => $projectName,
                        "project_delivery" => $handover,
                        "rooms_label" => $similarTypeLabel,
                        "list_meta" => implode(" • ", $listMeta),
                        "price_total_formatted" => commercialDetailFormatMoney($similarPriceRaw),
                        "price_old_formatted" => commercialDetailFormatMoney($similarPriceOldRaw),
                        "status_label" => $similarStatusLabel,
                        "plan_image" => $similarPlanImage,
                        "plan_alt" => commercialDetailPropertyScalar($similarProperties, "PLAN_ALT", trim((string)$similarFields["NAME"])),
                        "badges" => $similarBadges,
                        "board_url" => "",
                    ),
                );
            }

            usort($similarCandidates, static function ($left, $right) {
                if ($left["type_match"] !== $right["type_match"]) {
                    return $right["type_match"] <=> $left["type_match"];
                }
                if ($left["area_diff"] !== $right["area_diff"]) {
                    return $left["area_diff"] <=> $right["area_diff"];
                }
                if ($left["price_diff"] !== $right["price_diff"]) {
                    return $left["price_diff"] <=> $right["price_diff"];
                }

                return $left["sort"] <=> $right["sort"];
            });

            $similarCandidates = array_slice($similarCandidates, 0, 6);
            foreach ($similarCandidates as $similarCandidate) {
                $similarCommercials[] = $similarCandidate["card"];
            }
        }
    }
}

$APPLICATION->SetTitle($commercial && $commercial["title_line_1"] !== "" ? $commercial["title_line_1"] : "Коммерческое помещение");
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<?php if (!$commercial): ?>
<section class="apartment-detail apartment-detail--empty">
  <div class="container">
    <h1 class="section-title"><?php $APPLICATION->ShowTitle(false); ?></h1>
    <p>Помещение не найдено.</p>
  </div>
</section>
<?php else: ?>
<?php $isSoldCommercial = isset($commercial["availability_status"]) && (string)$commercial["availability_status"] === "sold"; ?>
<section class="apartment-detail">
  <div class="container">
    <div class="apartment-hero">
      <?php if (!empty($commercial["slides"])): ?>
      <div class="apartment-hero__media">
        <div class="apartment-hero__viewer-shell" data-apartment-gallery>
          <?php if (!empty($commercial["discount"])): ?>
          <div class="apartment-hero__badge apartment-hero__badge--discount"><?= htmlspecialcharsbx($commercial["discount"]) ?></div>
          <?php endif; ?>
          <div class="apartment-hero__rail">
            <?php if (!empty($commercial["availability_badges"])): ?>
              <?php $statusBadge = reset($commercial["availability_badges"]); ?>
              <div class="apartment-hero__badge apartment-hero__badge--status apartment-hero__badge--<?= htmlspecialcharsbx($statusBadge["status"]) ?>">
                <?= htmlspecialcharsbx($statusBadge["label"]) ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($commercial["badges"])): ?>
              <?php foreach ($commercial["badges"] as $badge): ?>
                <div class="apartment-hero__badge apartment-hero__badge--status">
                  <?= htmlspecialcharsbx($badge) ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
            <div class="apartment-hero__actions">
              <button class="apartment-hero__action" type="button" data-apartment-action="zoom" aria-label="Увеличить слайд">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <circle cx="10" cy="10" r="6.25" stroke="currentColor" stroke-width="1.5" />
                  <path d="M10 7V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                  <path d="M7 10H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                  <path d="M14.5 14.5L18 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
              </button>
              <button class="apartment-hero__action" type="button" data-apartment-action="favorite" aria-label="Добавить в избранное">
                <svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M10.9988 18.1463L9.77616 17.0337C5.43468 13.1098 2.56445 10.5081 2.56445 7.31585C2.56445 4.71413 4.59884 2.69336 7.20562 2.69336C8.67789 2.69336 10.0906 3.37417 10.9988 4.44819C11.9071 3.37417 13.3198 2.69336 14.7921 2.69336C17.3989 2.69336 19.4333 4.71413 19.4333 7.31585C19.4333 10.5081 16.563 13.1098 12.2215 17.0412L10.9988 18.1463Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
              <button class="apartment-hero__action" type="button" data-apartment-action="share" aria-label="Поделиться">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M14.8398 7.16113L7.16035 14.8406" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                  <path d="M9.37988 5.68164H16.3199V12.6216" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
              <button class="apartment-hero__action" type="button" data-apartment-action="print" aria-label="Печать">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M17 13.01L17.01 12.9989" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  <path d="M7 17H17M6 10V3.6C6 3.26863 6.26863 3 6.6 3H17.4C17.7314 3 18 3.26863 18 3.6V10M21 20.4V14C21 11.7909 19.2091 10 17 10H7C4.79086 10 3 11.7909 3 14V20.4C3 20.7314 3.26863 21 3.6 21H20.4C20.7314 21 21 20.7314 21 20.4Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
            </div>
          </div>

          <div class="apartment-hero__viewer">
            <div class="swiper apartment-hero__swiper" data-apartment-swiper>
              <div class="swiper-wrapper">
                <?php foreach ($commercial["slides"] as $index => $slide): ?>
                <div
                  class="swiper-slide apartment-hero__slide apartment-hero__slide--<?= htmlspecialcharsbx($slide["kind"]) ?>"
                  data-bearing="<?= (int)$slide["bearing"] ?>"
                >
                  <div class="apartment-hero__slide-media">
                    <img src="<?= htmlspecialcharsbx($slide["image"]) ?>" alt="<?= htmlspecialcharsbx($slide["alt"]) ?>" loading="lazy" />
                  </div>
                  <div class="apartment-hero__slide-caption">
                    <?php if (!empty($slide["title"])): ?>
                    <div class="apartment-hero__slide-title"><?= htmlspecialcharsbx($slide["title"]) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($slide["description"])): ?>
                    <p><?= htmlspecialcharsbx($slide["description"]) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <?php if (count($commercial["slides"]) > 1): ?>
          <div class="apartment-hero__tabs-row">
            <div class="apartment-hero__tabs" role="tablist" aria-label="Режимы просмотра помещения">
              <?php foreach ($commercial["slides"] as $index => $slide): ?>
              <button
                class="apartment-hero__tab<?= $index === 0 ? " is-active" : "" ?>"
                type="button"
                role="tab"
                aria-selected="<?= $index === 0 ? "true" : "false" ?>"
                data-apartment-tab="<?= $index ?>"
              >
                <?= htmlspecialcharsbx($slide["label"]) ?>
              </button>
              <?php endforeach; ?>
            </div>

            <div class="apartment-hero__nav">
              <button class="apartment-hero__nav-btn apartment-hero__nav-btn--prev" type="button" data-apartment-prev aria-label="Предыдущий слайд">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M10.5 4.5L6 9L10.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
              <button class="apartment-hero__nav-btn apartment-hero__nav-btn--next" type="button" data-apartment-next aria-label="Следующий слайд">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M7.5 4.5L12 9L7.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
            </div>
          </div>
          <?php endif; ?>

          <div class="apartment-hero__lightbox" data-apartment-lightbox hidden>
            <button class="apartment-hero__lightbox-backdrop" type="button" data-apartment-lightbox-close aria-label="Закрыть просмотр"></button>
            <div class="apartment-hero__lightbox-dialog" role="dialog" aria-modal="true" aria-label="Просмотр изображения помещения">
              <button class="apartment-hero__lightbox-close" type="button" data-apartment-lightbox-close aria-label="Закрыть">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M5 5L15 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                  <path d="M15 5L5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
              </button>
              <div class="apartment-hero__lightbox-stage">
                <button class="apartment-hero__lightbox-nav apartment-hero__lightbox-nav--prev" type="button" data-apartment-lightbox-prev aria-label="Предыдущий слайд">
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M10.5 4.5L6 9L10.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </button>
                <figure class="apartment-hero__lightbox-figure">
                  <img class="apartment-hero__lightbox-image" data-apartment-lightbox-image src="" alt="" />
                  <figcaption class="apartment-hero__lightbox-caption" data-apartment-lightbox-caption hidden></figcaption>
                </figure>
                <button class="apartment-hero__lightbox-nav apartment-hero__lightbox-nav--next" type="button" data-apartment-lightbox-next aria-label="Следующий слайд">
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M7.5 4.5L12 9L7.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <aside class="apartment-hero__summary">
        <div class="apartment-hero__eyebrow">№ <?= htmlspecialcharsbx($commercial["number"]) ?></div>
        <h1 class="apartment-hero__title">
          <?php if ($commercial["title_line_1"] !== ""): ?>
          <span class="apartment-hero__title-line"><?= htmlspecialcharsbx($commercial["title_line_1"]) ?></span>
          <?php endif; ?>
          <?php if ($commercial["title_line_2"] !== ""): ?>
          <span class="apartment-hero__title-line"><?= htmlspecialcharsbx($commercial["title_line_2"]) ?></span>
          <?php endif; ?>
        </h1>

        <dl class="apartment-hero__facts">
          <?php foreach ($commercial["primary_facts"] as $fact): ?>
          <div class="apartment-hero__fact">
            <dt><?= htmlspecialcharsbx($fact["label"]) ?></dt>
            <dd>
              <?php if (!empty($fact["url"])): ?>
              <a href="<?= htmlspecialcharsbx($fact["url"]) ?>"><?= htmlspecialcharsbx($fact["value"]) ?></a>
              <?php else: ?>
              <?= htmlspecialcharsbx($fact["value"]) ?>
              <?php endif; ?>
            </dd>
          </div>
          <?php endforeach; ?>
        </dl>

        <?php if (!empty($commercial["feature_tags"])): ?>
        <div class="apartment-hero__traits">
          <div class="apartment-hero__traits-title">Особенности</div>
          <div class="apartment-hero__traits-list">
            <?php foreach ($commercial["feature_tags"] as $featureTag): ?>
            <span class="apartment-hero__trait"><?= htmlspecialcharsbx($featureTag) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($commercial["description"] !== ""): ?>
        <div class="apartment-hero__traits">
          <div class="apartment-hero__traits-title">Описание</div>
          <div class="apartment-hero__traits-text"><?= nl2br(htmlspecialcharsbx($commercial["description"])) ?></div>
        </div>
        <?php endif; ?>

        <div class="apartment-hero__price-card">
          <?php if (!empty($commercial["price_old"])): ?>
          <div class="apartment-hero__price-old"><?= htmlspecialcharsbx($commercial["price_old"]) ?></div>
          <?php endif; ?>
          <div class="apartment-hero__price-current"><?= htmlspecialcharsbx($commercial["price_total"]) ?></div>
          <?php if (!empty($commercial["price_meter"])): ?>
          <div class="apartment-hero__price-meta">
            <span>Цена за м²</span>
            <strong><?= htmlspecialcharsbx($commercial["price_meter"]) ?></strong>
          </div>
          <?php endif; ?>
        </div>

        <button
          class="btn btn--primary apartment-hero__cta<?= $isSoldCommercial ? " apartment-hero__cta--sold" : "" ?>"
          type="button"
          <?php if (!$isSoldCommercial): ?>
          data-contact-open="contact"
          data-contact-title="Забронировать помещение"
          data-contact-type="booking"
          data-contact-source="commerce_detail"
          <?php else: ?>
          disabled
          aria-disabled="true"
          <?php endif; ?>
        >
          <?= $isSoldCommercial ? "ПРОДАНО" : "Забронировать" ?>
        </button>

        <?php if (!empty($commercial["detail_facts"])): ?>
        <div class="apartment-hero__params">
          <button class="apartment-hero__params-toggle" type="button" data-apartment-params-toggle aria-expanded="true">
            Все параметры помещения
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </button>

          <div class="apartment-hero__params-body" data-apartment-params>
            <dl class="apartment-hero__params-list">
              <?php foreach ($commercial["detail_facts"] as $param): ?>
              <div class="apartment-hero__fact">
                <dt><?= htmlspecialcharsbx($param["label"]) ?></dt>
                <dd><?= htmlspecialcharsbx($param["value"]) ?></dd>
              </div>
              <?php endforeach; ?>
            </dl>
          </div>
        </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>

<?php
global $arrProjectAdvantagesFilter;
$arrProjectAdvantagesFilter = array();
if ($projectId > 0) {
    $arrProjectAdvantagesFilter["PROPERTY_PROJECT"] = $projectId;
}

global $arrProjectPromotionsFilter;
$arrProjectPromotionsFilter = array();
if ($projectCode !== "") {
    $arrProjectPromotionsFilter["PROPERTY_ZHK_CODE"] = $projectCode;
}
?>

<?php if (!empty($projectExtraItems)): ?>
<section class="extra" id="commerce-extra">
  <div class="container">
    <h2 class="section-title">Кроме квартир</h2>
    <div class="extra__cards">
      <?php foreach ($projectExtraItems as $extraItem): ?>
        <?php if ($extraItem["url"] !== ""): ?>
          <a class="extra-card" href="<?= htmlspecialcharsbx($extraItem["url"]) ?>">
            <img src="<?= htmlspecialcharsbx($extraItem["image"]) ?>" alt="<?= htmlspecialcharsbx($extraItem["title"]) ?>" />
            <h3 class="extra-card__title"><?= htmlspecialcharsbx($extraItem["title"]) ?></h3>
            <div class="extra-card__overlay">
              <div class="extra-card__link">
                <img src="<?= SITE_TEMPLATE_PATH ?>/img/figma-c9a51b74-4033-4a0d-a682-d597c518fcf6.svg" alt="" />
              </div>
            </div>
          </a>
        <?php else: ?>
          <article class="extra-card">
            <img src="<?= htmlspecialcharsbx($extraItem["image"]) ?>" alt="<?= htmlspecialcharsbx($extraItem["title"]) ?>" />
            <h3 class="extra-card__title"><?= htmlspecialcharsbx($extraItem["title"]) ?></h3>
            <div class="extra-card__overlay">
              <div class="extra-card__link">
                <img src="<?= SITE_TEMPLATE_PATH ?>/img/figma-c9a51b74-4033-4a0d-a682-d597c518fcf6.svg" alt="" />
              </div>
            </div>
          </article>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="projects-benefits" aria-label="Преимущества проекта">
  <div class="container">
    <h2 class="projects-benefits__title">Преимущества</h2>
    <?php if ($projectAdvantagesIblockId > 0 && $projectId > 0): ?>
      <?php
      $APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "project_advantages",
        array(
          "IBLOCK_TYPE" => $projectAdvantagesIblockType !== "" ? $projectAdvantagesIblockType : "",
          "IBLOCK_ID" => $projectAdvantagesIblockId,
          "NEWS_COUNT" => "200",
          "SORT_BY1" => "SORT",
          "SORT_ORDER1" => "ASC",
          "SORT_BY2" => "ID",
          "SORT_ORDER2" => "ASC",
          "FILTER_NAME" => "arrProjectAdvantagesFilter",
          "FIELD_CODE" => array("NAME", "PREVIEW_TEXT", "PREVIEW_PICTURE", ""),
          "PROPERTY_CODE" => array("PROJECT", "LABEL", "CATEGORY", ""),
          "CHECK_DATES" => "N",
          "ACTIVE_DATE_FORMAT" => "d.m.Y",
          "CACHE_TYPE" => "A",
          "CACHE_TIME" => "36000000",
          "CACHE_FILTER" => "Y",
          "CACHE_GROUPS" => "Y",
          "SET_TITLE" => "N",
          "SET_BROWSER_TITLE" => "N",
          "SET_META_KEYWORDS" => "N",
          "SET_META_DESCRIPTION" => "N",
          "SET_LAST_MODIFIED" => "N",
          "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
          "ADD_SECTIONS_CHAIN" => "N",
          "HIDE_LINK_WHEN_NO_DETAIL" => "N",
          "DISPLAY_DATE" => "N",
          "DISPLAY_NAME" => "Y",
          "DISPLAY_PICTURE" => "Y",
          "DISPLAY_PREVIEW_TEXT" => "Y",
          "PARENT_SECTION" => "",
          "PARENT_SECTION_CODE" => "",
          "STRICT_SECTION_CHECK" => "N",
          "DISPLAY_TOP_PAGER" => "N",
          "DISPLAY_BOTTOM_PAGER" => "N",
          "PAGER_SHOW_ALWAYS" => "N",
          "PAGER_TEMPLATE" => "",
        ),
        false
      );
      ?>
    <?php else: ?>
      <div class="projects-benefits__body" data-benefits-body>
        <ul class="projects-benefits__list"></ul>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($homePromotionsIblockId > 0): ?>
  <?php
  $APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "home_promotions",
    array(
      "IBLOCK_TYPE" => $homePromotionsIblockType,
      "IBLOCK_ID" => $homePromotionsIblockId,
      "NEWS_COUNT" => "3",
      "SORT_BY1" => "ACTIVE_FROM",
      "SORT_ORDER1" => "DESC",
      "SORT_BY2" => "SORT",
      "SORT_ORDER2" => "ASC",
      "FILTER_NAME" => "arrProjectPromotionsFilter",
      "FIELD_CODE" => array("NAME", "PREVIEW_PICTURE", "DATE_ACTIVE_TO", ""),
      "PROPERTY_CODE" => array("ZHK_CODE", "ZHK_LABEL", ""),
      "CHECK_DATES" => "Y",
      "DETAIL_URL" => "/promotions/#ELEMENT_CODE#/",
      "ACTIVE_DATE_FORMAT" => "d.m.Y",
      "CACHE_TYPE" => "A",
      "CACHE_TIME" => "36000000",
      "CACHE_FILTER" => "Y",
      "CACHE_GROUPS" => "Y",
      "SET_TITLE" => "N",
      "SET_BROWSER_TITLE" => "N",
      "SET_META_KEYWORDS" => "N",
      "SET_META_DESCRIPTION" => "N",
      "SET_LAST_MODIFIED" => "N",
      "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
      "ADD_SECTIONS_CHAIN" => "N",
      "HIDE_LINK_WHEN_NO_DETAIL" => "N",
      "DISPLAY_DATE" => "N",
      "DISPLAY_NAME" => "Y",
      "DISPLAY_PICTURE" => "Y",
      "DISPLAY_PREVIEW_TEXT" => "N",
      "PARENT_SECTION" => "",
      "PARENT_SECTION_CODE" => "",
      "STRICT_SECTION_CHECK" => "N",
      "DISPLAY_TOP_PAGER" => "N",
      "DISPLAY_BOTTOM_PAGER" => "N",
      "PAGER_SHOW_ALWAYS" => "N",
      "PAGER_TEMPLATE" => "",
    ),
    false
  );
  ?>
<?php else: ?>
  <section class="promo" id="promo">
    <div class="container">
      <h2 class="section-title">Акции</h2>
    </div>
  </section>
<?php endif; ?>

<?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/purchase.php"; ?>
<?php if (!empty($similarCommercials)): ?>
<section class="apartment-similar" aria-label="Похожие помещения" data-apartment-similar>
  <div class="container">
    <div class="apartment-similar__head">
      <h2 class="section-title">Похожие помещения</h2>
      <div class="apartment-similar__nav" aria-label="Навигация по похожим помещениям">
        <button class="apartment-similar__nav-btn apartment-similar__nav-btn--prev" type="button" data-apartment-similar-prev aria-label="Предыдущие помещения">
          <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M10.5 4.5L6 9L10.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
        <button class="apartment-similar__nav-btn apartment-similar__nav-btn--next" type="button" data-apartment-similar-next aria-label="Следующие помещения">
          <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M7.5 4.5L12 9L7.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
      </div>
    </div>

    <div class="apartment-similar__swiper swiper" data-apartment-similar-swiper>
      <div class="swiper-wrapper">
        <?php foreach ($similarCommercials as $apartmentCard): ?>
        <div class="swiper-slide">
          <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/apartment-card.php"; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
<?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/project-benefit-modal.php"; ?>
<?php endif; ?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>


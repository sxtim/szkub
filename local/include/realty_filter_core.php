<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!function_exists("szcubeRealtyFilterFindIblockByCode")) {
    function szcubeRealtyFilterFindIblockByCode($code)
    {
        $res = CIBlock::GetList(array(), array("=CODE" => (string)$code, "ACTIVE" => "Y"), false);
        return $res->Fetch() ?: null;
    }
}

if (!function_exists("szcubeRealtyFilterElementUrl")) {
    function szcubeRealtyFilterElementUrl($template, array $fields, $fallbackPrefix)
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

if (!function_exists("szcubeRealtyFilterFilePath")) {
    function szcubeRealtyFilterFilePath($value)
    {
        $fileId = (int)$value;
        if ($fileId <= 0) {
            return "";
        }

        $path = CFile::GetPath($fileId);
        return $path ? (string)$path : "";
    }
}

if (!function_exists("szcubeRealtyFilterNormalizeKey")) {
    function szcubeRealtyFilterNormalizeKey($value)
    {
        $value = trim((string)$value);
        if ($value === "") {
            return "";
        }

        $value = function_exists("mb_strtolower") ? mb_strtolower($value) : strtolower($value);
        $value = preg_replace("/[^a-z0-9а-яё_-]+/iu", "-", $value);
        $value = preg_replace("/-+/u", "-", (string)$value);
        return trim((string)$value, "-");
    }
}

if (!function_exists("szcubeRealtyFilterPropertySingleValue")) {
    function szcubeRealtyFilterPropertySingleValue(array $property)
    {
        if (isset($property["VALUE_ENUM"]) && trim((string)$property["VALUE_ENUM"]) !== "") {
            return trim((string)$property["VALUE_ENUM"]);
        }

        if (isset($property["VALUE"]) && !is_array($property["VALUE"])) {
            return trim((string)$property["VALUE"]);
        }

        return "";
    }
}

if (!function_exists("szcubeRealtyFilterPropertySingleKey")) {
    function szcubeRealtyFilterPropertySingleKey(array $property)
    {
        if (isset($property["VALUE_XML_ID"]) && trim((string)$property["VALUE_XML_ID"]) !== "") {
            return szcubeRealtyFilterNormalizeKey($property["VALUE_XML_ID"]);
        }

        return szcubeRealtyFilterNormalizeKey(szcubeRealtyFilterPropertySingleValue($property));
    }
}

if (!function_exists("szcubeRealtyFilterPropertyMultipleValues")) {
    function szcubeRealtyFilterPropertyMultipleValues(array $property)
    {
        $source = array();
        if (isset($property["VALUE_ENUM"]) && is_array($property["VALUE_ENUM"])) {
            $source = $property["VALUE_ENUM"];
        } elseif (isset($property["VALUE"]) && is_array($property["VALUE"])) {
            $source = $property["VALUE"];
        } elseif (isset($property["VALUE"]) && trim((string)$property["VALUE"]) !== "") {
            $source = array($property["VALUE"]);
        }

        $result = array();
        foreach ($source as $item) {
            $item = trim((string)$item);
            if ($item !== "") {
                $result[] = $item;
            }
        }

        return array_values(array_unique($result));
    }
}

if (!function_exists("szcubeRealtyFilterIsPubliclyHiddenStatus")) {
    function szcubeRealtyFilterIsPubliclyHiddenStatus($statusKey, $statusLabel = "", $hideBooked = false)
    {
        $statusKey = trim((string)$statusKey);
        $statusLabel = trim((string)$statusLabel);

        $statusKey = function_exists("mb_strtolower") ? mb_strtolower($statusKey) : strtolower($statusKey);
        $statusLabel = function_exists("mb_strtolower") ? mb_strtolower($statusLabel) : strtolower($statusLabel);

        if ($statusKey === "sold") {
            return true;
        }

        if ($statusLabel !== "" && preg_match("/^продан[а-я]*$/u", $statusLabel) === 1) {
            return true;
        }

        if (!$hideBooked) {
            return false;
        }

        if ($statusKey === "booked") {
            return true;
        }

        return $statusLabel !== "" && preg_match("/^забронир[а-я]*$/u", $statusLabel) === 1;
    }
}

if (!function_exists("szcubeRealtyFilterRangeUpdate")) {
    function szcubeRealtyFilterRangeUpdate(array &$range, $value, $allowZero = false)
    {
        $value = (float)$value;
        if (!is_finite($value)) {
            return;
        }

        if (!$allowZero && $value <= 0) {
            return;
        }

        if ($range["min"] === null || $value < $range["min"]) {
            $range["min"] = $value;
        }
        if ($range["max"] === null || $value > $range["max"]) {
            $range["max"] = $value;
        }
    }
}

if (!function_exists("szcubeRealtyFilterRangeFinalize")) {
    function szcubeRealtyFilterRangeFinalize(array $range, $fallbackMin, $fallbackMax, $expandIfCollapsed = false)
    {
        $actualMin = $range["min"] !== null ? (float)$range["min"] : (float)$fallbackMin;
        $actualMax = $range["max"] !== null ? (float)$range["max"] : (float)$fallbackMax;
        $step = isset($range["step"]) ? (float)$range["step"] : 1;
        $precision = isset($range["precision"]) ? (int)$range["precision"] : 0;

        if ($expandIfCollapsed && $actualMax <= $actualMin) {
            $actualMax = $actualMin + ($step > 0 ? $step : 1);
        } elseif ($actualMax < $actualMin) {
            $actualMax = $actualMin;
        }

        $actualMin = round($actualMin, $precision);
        $actualMax = round($actualMax, $precision);

        return array(
            "actual_min" => $actualMin,
            "actual_max" => $actualMax,
            "render_min" => $actualMin,
            "render_max" => $actualMax,
            "step" => $step,
            "precision" => $precision,
        );
    }
}

if (!function_exists("szcubeRealtyFilterOptionAppend")) {
    function szcubeRealtyFilterOptionAppend(array &$options, $key, $label)
    {
        $key = trim((string)$key);
        $label = trim((string)$label);
        if ($key === "" || $label === "") {
            return;
        }

        if (!isset($options[$key])) {
            $options[$key] = array(
                "key" => $key,
                "label" => $label,
                "count" => 0,
            );
        }

        $options[$key]["count"]++;
    }
}

if (!function_exists("szcubeRealtyFilterRequestState")) {
    function szcubeRealtyFilterRequestState(array $discreteKeys, array $rangeKeys)
    {
        $state = array();

        foreach ($discreteKeys as $stateKey => $requestKey) {
            if (is_int($stateKey)) {
                $stateKey = (string)$requestKey;
            }

            $requestKey = trim((string)$requestKey);
            if ($requestKey === "") {
                $requestKey = (string)$stateKey;
            }

            $state[$stateKey] = function_exists("szcubeRequestCsvList") ? szcubeRequestCsvList($requestKey) : array();
        }

        foreach ($rangeKeys as $stateKey => $requestKey) {
            if (is_int($stateKey)) {
                $stateKey = (string)$requestKey;
            }

            $requestKey = trim((string)$requestKey);
            if ($requestKey === "") {
                $requestKey = (string)$stateKey;
            }

            $state[$stateKey] = function_exists("szcubeRequestNumberValue") ? szcubeRequestNumberValue($requestKey) : null;
        }

        return $state;
    }
}

if (!function_exists("szcubeRealtyFilterHasRequestCriteria")) {
    function szcubeRealtyFilterHasRequestCriteria(array $state, array $discreteKeys, array $rangeKeys)
    {
        foreach ($discreteKeys as $key) {
            if (!empty($state[$key]) && is_array($state[$key])) {
                return true;
            }
        }

        foreach ($rangeKeys as $key) {
            if (array_key_exists($key, $state) && $state[$key] !== null) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists("szcubeRealtyFilterMatchesRequestState")) {
    function szcubeRealtyFilterMatchesRequestState(array $item, array $state, array $definition)
    {
        if (isset($definition["discrete"]) && is_array($definition["discrete"])) {
            foreach ($definition["discrete"] as $stateKey => $config) {
                $selected = isset($state[$stateKey]) && is_array($state[$stateKey]) ? $state[$stateKey] : array();
                if (empty($selected)) {
                    continue;
                }

                $field = isset($config["field"]) ? (string)$config["field"] : "";
                if ($field === "") {
                    continue;
                }

                $mode = isset($config["mode"]) ? (string)$config["mode"] : "equals";
                $value = isset($item[$field]) ? $item[$field] : null;

                if ($mode === "intersect") {
                    $values = is_array($value) ? $value : array();
                    if (!array_intersect($selected, $values)) {
                        return false;
                    }
                    continue;
                }

                if (!in_array((string)$value, $selected, true)) {
                    return false;
                }
            }
        }

        if (isset($definition["ranges"]) && is_array($definition["ranges"])) {
            foreach ($definition["ranges"] as $config) {
                $fromKey = isset($config["from"]) ? (string)$config["from"] : "";
                $toKey = isset($config["to"]) ? (string)$config["to"] : "";
                $field = isset($config["field"]) ? (string)$config["field"] : "";
                $mode = isset($config["mode"]) ? (string)$config["mode"] : "between";

                $fromValue = $fromKey !== "" && array_key_exists($fromKey, $state) ? $state[$fromKey] : null;
                $toValue = $toKey !== "" && array_key_exists($toKey, $state) ? $state[$toKey] : null;

                if ($mode === "overlap") {
                    $fieldFrom = isset($config["field_from"]) ? (string)$config["field_from"] : "";
                    $fieldTo = isset($config["field_to"]) ? (string)$config["field_to"] : "";
                    $valueFrom = isset($item[$fieldFrom]) ? (float)$item[$fieldFrom] : 0.0;
                    $valueTo = isset($item[$fieldTo]) ? (float)$item[$fieldTo] : $valueFrom;

                    if ($valueFrom > 0) {
                        if ($fromValue !== null && $valueTo + 0.0001 < (float)$fromValue) {
                            return false;
                        }
                        if ($toValue !== null && $valueFrom - 0.0001 > (float)$toValue) {
                            return false;
                        }
                    }
                    continue;
                }

                if ($field === "") {
                    continue;
                }

                $value = isset($item[$field]) ? (float)$item[$field] : 0.0;
                if ($value <= 0) {
                    continue;
                }

                if ($fromValue !== null && $value + 0.0001 < (float)$fromValue) {
                    return false;
                }
                if ($toValue !== null && $value - 0.0001 > (float)$toValue) {
                    return false;
                }
            }
        }

        return true;
    }
}

if (!function_exists("szcubeRealtyFilterSortItems")) {
    function szcubeRealtyFilterSortItems(array $items, $sortValue, array $sortMap, callable $defaultSort = null)
    {
        $sortValue = trim((string)$sortValue);
        if ($sortValue === "" || $sortValue === "default") {
            if ($defaultSort !== null) {
                $items = $defaultSort($items);
            }

            return array_values($items);
        }

        if (!isset($sortMap[$sortValue]) || !is_array($sortMap[$sortValue])) {
            return array_values($items);
        }

        $map = $sortMap[$sortValue];
        $field = isset($map["field"]) ? (string)$map["field"] : "";
        $direction = isset($map["direction"]) ? (string)$map["direction"] : "asc";
        $tieField = isset($map["tie_field"]) ? (string)$map["tie_field"] : "id";

        usort($items, static function ($left, $right) use ($field, $direction, $tieField) {
            $leftValue = $field !== "" && isset($left[$field]) ? (float)$left[$field] : 0.0;
            $rightValue = $field !== "" && isset($right[$field]) ? (float)$right[$field] : 0.0;

            if (abs($leftValue - $rightValue) >= 0.0001) {
                if ($direction === "desc") {
                    return $leftValue < $rightValue ? 1 : -1;
                }

                return $leftValue > $rightValue ? 1 : -1;
            }

            $leftTie = isset($left[$tieField]) ? (float)$left[$tieField] : 0.0;
            $rightTie = isset($right[$tieField]) ? (float)$right[$tieField] : 0.0;
            if (abs($leftTie - $rightTie) < 0.0001) {
                return 0;
            }

            return $leftTie > $rightTie ? 1 : -1;
        });

        return array_values($items);
    }
}

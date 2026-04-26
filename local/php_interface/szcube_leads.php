<?php

if (!function_exists("szcubeLeadGetFormSid")) {
    function szcubeLeadGetFormSid()
    {
        $sid = defined("SZCUBE_CONTACT_WEB_FORM_SID") ? (string)SZCUBE_CONTACT_WEB_FORM_SID : "SZCUBE_LEADS";
        $sid = trim($sid);

        return $sid !== "" ? $sid : "SZCUBE_LEADS";
    }
}

if (!function_exists("szcubeLeadNormalizeCode")) {
    function szcubeLeadNormalizeCode($value, $default = "")
    {
        $value = trim(mb_strtolower((string)$value));
        $value = preg_replace("/[^a-z0-9_-]+/", "", $value);

        if (!is_string($value) || $value === "") {
            return (string)$default;
        }

        return mb_substr($value, 0, 64);
    }
}

if (!function_exists("szcubeLeadGetTypeMap")) {
    function szcubeLeadGetTypeMap()
    {
        static $map = null;

        if ($map !== null) {
            return $map;
        }

        $map = array(
            "callback" => array(
                "title" => "Обратный звонок",
                "description" => "Общие обращения с сайта",
                "scope" => "callback",
            ),
            "project_detail" => array(
                "title" => "Консультация по проекту",
                "description" => "Форма на странице ЖК",
                "scope" => "callback",
            ),
            "about_company_sale" => array(
                "title" => "Узнать о проектах в продаже",
                "description" => "Форма на странице о компании",
                "scope" => "callback",
            ),
            "apartments_catalog" => array(
                "title" => "Подобрать квартиру",
                "description" => "Форма в каталоге квартир",
                "scope" => "callback",
            ),
            "consulting" => array(
                "title" => "Строительный консалтинг",
                "description" => "Заявка на консультацию",
                "scope" => "consulting",
            ),
            "tenders" => array(
                "title" => "Тендеры",
                "description" => "Заявка от подрядчика или поставщика",
                "scope" => "tenders",
            ),
            "apartment_reserve" => array(
                "title" => "Бронирование квартиры",
                "description" => "Заявка по квартире",
                "scope" => "apartments",
            ),
            "storeroom_reserve" => array(
                "title" => "Бронирование кладовки",
                "description" => "Заявка по кладовке",
                "scope" => "storerooms",
            ),
            "parking_reserve" => array(
                "title" => "Бронирование паркинга",
                "description" => "Заявка по парковочному месту",
                "scope" => "parking",
            ),
            "commerce_reserve" => array(
                "title" => "Бронирование коммерции",
                "description" => "Заявка по коммерческому помещению",
                "scope" => "commerce",
            ),
        );

        return $map;
    }
}

if (!function_exists("szcubeLeadGetScopeMap")) {
    function szcubeLeadGetScopeMap()
    {
        static $scopes = null;

        if ($scopes !== null) {
            return $scopes;
        }

        $scopes = array(
            "all" => array(
                "title" => "Все заявки",
                "description" => "Все лиды из формы " . szcubeLeadGetFormSid(),
                "lead_types" => array(),
            ),
            "callback" => array(
                "title" => "Общие обращения",
                "description" => "Обратный звонок и консультации по проектам",
                "lead_types" => array("callback", "project_detail", "about_company_sale", "apartments_catalog"),
            ),
            "apartments" => array(
                "title" => "Квартиры",
                "description" => "Заявки по квартирам",
                "lead_types" => array("apartment_reserve"),
            ),
            "storerooms" => array(
                "title" => "Кладовки",
                "description" => "Заявки по кладовкам",
                "lead_types" => array("storeroom_reserve"),
            ),
            "parking" => array(
                "title" => "Паркинги",
                "description" => "Заявки по парковочным местам",
                "lead_types" => array("parking_reserve"),
            ),
            "commerce" => array(
                "title" => "Коммерция",
                "description" => "Заявки по коммерческим помещениям",
                "lead_types" => array("commerce_reserve"),
            ),
            "consulting" => array(
                "title" => "Консалтинг",
                "description" => "Заявки на строительный консалтинг",
                "lead_types" => array("consulting"),
            ),
            "tenders" => array(
                "title" => "Тендеры",
                "description" => "Обращения от подрядчиков и поставщиков",
                "lead_types" => array("tenders"),
            ),
        );

        return $scopes;
    }
}

if (!function_exists("szcubeLeadGetTypeMeta")) {
    function szcubeLeadGetTypeMeta($leadType)
    {
        $leadType = szcubeLeadNormalizeType($leadType);
        $types = szcubeLeadGetTypeMap();

        if (isset($types[$leadType])) {
            return $types[$leadType];
        }

        return array(
            "title" => $leadType !== "" ? $leadType : "Заявка",
            "description" => "Неизвестный тип заявки",
            "scope" => "all",
        );
    }
}

if (!function_exists("szcubeLeadNormalizeType")) {
    function szcubeLeadNormalizeType($leadType, $leadSource = "")
    {
        $leadType = szcubeLeadNormalizeCode($leadType);
        $leadSource = szcubeLeadNormalizeCode($leadSource);

        if ($leadType === "booking") {
            if ($leadSource === "apartment_detail") {
                return "apartment_reserve";
            }

            if ($leadSource === "commerce_detail") {
                return "commerce_reserve";
            }
        }

        return $leadType !== "" ? $leadType : "callback";
    }
}

if (!function_exists("szcubeLeadNormalizeSource")) {
    function szcubeLeadNormalizeSource($leadSource)
    {
        return szcubeLeadNormalizeCode($leadSource, "unknown");
    }
}

if (!function_exists("szcubeLeadResolveScope")) {
    function szcubeLeadResolveScope($leadType)
    {
        $leadType = szcubeLeadNormalizeType($leadType);
        $scopes = szcubeLeadGetScopeMap();

        foreach ($scopes as $scopeCode => $scopeConfig) {
            if ($scopeCode === "all") {
                continue;
            }

            if (in_array($leadType, $scopeConfig["lead_types"], true)) {
                return $scopeCode;
            }
        }

        return "all";
    }
}

if (!function_exists("szcubeLeadFindFormIdBySid")) {
    function szcubeLeadFindFormIdBySid($formSid)
    {
        $formSid = trim((string)$formSid);
        if ($formSid === "") {
            return 0;
        }

        $res = CForm::GetBySID($formSid);
        if ($row = $res->Fetch()) {
            return (int)$row["ID"];
        }

        return 0;
    }
}

if (!function_exists("szcubeLeadExtractAnswerValue")) {
    function szcubeLeadExtractAnswerValue(array $answers, $sid)
    {
        if (!isset($answers[$sid]) || !is_array($answers[$sid])) {
            return "";
        }

        foreach ($answers[$sid] as $answer) {
            if (!is_array($answer)) {
                continue;
            }

            $valueCandidates = array(
                isset($answer["USER_TEXT"]) ? trim((string)$answer["USER_TEXT"]) : "",
                isset($answer["ANSWER_TEXT"]) ? trim((string)$answer["ANSWER_TEXT"]) : "",
                isset($answer["ANSWER_VALUE"]) ? trim((string)$answer["ANSWER_VALUE"]) : "",
                isset($answer["VALUE"]) ? trim((string)$answer["VALUE"]) : "",
            );

            foreach ($valueCandidates as $value) {
                if ($value !== "") {
                    return $value;
                }
            }
        }

        return "";
    }
}

if (!function_exists("szcubeLeadIsTargetFormId")) {
    function szcubeLeadIsTargetFormId($formId)
    {
        $formId = (int)$formId;
        if ($formId <= 0) {
            return false;
        }

        static $cache = array();
        if (array_key_exists($formId, $cache)) {
            return $cache[$formId];
        }

        $cache[$formId] = false;
        $res = CForm::GetByID($formId);
        $row = $res ? $res->Fetch() : false;
        if (is_array($row) && trim((string)$row["SID"]) === szcubeLeadGetFormSid()) {
            $cache[$formId] = true;
        }

        return $cache[$formId];
    }
}

if (!function_exists("szcubeLeadBuildResultData")) {
    function szcubeLeadBuildResultData($resultId, $formId = 0)
    {
        $resultId = (int)$resultId;
        if ($resultId <= 0) {
            return null;
        }

        $resultRes = CFormResult::GetByID($resultId);
        $resultRow = $resultRes ? $resultRes->Fetch() : false;
        if (!is_array($resultRow)) {
            return null;
        }

        $formId = $formId > 0 ? (int)$formId : (int)$resultRow["FORM_ID"];
        if ($formId <= 0 || !szcubeLeadIsTargetFormId($formId)) {
            return null;
        }

        $result = array();
        $answers = array();
        CFormResult::GetDataByID(
            $resultId,
            array("NAME", "PHONE", "LEAD_TYPE", "LEAD_SOURCE", "LEAD_NOTE", "PAGE_URL", "CONSENT"),
            $result,
            $answers
        );

        $leadTypeRaw = szcubeLeadExtractAnswerValue($answers, "LEAD_TYPE");
        $leadSourceRaw = szcubeLeadExtractAnswerValue($answers, "LEAD_SOURCE");
        $leadType = szcubeLeadNormalizeType($leadTypeRaw, $leadSourceRaw);
        $leadSource = szcubeLeadNormalizeSource($leadSourceRaw);

        return array(
            "ID" => $resultId,
            "FORM_ID" => $formId,
            "FORM_SID" => szcubeLeadGetFormSid(),
            "TIMESTAMP_X" => isset($resultRow["TIMESTAMP_X"]) ? (string)$resultRow["TIMESTAMP_X"] : "",
            "STATUS_TITLE" => isset($resultRow["STATUS_TITLE"]) ? (string)$resultRow["STATUS_TITLE"] : "",
            "SENT_TO_CRM" => isset($resultRow["SENT_TO_CRM"]) ? (string)$resultRow["SENT_TO_CRM"] : "N",
            "NAME" => szcubeLeadExtractAnswerValue($answers, "NAME"),
            "PHONE" => szcubeLeadExtractAnswerValue($answers, "PHONE"),
            "LEAD_TYPE" => $leadType,
            "LEAD_TYPE_RAW" => $leadTypeRaw,
            "LEAD_SOURCE" => $leadSource,
            "LEAD_SOURCE_RAW" => $leadSourceRaw,
            "LEAD_NOTE" => szcubeLeadExtractAnswerValue($answers, "LEAD_NOTE"),
            "PAGE_URL" => szcubeLeadExtractAnswerValue($answers, "PAGE_URL"),
            "CONSENT" => szcubeLeadExtractAnswerValue($answers, "CONSENT"),
            "SCOPE" => szcubeLeadResolveScope($leadType),
            "TYPE_META" => szcubeLeadGetTypeMeta($leadType),
        );
    }
}

if (!function_exists("szcubeLeadGetBitrix24WebhookUrl")) {
    function szcubeLeadGetBitrix24WebhookUrl()
    {
        $webhookUrl = defined("SZCUBE_B24_WEBHOOK_URL") ? trim((string)SZCUBE_B24_WEBHOOK_URL) : "";
        if ($webhookUrl === "") {
            return "";
        }

        if (preg_match("~crm\\.item\\.add(?:\\.json)?/?$~i", $webhookUrl)) {
            return $webhookUrl;
        }

        return rtrim($webhookUrl, "/") . "/crm.item.add";
    }
}

if (!function_exists("szcubeLeadBuildBitrix24Request")) {
    function szcubeLeadBuildBitrix24Request(array $lead)
    {
        $typeMeta = isset($lead["TYPE_META"]) && is_array($lead["TYPE_META"])
            ? $lead["TYPE_META"]
            : szcubeLeadGetTypeMeta(isset($lead["LEAD_TYPE"]) ? $lead["LEAD_TYPE"] : "");

        $leadName = trim((string)($lead["NAME"] ?? ""));
        $leadPhone = trim((string)($lead["PHONE"] ?? ""));
        $leadNote = trim((string)($lead["LEAD_NOTE"] ?? ""));
        $pageUrl = trim((string)($lead["PAGE_URL"] ?? ""));
        $leadSource = trim((string)($lead["LEAD_SOURCE"] ?? ""));
        $resultId = (int)($lead["ID"] ?? 0);

        $title = $typeMeta["title"];
        if ($leadName !== "") {
            $title .= ": " . $leadName;
        }

        $comments = array();
        if ($leadNote !== "") {
            $comments[] = $leadNote;
        }
        if ($pageUrl !== "") {
            $comments[] = "Страница: " . $pageUrl;
        }
        if ($leadSource !== "") {
            $comments[] = "Источник: " . $leadSource;
        }
        if ($resultId > 0) {
            $comments[] = "ID результата веб-формы: " . $resultId;
        }

        $fields = array(
            "title" => $title,
            "name" => $leadName,
            "sourceId" => defined("SZCUBE_B24_SOURCE_ID") ? trim((string)SZCUBE_B24_SOURCE_ID) : "WEB",
            "sourceDescription" => "SZCUBE / " . $typeMeta["title"],
            "comments" => implode("\n", $comments),
            "originatorId" => "szcube",
            "originId" => $resultId > 0 ? "webform-result-" . $resultId : "",
        );

        if ($leadPhone !== "") {
            $fields["fm"] = array(
                array(
                    "typeId" => "PHONE",
                    "valueType" => "WORK",
                    "value" => $leadPhone,
                ),
            );
        }

        $assignedById = defined("SZCUBE_B24_ASSIGNED_BY_ID") ? (int)SZCUBE_B24_ASSIGNED_BY_ID : 0;
        if ($assignedById > 0) {
            $fields["assignedById"] = $assignedById;
        }

        return array(
            "entityTypeId" => defined("SZCUBE_B24_ENTITY_TYPE_ID") ? (int)SZCUBE_B24_ENTITY_TYPE_ID : 1,
            "fields" => $fields,
        );
    }
}

if (!function_exists("szcubeLeadSendToBitrix24")) {
    function szcubeLeadSendToBitrix24(array $lead)
    {
        $webhookUrl = szcubeLeadGetBitrix24WebhookUrl();
        if ($webhookUrl === "") {
            return array(
                "success" => false,
                "skipped" => true,
                "reason" => "webhook_not_configured",
            );
        }

        if (!class_exists("\\Bitrix\\Main\\Web\\HttpClient")) {
            return array(
                "success" => false,
                "skipped" => true,
                "reason" => "http_client_unavailable",
            );
        }

        $requestBody = szcubeLeadBuildBitrix24Request($lead);
        $client = new \Bitrix\Main\Web\HttpClient(array(
            "socketTimeout" => 10,
            "streamTimeout" => 10,
        ));
        $client->setHeader("Content-Type", "application/json", true);
        $client->setHeader("Accept", "application/json", true);

        $responseRaw = $client->post($webhookUrl, json_encode($requestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $statusCode = (int)$client->getStatus();
        $response = is_string($responseRaw) && $responseRaw !== "" ? json_decode($responseRaw, true) : null;

        if ($statusCode >= 200 && $statusCode < 300 && is_array($response) && !isset($response["error"])) {
            return array(
                "success" => true,
                "status_code" => $statusCode,
                "response" => $response,
            );
        }

        return array(
            "success" => false,
            "status_code" => $statusCode,
            "response" => is_array($response) ? $response : null,
            "body" => is_string($responseRaw) ? $responseRaw : "",
        );
    }
}

if (!function_exists("szcubeLeadHasNativeCrmLink")) {
    function szcubeLeadHasNativeCrmLink($formId)
    {
        global $DB;

        $formId = (int)$formId;
        if ($formId <= 0 || !is_object($DB)) {
            return false;
        }

        $sql = "
            SELECT L.ID
            FROM b_form_crm_link L
                INNER JOIN b_form_crm C ON C.ID = L.CRM_ID
            WHERE L.FORM_ID = " . $formId . "
                AND C.ACTIVE = 'Y'
            LIMIT 1
        ";

        $res = $DB->Query($sql, true);
        if (!$res) {
            return false;
        }

        return is_array($res->Fetch());
    }
}

if (!function_exists("szcubeLeadIsNativeCrmSent")) {
    function szcubeLeadIsNativeCrmSent($resultId)
    {
        $resultId = (int)$resultId;
        if ($resultId <= 0 || !class_exists("CFormResult")) {
            return false;
        }

        $res = CFormResult::GetByID($resultId);
        $row = $res ? $res->Fetch() : false;

        return is_array($row) && isset($row["SENT_TO_CRM"]) && (string)$row["SENT_TO_CRM"] === "Y";
    }
}

if (!function_exists("szcubeLeadSendToNativeCrm")) {
    function szcubeLeadSendToNativeCrm($formId, $resultId)
    {
        $formId = (int)$formId;
        $resultId = (int)$resultId;

        if ($formId <= 0 || $resultId <= 0 || !szcubeLeadIsTargetFormId($formId)) {
            return array(
                "success" => false,
                "skipped" => true,
                "reason" => "not_target_form",
            );
        }

        if (!szcubeLeadHasNativeCrmLink($formId)) {
            return array(
                "success" => false,
                "skipped" => true,
                "reason" => "native_crm_not_configured",
            );
        }

        if (szcubeLeadIsNativeCrmSent($resultId)) {
            return array(
                "success" => true,
                "skipped" => true,
                "reason" => "already_sent",
            );
        }

        if (class_exists("CFormCRM") && method_exists("CFormCRM", "onResultAdded")) {
            CFormCRM::onResultAdded($formId, $resultId);
        } elseif (class_exists("CFormCrm") && method_exists("CFormCrm", "onResultAdded")) {
            CFormCrm::onResultAdded($formId, $resultId);
        } elseif (class_exists("CFormCrm") && method_exists("CFormCrm", "AddLead")) {
            CFormCrm::AddLead($formId, $resultId);
        } elseif (class_exists("CFormCRM") && method_exists("CFormCRM", "AddLead")) {
            CFormCRM::AddLead($formId, $resultId);
        } else {
            return array(
                "success" => false,
                "skipped" => true,
                "reason" => "native_crm_class_unavailable",
            );
        }

        return array(
            "success" => szcubeLeadIsNativeCrmSent($resultId),
            "skipped" => false,
        );
    }
}

if (!function_exists("szcubeHandleLeadResultCreated")) {
    function szcubeHandleLeadResultCreated($webFormId, $resultId)
    {
        $webFormId = (int)$webFormId;
        $resultId = (int)$resultId;

        if ($webFormId <= 0 || $resultId <= 0 || !szcubeLeadIsTargetFormId($webFormId)) {
            return;
        }

        $lead = szcubeLeadBuildResultData($resultId, $webFormId);
        if (!is_array($lead)) {
            return;
        }

        $syncResult = szcubeLeadSendToBitrix24($lead);
        if (!empty($syncResult["success"]) || !empty($syncResult["skipped"])) {
            return;
        }

        if (function_exists("AddMessage2Log")) {
            AddMessage2Log(
                "Bitrix24 sync failed for lead RESULT_ID=" . $resultId . ": " . print_r($syncResult, true),
                "szcube_leads"
            );
        }
    }
}

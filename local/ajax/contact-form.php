<?php
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("PUBLIC_AJAX_MODE", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

header("Content-Type: application/json; charset=UTF-8");

/**
 * Каркас интеграции:
 * - пока модуль form не установлен/не настроен, заявки пишутся в stub-лог;
 * - после установки модуля form на проде заменяем блок szcubeContactSubmit()
 *   на сохранение в веб-форму (или дополняем его).
 */

function szcubeContactJsonResponse(array $payload, int $statusCode = 200): void
{
    if (function_exists("http_response_code")) {
        http_response_code($statusCode);
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    die();
}

function szcubeContactRequireSessid(): bool
{
    if (defined("SZCUBE_CONTACT_REQUIRE_SESSID")) {
        return (bool) SZCUBE_CONTACT_REQUIRE_SESSID;
    }

    // Для публичной формы лидов по умолчанию не блокируем отправку по истекшей сессии.
    return false;
}

function szcubeContactTrim(string $value): string
{
    return trim(preg_replace("/\s+/u", " ", $value));
}

function szcubeContactSanitizeSlug(string $value, string $default): string
{
    $value = trim(mb_strtolower($value));
    $value = preg_replace("/[^a-z0-9_-]+/", "", $value);

    if ($value === null || $value === "") {
        return $default;
    }

    return mb_substr($value, 0, 64);
}

function szcubeContactNormalizePhone(string $value): string
{
    $digits = preg_replace("/\D+/", "", $value);
    if ($digits === null) {
        return "";
    }

    if (strlen($digits) === 10) {
        $digits = "7" . $digits;
    } elseif (strlen($digits) === 11 && $digits[0] === "8") {
        $digits = "7" . substr($digits, 1);
    }

    if (strlen($digits) !== 11 || $digits[0] !== "7") {
        return "";
    }

    return "+" . $digits;
}

function szcubeContactStoreStub(array $payload): array
{
    $uploadDir = rtrim($_SERVER["DOCUMENT_ROOT"], "/") . "/upload";
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        return array(
            "success" => false,
            "message" => "Не удалось создать каталог для stub-лога.",
        );
    }

    $filePath = $uploadDir . "/szcube-contact-form-stub.log";
    $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    $writeResult = @file_put_contents($filePath, $line, FILE_APPEND | LOCK_EX);

    if ($writeResult === false) {
        return array(
            "success" => false,
            "message" => "Не удалось сохранить заявку в stub-лог.",
        );
    }

    return array(
        "success" => true,
        "mode" => "stub",
    );
}

function szcubeContactGetWebFormSid(): string
{
    $sid = defined("SZCUBE_CONTACT_WEB_FORM_SID") ? (string) SZCUBE_CONTACT_WEB_FORM_SID : "SZCUBE_LEADS";
    $sid = trim($sid);

    return $sid !== "" ? $sid : "SZCUBE_LEADS";
}

function szcubeContactGetWebForm(): ?array
{
    $webFormId = defined("SZCUBE_CONTACT_WEB_FORM_ID") ? (int) SZCUBE_CONTACT_WEB_FORM_ID : 0;

    if ($webFormId > 0) {
        $rsForm = CForm::GetByID($webFormId);
        $form = $rsForm ? $rsForm->Fetch() : false;
        return is_array($form) ? $form : null;
    }

    $rsForm = CForm::GetBySID(szcubeContactGetWebFormSid());
    $form = $rsForm ? $rsForm->Fetch() : false;

    return is_array($form) ? $form : null;
}

function szcubeContactResolveQuestionInputKey(int $formId, string $questionSid): array
{
    static $cache = array();

    $cacheKey = $formId . "|" . $questionSid;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $rsField = CFormField::GetBySID($questionSid, $formId);
    $field = $rsField ? $rsField->Fetch() : false;
    if (!is_array($field)) {
        throw new \RuntimeException("Question not found: " . $questionSid);
    }

    $fieldId = (int) $field["ID"];
    $rsAnswer = CFormAnswer::GetList($fieldId, "s_sort", "asc");
    $answer = $rsAnswer ? $rsAnswer->Fetch() : false;
    if (!is_array($answer)) {
        throw new \RuntimeException("Answer not found for question: " . $questionSid);
    }

    $answerId = (int) $answer["ID"];
    $fieldType = isset($answer["FIELD_TYPE"]) ? (string) $answer["FIELD_TYPE"] : "text";

    $cache[$cacheKey] = array(
        "FIELD_ID" => $fieldId,
        "ANSWER_ID" => $answerId,
        "FIELD_TYPE" => $fieldType,
        "INPUT_KEY" => "form_" . $fieldType . "_" . $answerId,
    );

    return $cache[$cacheKey];
}

function szcubeContactBuildBitrixFormValues(array $webForm, array $payload): array
{
    $formId = (int) $webForm["ID"];
    $formSid = isset($webForm["SID"]) ? (string) $webForm["SID"] : "";

    $map = array(
        "NAME" => $payload["name"],
        "PHONE" => $payload["phone"],
        "LEAD_TYPE" => $payload["lead_type"],
        "LEAD_SOURCE" => $payload["lead_source"],
        "PAGE_URL" => $payload["page_url"],
        "CONSENT" => "Y",
    );

    $values = array();
    foreach ($map as $questionSid => $value) {
        $question = szcubeContactResolveQuestionInputKey($formId, $questionSid);
        $values[$question["INPUT_KEY"]] = (string) $value;
    }

    $defaultStatusId = CFormStatus::GetDefault($formId);
    if ((int) $defaultStatusId > 0 && $formSid !== "") {
        $values["status_" . $formSid] = (int) $defaultStatusId;
    }

    return $values;
}

function szcubeContactSubmit(array $payload): array
{
    global $strError;

    if (class_exists("CModule") && CModule::IncludeModule("form")) {
        try {
            $webForm = szcubeContactGetWebForm();
            if (!is_array($webForm) || (int) $webForm["ID"] <= 0) {
                return szcubeContactStoreStub($payload);
            }

            $formValues = szcubeContactBuildBitrixFormValues($webForm, $payload);
            $strError = "";
            $resultId = CFormResult::Add((int) $webForm["ID"], $formValues, "Y");

            if ((int) $resultId <= 0) {
                return array(
                    "success" => false,
                    "code" => "bitrix_form_add_failed",
                    "message" => "Не удалось сохранить заявку в веб-форму.",
                    "details" => trim((string) $strError),
                );
            }

            return array(
                "success" => true,
                "mode" => "bitrix_form",
                "result_id" => (int) $resultId,
                "form_id" => (int) $webForm["ID"],
            );
        } catch (\Throwable $e) {
            return array(
                "success" => false,
                "code" => "bitrix_form_mapping_failed",
                "message" => "Веб-форма настроена не полностью. Проверьте поля в админке.",
                "details" => $e->getMessage(),
            );
        }
    }

    return szcubeContactStoreStub($payload);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    szcubeContactJsonResponse(
        array(
            "success" => false,
            "message" => "Метод не поддерживается.",
        ),
        405
    );
}

if (!check_bitrix_sessid() && szcubeContactRequireSessid()) {
    szcubeContactJsonResponse(
        array(
            "success" => false,
            "message" => "Сессия истекла. Обновите страницу и попробуйте снова.",
        ),
        403
    );
}

$name = isset($_POST["name"]) ? szcubeContactTrim((string) $_POST["name"]) : "";
$rawPhone = isset($_POST["phone"]) ? (string) $_POST["phone"] : "";
$phone = szcubeContactNormalizePhone($rawPhone);
$consent = isset($_POST["consent"]) && (string) $_POST["consent"] === "Y";
$leadType = isset($_POST["lead_type"]) ? szcubeContactSanitizeSlug((string) $_POST["lead_type"], "callback") : "callback";
$leadSource = isset($_POST["lead_source"]) ? szcubeContactSanitizeSlug((string) $_POST["lead_source"], "unknown") : "unknown";
$pageUrl = isset($_POST["page_url"]) ? trim((string) $_POST["page_url"]) : "";

if ($pageUrl === "" && isset($_SERVER["HTTP_REFERER"])) {
    $pageUrl = (string) $_SERVER["HTTP_REFERER"];
}

$errors = array();

if ($name === "") {
    $errors["name"] = "Укажите имя.";
} elseif (mb_strlen($name) < 2) {
    $errors["name"] = "Имя слишком короткое.";
}

if ($phone === "") {
    $errors["phone"] = "Укажите телефон в формате РФ.";
}

if (!$consent) {
    $errors["consent"] = "Нужно согласие на обработку персональных данных.";
}

if (!empty($errors)) {
    szcubeContactJsonResponse(
        array(
            "success" => false,
            "message" => "Проверьте заполнение формы.",
            "errors" => $errors,
        ),
        422
    );
}

$requestData = array(
    "name" => $name,
    "phone" => $phone,
    "lead_type" => $leadType,
    "lead_source" => $leadSource,
    "page_url" => $pageUrl,
    "user_agent" => isset($_SERVER["HTTP_USER_AGENT"]) ? (string) $_SERVER["HTTP_USER_AGENT"] : "",
    "ip" => isset($_SERVER["REMOTE_ADDR"]) ? (string) $_SERVER["REMOTE_ADDR"] : "",
    "created_at" => date("c"),
);

$submitResult = szcubeContactSubmit($requestData);

if (empty($submitResult["success"])) {
    szcubeContactJsonResponse(
        array(
            "success" => false,
            "message" => isset($submitResult["message"]) ? (string) $submitResult["message"] : "Не удалось отправить заявку.",
            "code" => isset($submitResult["code"]) ? (string) $submitResult["code"] : "submit_failed",
        ),
        500
    );
}

szcubeContactJsonResponse(
    array(
        "success" => true,
        "message" => "Спасибо! Мы свяжемся с вами в ближайшее время.",
        "mode" => isset($submitResult["mode"]) ? (string) $submitResult["mode"] : "unknown",
    )
);

<?php
/**
 * Идемпотентное создание web-формы "Заявки" для кастомной формы сайта.
 *
 * Создает/обновляет:
 * - web-форму (модуль form)
 * - права формы (публичная отправка + полный доступ админам)
 * - статус по умолчанию "Новая" (с правом MOVE для публичной группы)
 * - вопросы и ответы (по одному answer на вопрос для программного CFormResult::Add)
 *
 * CLI:
 *   php local/tools/create_contact_web_form.php
 *   php local/tools/create_contact_web_form.php --form-sid=SZCUBE_LEADS --site-id=s1
 *   php local/tools/create_contact_web_form.php --dry-run=1
 *
 * Web (локально/под админом):
 *   /local/tools/create_contact_web_form.php
 */

@set_time_limit(0);

$_SERVER["DOCUMENT_ROOT"] = isset($_SERVER["DOCUMENT_ROOT"]) && $_SERVER["DOCUMENT_ROOT"] !== ""
	? rtrim((string)$_SERVER["DOCUMENT_ROOT"], "/")
	: rtrim(dirname(__DIR__, 2), "/");

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);

if (PHP_SAPI === "cli") {
	$options = getopt("", array(
		"form-sid::",
		"form-name::",
		"site-id::",
		"public-group-id::",
		"admin-group-id::",
		"dry-run::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/create_contact_web_form.php [--form-sid=SZCUBE_LEADS] [--site-id=s1] [--dry-run=1]\n";
		exit(0);
	}

	foreach ($options as $key => $value) {
		$_REQUEST[str_replace("-", "_", $key)] = $value;
	}
}

$prologPath = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
if (!is_file($prologPath)) {
	echo "Bitrix bootstrap not found: " . $prologPath . PHP_EOL;
	exit(1);
}

require $prologPath;

if (!class_exists("\\Bitrix\\Main\\Loader")) {
	echo "Bitrix Loader class is unavailable" . PHP_EOL;
	exit(1);
}

if (!\Bitrix\Main\Loader::includeModule("form")) {
	echo "Failed to load form module" . PHP_EOL;
	exit(1);
}

function contactFormToolOut(string $message): void
{
	if (PHP_SAPI === "cli") {
		echo $message . PHP_EOL;
		return;
	}

	echo htmlspecialcharsbx($message) . "<br>";
}

function contactFormToolFail(string $message, int $code = 1): void
{
	contactFormToolOut("[ERROR] " . $message);
	exit($code);
}

function contactFormToolAllSiteIds(): array
{
	$siteIds = array();

	$by = "sort";
	$order = "asc";
	$rsSites = CSite::GetList($by, $order);
	while ($site = $rsSites->Fetch()) {
		if (!empty($site["LID"])) {
			$siteIds[] = (string)$site["LID"];
		}
	}

	$siteIds = array_values(array_unique($siteIds));
	sort($siteIds);

	return $siteIds;
}

function contactFormToolGetFormBySid(string $sid): ?array
{
	$rs = CForm::GetBySID($sid);
	$row = $rs ? $rs->Fetch() : false;

	return is_array($row) ? $row : null;
}

function contactFormToolEnsureForm(array $config): array
{
	global $strError;

	$existingForm = contactFormToolGetFormBySid($config["SID"]);
	$formId = $existingForm ? (int)$existingForm["ID"] : 0;

	$formFields = array(
		"NAME" => $config["NAME"],
		"SID" => $config["SID"],
		"C_SORT" => 100,
		"BUTTON" => "Отправить",
		"USE_CAPTCHA" => "N",
		"DESCRIPTION" => "Заявки с сайта Szcube (кастомный UI + AJAX endpoint).",
		"DESCRIPTION_TYPE" => "text",
		"USE_DEFAULT_TEMPLATE" => "Y",
		"STAT_EVENT1" => "form",
		"STAT_EVENT2" => $config["SID"],
		"STAT_EVENT3" => "contact_lead",
		"arSITE" => $config["SITE_IDS"],
		"arGROUP" => array(
			(int)$config["ADMIN_GROUP_ID"] => 30,
			(int)$config["PUBLIC_GROUP_ID"] => 10,
		),
	);

	$strError = "";
	$savedId = CForm::Set($formFields, $formId, "N");
	if ((int)$savedId <= 0) {
		contactFormToolFail("Failed to create/update form. " . trim((string)$strError), 2);
	}

	$formRow = contactFormToolGetFormBySid($config["SID"]);
	if (!$formRow) {
		contactFormToolFail("Form saved but cannot be reloaded by SID=" . $config["SID"], 2);
	}

	$mode = $existingForm ? "UPDATE" : "CREATE";
	contactFormToolOut(sprintf("[%s] Form: ID=%d SID=%s NAME=%s", $mode, (int)$formRow["ID"], $formRow["SID"], $formRow["NAME"]));

	return $formRow;
}

function contactFormToolEnsureDefaultStatus(int $formId, int $adminGroupId, int $publicGroupId): array
{
	global $strError;

	$defaultStatusId = (int)CFormStatus::GetDefault($formId);
	$statusRow = null;

	if ($defaultStatusId > 0) {
		$rsStatus = CFormStatus::GetByID($defaultStatusId);
		$statusRow = $rsStatus ? $rsStatus->Fetch() : false;
		$statusRow = is_array($statusRow) ? $statusRow : null;
	}

	if (!$statusRow) {
		$rsStatusList = CFormStatus::GetList($formId, "s_sort", "asc", array(
			"TITLE" => "Новая",
			"TITLE_EXACT_MATCH" => "Y",
		));
		$row = $rsStatusList ? $rsStatusList->Fetch() : false;
		$statusRow = is_array($row) ? $row : null;
	}

	$statusId = $statusRow ? (int)$statusRow["ID"] : 0;

	$statusFields = array(
		"FORM_ID" => $formId,
		"ACTIVE" => "Y",
		"C_SORT" => 100,
		"TITLE" => "Новая",
		"DESCRIPTION" => "Новая заявка с сайта",
		"DEFAULT_VALUE" => "Y",
		"arPERMISSION_VIEW" => array($adminGroupId),
		"arPERMISSION_MOVE" => array($adminGroupId, $publicGroupId),
		"arPERMISSION_EDIT" => array($adminGroupId),
		"arPERMISSION_DELETE" => array($adminGroupId),
	);

	$strError = "";
	$savedStatusId = CFormStatus::Set($statusFields, $statusId, "N");
	if ((int)$savedStatusId <= 0) {
		contactFormToolFail("Failed to create/update default status. " . trim((string)$strError), 3);
	}

	$rsStatus = CFormStatus::GetByID((int)$savedStatusId);
	$row = $rsStatus ? $rsStatus->Fetch() : false;
	if (!is_array($row)) {
		contactFormToolFail("Status saved but cannot be reloaded. STATUS_ID=" . (int)$savedStatusId, 3);
	}

	$mode = $statusId > 0 ? "UPDATE" : "CREATE";
	contactFormToolOut(sprintf("[%s] Status: ID=%d TITLE=%s DEFAULT=%s", $mode, (int)$row["ID"], $row["TITLE"], $row["DEFAULT_VALUE"]));

	return $row;
}

function contactFormToolGetFieldBySid(int $formId, string $sid): ?array
{
	$rsField = CFormField::GetBySID($sid, $formId);
	$row = $rsField ? $rsField->Fetch() : false;

	return is_array($row) ? $row : null;
}

function contactFormToolFirstAnswerForField(int $fieldId): ?array
{
	$rsAnswers = CFormAnswer::GetList($fieldId, "s_sort", "asc");
	$row = $rsAnswers ? $rsAnswers->Fetch() : false;

	return is_array($row) ? $row : null;
}

function contactFormToolEnsureSingleAnswer(int $fieldId, array $answerConfig): array
{
	global $strError;

	$existingAnswers = array();
	$rsAnswers = CFormAnswer::GetList($fieldId, "s_sort", "asc");
	while ($answer = $rsAnswers->Fetch()) {
		$existingAnswers[] = $answer;
	}

	$primaryAnswer = !empty($existingAnswers) ? $existingAnswers[0] : null;
	$primaryAnswerId = $primaryAnswer ? (int)$primaryAnswer["ID"] : 0;

	$strError = "";
	$savedAnswerId = CFormAnswer::Set(array(
		"QUESTION_ID" => $fieldId,
		"MESSAGE" => $answerConfig["MESSAGE"],
		"VALUE" => $answerConfig["VALUE"],
		"C_SORT" => (int)$answerConfig["C_SORT"],
		"ACTIVE" => "Y",
		"FIELD_TYPE" => $answerConfig["FIELD_TYPE"],
		"FIELD_WIDTH" => (int)$answerConfig["FIELD_WIDTH"],
		"FIELD_HEIGHT" => (int)$answerConfig["FIELD_HEIGHT"],
		"FIELD_PARAM" => isset($answerConfig["FIELD_PARAM"]) ? (string)$answerConfig["FIELD_PARAM"] : "",
	), $primaryAnswerId);

	if ((int)$savedAnswerId <= 0) {
		contactFormToolFail("Failed to create/update answer for FIELD_ID=" . $fieldId . ". " . trim((string)$strError), 5);
	}

	if (count($existingAnswers) > 1) {
		for ($i = 1, $cnt = count($existingAnswers); $i < $cnt; $i++) {
			CFormAnswer::Delete((int)$existingAnswers[$i]["ID"], $fieldId);
		}
	}

	$rsAnswer = CFormAnswer::GetByID((int)$savedAnswerId);
	$row = $rsAnswer ? $rsAnswer->Fetch() : false;
	if (!is_array($row)) {
		contactFormToolFail("Answer saved but cannot be reloaded. ANSWER_ID=" . (int)$savedAnswerId, 5);
	}

	return $row;
}

function contactFormToolEnsureQuestion(int $formId, array $questionConfig): array
{
	global $strError;

	$existingField = contactFormToolGetFieldBySid($formId, $questionConfig["SID"]);
	$fieldId = $existingField ? (int)$existingField["ID"] : 0;

	$fieldFields = array(
		"FORM_ID" => $formId,
		"ACTIVE" => "Y",
		"TITLE" => $questionConfig["TITLE"],
		"TITLE_TYPE" => "text",
		"SID" => $questionConfig["SID"],
		"C_SORT" => (int)$questionConfig["C_SORT"],
		"ADDITIONAL" => "N",
		"REQUIRED" => $questionConfig["REQUIRED"] ? "Y" : "N",
		"IN_RESULTS_TABLE" => "Y",
		"IN_EXCEL_TABLE" => "Y",
		"FIELD_TYPE" => $questionConfig["FIELD_TYPE"],
		"COMMENTS" => isset($questionConfig["COMMENTS"]) ? (string)$questionConfig["COMMENTS"] : "",
		"FILTER_TITLE" => $questionConfig["TITLE"],
		"RESULTS_TABLE_TITLE" => $questionConfig["TITLE"],
	);

	$strError = "";
	$savedFieldId = CFormField::Set($fieldFields, $fieldId, "N", "N");
	if ((int)$savedFieldId <= 0) {
		contactFormToolFail("Failed to create/update question SID=" . $questionConfig["SID"] . ". " . trim((string)$strError), 4);
	}

	$fieldRow = contactFormToolGetFieldBySid($formId, $questionConfig["SID"]);
	if (!$fieldRow) {
		contactFormToolFail("Question saved but cannot be reloaded. SID=" . $questionConfig["SID"], 4);
	}

	$answerRow = contactFormToolEnsureSingleAnswer((int)$fieldRow["ID"], array(
		"MESSAGE" => isset($questionConfig["ANSWER_MESSAGE"]) ? (string)$questionConfig["ANSWER_MESSAGE"] : $questionConfig["TITLE"],
		"VALUE" => $questionConfig["SID"],
		"C_SORT" => (int)$questionConfig["C_SORT"],
		"FIELD_TYPE" => $questionConfig["FIELD_TYPE"],
		"FIELD_WIDTH" => isset($questionConfig["FIELD_WIDTH"]) ? (int)$questionConfig["FIELD_WIDTH"] : 40,
		"FIELD_HEIGHT" => isset($questionConfig["FIELD_HEIGHT"]) ? (int)$questionConfig["FIELD_HEIGHT"] : 0,
		"FIELD_PARAM" => isset($questionConfig["FIELD_PARAM"]) ? (string)$questionConfig["FIELD_PARAM"] : "",
	));

	$mode = $existingField ? "UPDATE" : "CREATE";
	contactFormToolOut(sprintf(
		"[%s] Question: SID=%s FIELD_ID=%d ANSWER_ID=%d INPUT_KEY=%s",
		$mode,
		$fieldRow["SID"],
		(int)$fieldRow["ID"],
		(int)$answerRow["ID"],
		"form_" . $answerRow["FIELD_TYPE"] . "_" . (int)$answerRow["ID"]
	));

	return array(
		"FIELD" => $fieldRow,
		"ANSWER" => $answerRow,
	);
}

$formSid = isset($_REQUEST["form_sid"]) && trim((string)$_REQUEST["form_sid"]) !== "" ? trim((string)$_REQUEST["form_sid"]) : "SZCUBE_LEADS";
$formName = isset($_REQUEST["form_name"]) && trim((string)$_REQUEST["form_name"]) !== "" ? trim((string)$_REQUEST["form_name"]) : "Заявки";
$siteId = isset($_REQUEST["site_id"]) && trim((string)$_REQUEST["site_id"]) !== "" ? trim((string)$_REQUEST["site_id"]) : "";
$publicGroupId = isset($_REQUEST["public_group_id"]) ? (int)$_REQUEST["public_group_id"] : 2;
$adminGroupId = isset($_REQUEST["admin_group_id"]) ? (int)$_REQUEST["admin_group_id"] : 1;
$dryRun = false;
if (isset($_REQUEST["dry_run"])) {
	$dryRaw = trim((string)$_REQUEST["dry_run"]);
	$dryRun = ($dryRaw === "" || $dryRaw === "1" || strtolower($dryRaw) === "y" || strtolower($dryRaw) === "yes" || strtolower($dryRaw) === "true");
}

$siteIds = contactFormToolAllSiteIds();
if ($siteId !== "") {
	if (!in_array($siteId, $siteIds, true)) {
		$siteIds[] = $siteId;
	}
}
if (empty($siteIds) && defined("SITE_ID") && (string)SITE_ID !== "") {
	$siteIds[] = (string)SITE_ID;
}
if (empty($siteIds)) {
	contactFormToolFail("No Bitrix sites found for binding form (arSITE).", 1);
}

sort($siteIds);

contactFormToolOut("Target form SID: " . $formSid);
contactFormToolOut("Sites: " . implode(", ", $siteIds));
contactFormToolOut("Groups: admin=" . $adminGroupId . ", public=" . $publicGroupId);
contactFormToolOut("Mode: " . ($dryRun ? "DRY-RUN (no changes)" : "APPLY"));

$questionSchema = array(
	array(
		"SID" => "NAME",
		"TITLE" => "Имя",
		"C_SORT" => 100,
		"REQUIRED" => true,
		"FIELD_TYPE" => "text",
		"COMMENTS" => "Имя клиента",
		"ANSWER_MESSAGE" => "Имя",
		"FIELD_WIDTH" => 40,
	),
	array(
		"SID" => "PHONE",
		"TITLE" => "Телефон",
		"C_SORT" => 200,
		"REQUIRED" => true,
		"FIELD_TYPE" => "text",
		"COMMENTS" => "Телефон в формате +7XXXXXXXXXX",
		"ANSWER_MESSAGE" => "Телефон",
		"FIELD_WIDTH" => 40,
	),
	array(
		"SID" => "LEAD_TYPE",
		"TITLE" => "Тип заявки",
		"C_SORT" => 300,
		"REQUIRED" => false,
		"FIELD_TYPE" => "text",
		"COMMENTS" => "callback / consulting / tenders / ...",
		"ANSWER_MESSAGE" => "Тип заявки",
		"FIELD_WIDTH" => 30,
	),
	array(
		"SID" => "LEAD_SOURCE",
		"TITLE" => "Источник заявки",
		"C_SORT" => 400,
		"REQUIRED" => false,
		"FIELD_TYPE" => "text",
		"COMMENTS" => "header / footer / page inline / ...",
		"ANSWER_MESSAGE" => "Источник заявки",
		"FIELD_WIDTH" => 40,
	),
	array(
		"SID" => "PAGE_URL",
		"TITLE" => "URL страницы",
		"C_SORT" => 500,
		"REQUIRED" => false,
		"FIELD_TYPE" => "text",
		"COMMENTS" => "URL страницы, с которой отправлена заявка",
		"ANSWER_MESSAGE" => "URL страницы",
		"FIELD_WIDTH" => 80,
	),
	array(
		"SID" => "CONSENT",
		"TITLE" => "Согласие ПД",
		"C_SORT" => 600,
		"REQUIRED" => false,
		"FIELD_TYPE" => "text",
		"COMMENTS" => "Y/N (сейчас UI валидирует обязательно, в форму пишем Y)",
		"ANSWER_MESSAGE" => "Согласие ПД",
		"FIELD_WIDTH" => 5,
	),
);

if ($dryRun) {
	$existingForm = contactFormToolGetFormBySid($formSid);

	if ($existingForm) {
		contactFormToolOut(sprintf("[DRY-RUN][UPDATE] Form exists: ID=%d SID=%s NAME=%s", (int)$existingForm["ID"], $existingForm["SID"], $existingForm["NAME"]));

		$defaultStatusId = (int)CFormStatus::GetDefault((int)$existingForm["ID"]);
		if ($defaultStatusId > 0) {
			$rsStatus = CFormStatus::GetByID($defaultStatusId);
			$statusRow = $rsStatus ? $rsStatus->Fetch() : false;
			if (is_array($statusRow)) {
				contactFormToolOut(sprintf("[DRY-RUN][UPDATE] Default status exists: ID=%d TITLE=%s", (int)$statusRow["ID"], $statusRow["TITLE"]));
			} else {
				contactFormToolOut("[DRY-RUN][CREATE] Default status 'Новая' (default)");
			}
		} else {
			contactFormToolOut("[DRY-RUN][CREATE] Default status 'Новая' (default)");
		}

		foreach ($questionSchema as $questionConfig) {
			$field = contactFormToolGetFieldBySid((int)$existingForm["ID"], $questionConfig["SID"]);
			if (!$field) {
				contactFormToolOut(sprintf(
					"[DRY-RUN][CREATE] Question SID=%s TITLE=%s FIELD_TYPE=%s",
					$questionConfig["SID"],
					$questionConfig["TITLE"],
					$questionConfig["FIELD_TYPE"]
				));
				continue;
			}

			$answer = contactFormToolFirstAnswerForField((int)$field["ID"]);
			if ($answer) {
				contactFormToolOut(sprintf(
					"[DRY-RUN][UPDATE] Question SID=%s FIELD_ID=%d ANSWER_ID=%d INPUT_KEY=%s",
					$field["SID"],
					(int)$field["ID"],
					(int)$answer["ID"],
					"form_" . $answer["FIELD_TYPE"] . "_" . (int)$answer["ID"]
				));
			} else {
				contactFormToolOut(sprintf(
					"[DRY-RUN][UPDATE] Question SID=%s FIELD_ID=%d (answer will be created)",
					$field["SID"],
					(int)$field["ID"]
				));
			}
		}
	} else {
		contactFormToolOut(sprintf("[DRY-RUN][CREATE] Form will be created: SID=%s NAME=%s", $formSid, $formName));
		contactFormToolOut("[DRY-RUN][CREATE] Default status 'Новая' (default)");
		foreach ($questionSchema as $questionConfig) {
			contactFormToolOut(sprintf(
				"[DRY-RUN][CREATE] Question SID=%s TITLE=%s FIELD_TYPE=%s",
				$questionConfig["SID"],
				$questionConfig["TITLE"],
				$questionConfig["FIELD_TYPE"]
			));
		}
	}

	contactFormToolOut(str_repeat("-", 60));
	contactFormToolOut("[DRY-RUN OK] Проверка пройдена. Скрипт готов к запуску без --dry-run.");
	exit(0);
}

$form = contactFormToolEnsureForm(array(
	"SID" => $formSid,
	"NAME" => $formName,
	"SITE_IDS" => $siteIds,
	"ADMIN_GROUP_ID" => $adminGroupId,
	"PUBLIC_GROUP_ID" => $publicGroupId,
));

$status = contactFormToolEnsureDefaultStatus((int)$form["ID"], $adminGroupId, $publicGroupId);

$fieldMap = array();
foreach ($questionSchema as $questionConfig) {
	$ensured = contactFormToolEnsureQuestion((int)$form["ID"], $questionConfig);
	$fieldMap[$questionConfig["SID"]] = array(
		"FIELD_ID" => (int)$ensured["FIELD"]["ID"],
		"ANSWER_ID" => (int)$ensured["ANSWER"]["ID"],
		"INPUT_KEY" => "form_" . $ensured["ANSWER"]["FIELD_TYPE"] . "_" . (int)$ensured["ANSWER"]["ID"],
	);
}

contactFormToolOut(str_repeat("-", 60));
contactFormToolOut("Summary");
contactFormToolOut("FORM_ID=" . (int)$form["ID"]);
contactFormToolOut("FORM_SID=" . $form["SID"]);
contactFormToolOut("DEFAULT_STATUS_ID=" . (int)$status["ID"]);
contactFormToolOut("Suggested endpoint config:");
contactFormToolOut("  web-form SID: " . $form["SID"]);
contactFormToolOut("  use CForm::GetBySID('" . $form["SID"] . "') and resolve answer IDs dynamically");
contactFormToolOut("Input keys for CFormResult::Add:");
foreach ($fieldMap as $sid => $row) {
	contactFormToolOut("  " . $sid . " => " . $row["INPUT_KEY"]);
}
contactFormToolOut("[OK] Web-форма успешно создана/обновлена.");

exit(0);

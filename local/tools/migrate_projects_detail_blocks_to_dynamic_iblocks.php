<?php
/**
 * Переносит "Преимущества" и "Ход строительства"
 * из свойств ИБ projects в отдельные динамические ИБ:
 * - project_advantages
 * - project_construction
 *
 * Запуск:
 *   php local/tools/migrate_projects_detail_blocks_to_dynamic_iblocks.php --dry-run=1
 *   php local/tools/migrate_projects_detail_blocks_to_dynamic_iblocks.php --dry-run=0
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
	$options = getopt("", array("dry-run::", "help::"));
	if (isset($options["help"])) {
		echo "Usage: php local/tools/migrate_projects_detail_blocks_to_dynamic_iblocks.php [--dry-run=1]\n";
		exit(0);
	}
	if (isset($options["dry-run"])) {
		$_REQUEST["dry_run"] = $options["dry-run"];
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

if (!\Bitrix\Main\Loader::includeModule("iblock")) {
	echo "Failed to load iblock module" . PHP_EOL;
	exit(1);
}

$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

function iblockIdByCode($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return (int)$row["ID"];
	}

	return 0;
}

function scalarProperty($properties, $code, $default = "")
{
	if (!isset($properties[$code]["VALUE"])) {
		return (string)$default;
	}
	$value = $properties[$code]["VALUE"];
	if (is_array($value)) {
		$value = reset($value);
	}
	$value = trim((string)$value);
	return $value !== "" ? $value : (string)$default;
}

function fileIdFromProperty($properties, $code)
{
	if (!isset($properties[$code]["VALUE"])) {
		return 0;
	}
	$value = $properties[$code]["VALUE"];
	if (is_array($value)) {
		$value = reset($value);
	}
	return (int)$value;
}

function fileIdsFromProperty($properties, $code)
{
	if (!isset($properties[$code]["VALUE"])) {
		return array();
	}
	$value = $properties[$code]["VALUE"];
	if (!is_array($value)) {
		$value = $value !== "" ? array($value) : array();
	}
	$result = array();
	foreach ($value as $idRaw) {
		$id = (int)$idRaw;
		if ($id > 0) {
			$result[] = $id;
		}
	}
	return $result;
}

function fileArrayFromId($fileId)
{
	$fileId = (int)$fileId;
	if ($fileId <= 0) {
		return false;
	}

	$path = CFile::GetPath($fileId);
	if (!$path) {
		return false;
	}

	$abs = $_SERVER["DOCUMENT_ROOT"] . $path;
	if (!is_file($abs)) {
		return false;
	}

	return CFile::MakeFileArray($abs);
}

function fileArraysFromIds(array $ids)
{
	$result = array();
	$index = 0;
	foreach ($ids as $id) {
		$file = fileArrayFromId($id);
		if ($file === false) {
			continue;
		}
		$result["n" . $index] = array(
			"VALUE" => $file,
			"DESCRIPTION" => "",
		);
		$index++;
	}
	return $result;
}

function upsertElement($iblockId, $code, array $fields, array $properties, $dryRun)
{
	$existingId = 0;
	$res = CIBlockElement::GetList(
		array(),
		array("IBLOCK_ID" => $iblockId, "=CODE" => $code),
		false,
		false,
		array("ID")
	);
	if ($row = $res->Fetch()) {
		$existingId = (int)$row["ID"];
	}

	$action = $existingId > 0 ? "UPDATE" : "CREATE";
	echo "  [{$action}] {$code}" . PHP_EOL;

	if ($dryRun) {
		return array("action" => $action, "ok" => true);
	}

	$el = new CIBlockElement();
	if ($existingId > 0) {
		if (!$el->Update($existingId, $fields)) {
			return array("action" => $action, "ok" => false, "error" => $el->LAST_ERROR);
		}
		CIBlockElement::SetPropertyValuesEx($existingId, $iblockId, $properties);
		return array("action" => $action, "ok" => true);
	}

	$newId = $el->Add($fields);
	if (!$newId) {
		return array("action" => $action, "ok" => false, "error" => $el->LAST_ERROR);
	}
	CIBlockElement::SetPropertyValuesEx((int)$newId, $iblockId, $properties);
	return array("action" => $action, "ok" => true);
}

$projectsIblockId = iblockIdByCode("projects");
$advantagesIblockId = iblockIdByCode("project_advantages");
$constructionIblockId = iblockIdByCode("project_construction");

if ($projectsIblockId <= 0 || $advantagesIblockId <= 0 || $constructionIblockId <= 0) {
	echo "Required iblocks not found. Run create_project_detail_dynamic_iblocks.php first." . PHP_EOL;
	echo "projects={$projectsIblockId}, advantages={$advantagesIblockId}, construction={$constructionIblockId}" . PHP_EOL;
	exit(2);
}

echo "Projects IBlock ID: {$projectsIblockId}" . PHP_EOL;
echo "Advantages IBlock ID: {$advantagesIblockId}" . PHP_EOL;
echo "Construction IBlock ID: {$constructionIblockId}" . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

$benefitDefaults = array(
	array("label" => "Локация", "title" => "ЖК — жизнь в удобной локации", "text" => "До ключевых точек города — быстро, при этом рядом зелёные зоны и спокойные улицы."),
	array("label" => "Благоустройство", "title" => "Закрытая территория и контроль доступа", "text" => "Закрытая территория, контроль доступа и видеонаблюдение для безопасности жителей."),
	array("label" => "Инфраструктура", "title" => "Всё необходимое рядом", "text" => "Повседневная инфраструктура в пешей доступности: магазины, сервисы, школы и детские сады."),
	array("label" => "Отделка", "title" => "Гибкие решения по отделке квартиры", "text" => "Предчистовая база и дополнительные опции: чистовая отделка и дизайн-проект."),
	array("label" => "Фасад и материалы", "title" => "Современная архитектура", "text" => "Лаконичная архитектура, продуманные материалы и выразительные фасадные решения."),
	array("label" => "Планировки", "title" => "Функциональные планировки", "text" => "Планировочные решения под разные жизненные сценарии без лишних метров."),
	array("label" => "Благоустройство", "title" => "Подземный паркинг и зоны отдыха", "text" => "Подземный паркинг, детские зоны и пространства для отдыха жителей."),
	array("label" => "Инфраструктура", "title" => "Комфортные общественные пространства", "text" => "Продуманная внутренняя инфраструктура и современные входные группы."),
);

$constructionDefaults = array(
	array("title" => "Октябрь 2025", "date" => "октябрь 2025", "text" => "Подготовка площадки и старт ключевых этапов."),
	array("title" => "Ноябрь 2025", "date" => "ноябрь 2025", "text" => "Продолжаем строительство: фиксируем прогресс на объекте."),
	array("title" => "Декабрь 2025", "date" => "декабрь 2025", "text" => "Итоги месяца: основные работы на площадке и общий прогресс."),
	array("title" => "Январь 2026", "date" => "январь 2026", "text" => "Новый этап строительства: показываем текущее состояние объекта."),
	array("title" => "Февраль 2026", "date" => "февраль 2026", "text" => "Промежуточный фотоотчет: свежие кадры с площадки."),
	array("title" => "Март 2026", "date" => "март 2026", "text" => "Ежемесячный отчет о ходе строительства и динамике работ."),
);

$created = 0;
$updated = 0;
$errors = array();

$projectsRes = CIBlockElement::GetList(
	array("SORT" => "ASC", "ID" => "ASC"),
	array("IBLOCK_ID" => $projectsIblockId, "ACTIVE" => "Y"),
	false,
	false,
	array("ID", "IBLOCK_ID", "NAME", "CODE")
);

while ($projectElement = $projectsRes->GetNextElement()) {
	$fields = $projectElement->GetFields();
	$properties = $projectElement->GetProperties();

	$projectId = (int)$fields["ID"];
	$projectCode = trim((string)$fields["CODE"]);
	if ($projectId <= 0 || $projectCode === "") {
		continue;
	}

	echo "[PROJECT] {$projectCode} :: " . trim((string)$fields["NAME"]) . PHP_EOL;

	for ($i = 1; $i <= 8; $i++) {
		$def = isset($benefitDefaults[$i - 1]) ? $benefitDefaults[$i - 1] : array("label" => "", "title" => "", "text" => "");

		$title = scalarProperty($properties, "BEN" . $i . "_TITLE", $def["title"]);
		$text = scalarProperty($properties, "BEN" . $i . "_TEXT", $def["text"]);
		$label = scalarProperty($properties, "BEN" . $i . "_LABEL", $def["label"]);
		$imageFileId = fileIdFromProperty($properties, "BEN" . $i . "_IMAGE");
		$imageFile = fileArrayFromId($imageFileId);

		if ($title === "" && $text === "" && $imageFile === false) {
			continue;
		}

		$code = $projectCode . "-ben-" . $i;
		$elementFields = array(
			"IBLOCK_ID" => $advantagesIblockId,
			"ACTIVE" => "Y",
			"NAME" => $title !== "" ? $title : ("Преимущество " . $i),
			"CODE" => $code,
			"SORT" => $i * 100,
			"PREVIEW_TEXT" => $text,
			"PREVIEW_TEXT_TYPE" => "text",
		);
		if ($imageFile !== false) {
			$elementFields["PREVIEW_PICTURE"] = $imageFile;
		}

		$elementProperties = array(
			"PROJECT" => $projectId,
			"LABEL" => $label,
		);

		$result = upsertElement($advantagesIblockId, $code, $elementFields, $elementProperties, $dryRun);
		if (!$result["ok"]) {
			$errors[] = "Benefit {$code}: " . (isset($result["error"]) ? $result["error"] : "unknown error");
			continue;
		}
		if ($result["action"] === "CREATE") {
			$created++;
		} else {
			$updated++;
		}
	}

	for ($i = 1; $i <= 6; $i++) {
		$def = isset($constructionDefaults[$i - 1]) ? $constructionDefaults[$i - 1] : array("title" => "", "date" => "", "text" => "");

		$title = scalarProperty($properties, "CONS" . $i . "_TITLE", $def["title"]);
		$dateText = scalarProperty($properties, "CONS" . $i . "_DATE", $def["date"]);
		$text = scalarProperty($properties, "CONS" . $i . "_TEXT", $def["text"]);
		$imageFileId = fileIdFromProperty($properties, "CONS" . $i . "_IMAGE");
		$imageFile = fileArrayFromId($imageFileId);
		$galleryIds = fileIdsFromProperty($properties, "CONS" . $i . "_GALLERY");
		$galleryFiles = fileArraysFromIds($galleryIds);
		if (empty($galleryFiles) && $imageFile !== false) {
			$galleryFiles = array(
				"n0" => array(
					"VALUE" => $imageFile,
					"DESCRIPTION" => "",
				),
			);
		}

		if ($title === "" && $text === "" && $imageFile === false && empty($galleryFiles)) {
			continue;
		}

		$code = $projectCode . "-cons-" . $i;
		$elementFields = array(
			"IBLOCK_ID" => $constructionIblockId,
			"ACTIVE" => "Y",
			"NAME" => $title !== "" ? $title : ("Отчет " . $i),
			"CODE" => $code,
			"SORT" => $i * 100,
			"PREVIEW_TEXT" => $text,
			"PREVIEW_TEXT_TYPE" => "text",
		);
		if ($imageFile !== false) {
			$elementFields["PREVIEW_PICTURE"] = $imageFile;
		}

		$elementProperties = array(
			"PROJECT" => $projectId,
			"DATE_TEXT" => $dateText,
		);
		if (!empty($galleryFiles)) {
			$elementProperties["GALLERY"] = $galleryFiles;
		}

		$result = upsertElement($constructionIblockId, $code, $elementFields, $elementProperties, $dryRun);
		if (!$result["ok"]) {
			$errors[] = "Construction {$code}: " . (isset($result["error"]) ? $result["error"] : "unknown error");
			continue;
		}
		if ($result["action"] === "CREATE") {
			$created++;
		} else {
			$updated++;
		}
	}
}

echo PHP_EOL . "Done. Created: {$created}, Updated: {$updated}" . PHP_EOL;
if (!empty($errors)) {
	echo "Errors:" . PHP_EOL;
	foreach ($errors as $error) {
		echo " - {$error}" . PHP_EOL;
	}
	exit(3);
}

exit(0);

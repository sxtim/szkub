<?php
/**
 * Единый rollout квартирного контура.
 *
 * Запускает только квартирные шаги:
 * - create_apartments_iblocks.php
 * - tune_iblock_property_form_sizes.php
 * - import_apartments_from_seed.php
 * - configure_apartments_admin_form.php
 *
 * CLI:
 *   php local/tools/rollout_apartments.php --dry-run=1
 *   php local/tools/rollout_apartments.php --dry-run=0
 */

@set_time_limit(0);

$_SERVER["DOCUMENT_ROOT"] = isset($_SERVER["DOCUMENT_ROOT"]) && $_SERVER["DOCUMENT_ROOT"] !== ""
	? rtrim((string)$_SERVER["DOCUMENT_ROOT"], "/")
	: rtrim(dirname(__DIR__, 2), "/");

if (PHP_SAPI === "cli") {
	$options = getopt("", array(
		"source::",
		"admin-user-id::",
		"dry-run::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/rollout_apartments.php [--source=/local/tools/data/apartments-seed.php] [--admin-user-id=1] [--dry-run=1]\n";
		exit(0);
	}

	foreach ($options as $key => $value) {
		$_REQUEST[str_replace("-", "_", $key)] = $value;
	}
}

$documentRoot = rtrim((string)$_SERVER["DOCUMENT_ROOT"], "/");
$phpBinary = defined("PHP_BINARY") && PHP_BINARY ? PHP_BINARY : "php";
$sourceRel = isset($_REQUEST["source"]) && $_REQUEST["source"] !== "" ? (string)$_REQUEST["source"] : "/local/tools/data/apartments-seed.php";
$adminUserId = isset($_REQUEST["admin_user_id"]) ? (int)$_REQUEST["admin_user_id"] : 1;
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

echo "DOCUMENT_ROOT: " . $documentRoot . PHP_EOL;
echo "PHP binary: " . $phpBinary . PHP_EOL;
echo "Source: " . $sourceRel . PHP_EOL;
echo "Admin user ID: " . $adminUserId . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

function rolloutRunApartmentStep($label, array $command)
{
	echo PHP_EOL . "[STEP] " . $label . PHP_EOL;
	echo "[CMD] " . implode(" ", array_map("escapeshellarg", $command)) . PHP_EOL;

	$process = proc_open(
		$command,
		array(
			0 => STDIN,
			1 => STDOUT,
			2 => STDERR,
		),
		$pipes,
		$_SERVER["DOCUMENT_ROOT"]
	);

	if (!is_resource($process)) {
		echo "[ERROR] Failed to start step: " . $label . PHP_EOL;
		return 1;
	}

	return (int)proc_close($process);
}

$steps = array(
	array(
		"label" => "Create apartments iblock and properties",
		"script" => "/local/tools/create_apartments_iblocks.php",
		"args" => array(
			"--dry-run=" . ($dryRun ? "1" : "0"),
			"--with-promotion-links=0",
			"--seed-project-sections=1",
		),
	),
	array(
		"label" => "Tune apartments form field sizes",
		"script" => "/local/tools/tune_iblock_property_form_sizes.php",
		"args" => array(
			"--codes=apartments",
			"--dry-run=" . ($dryRun ? "1" : "0"),
		),
	),
	array(
		"label" => "Import apartments from seed",
		"script" => "/local/tools/import_apartments_from_seed.php",
		"args" => array(
			"--source=" . $sourceRel,
			"--dry-run=" . ($dryRun ? "1" : "0"),
		),
	),
	array(
		"label" => "Cleanup legacy apartment samples",
		"script" => "/local/tools/cleanup_legacy_apartment_samples.php",
		"args" => array(
			"--dry-run=" . ($dryRun ? "1" : "0"),
		),
	),
	array(
		"label" => "Configure apartments admin form",
		"script" => "/local/tools/configure_apartments_admin_form.php",
		"args" => array(
			"--code=apartments",
			"--admin-user-id=" . $adminUserId,
			"--dry-run=" . ($dryRun ? "1" : "0"),
		),
	),
);

foreach ($steps as $step) {
	$scriptAbs = $documentRoot . $step["script"];
	if (!is_file($scriptAbs)) {
		echo "[ERROR] Script not found: " . $scriptAbs . PHP_EOL;
		exit(2);
	}

	$command = array_merge(
		array($phpBinary, $scriptAbs),
		$step["args"]
	);

	$exitCode = rolloutRunApartmentStep($step["label"], $command);
	if ($exitCode !== 0) {
		echo PHP_EOL . "[FAIL] Rollout stopped on step: " . $step["label"] . " (exit=" . $exitCode . ")" . PHP_EOL;
		exit($exitCode);
	}
}

echo PHP_EOL . "[OK] Apartment rollout completed." . PHP_EOL;
exit(0);

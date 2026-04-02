<?php
/**
 * Creates Bitrix admin wrapper files for local/admin pages.
 *
 * Usage:
 *   php local/tools/install_admin_wrappers.php
 */

if (empty($_SERVER["DOCUMENT_ROOT"])) {
    $envDocumentRoot = getenv("DOCUMENT_ROOT");
    if (is_string($envDocumentRoot) && trim($envDocumentRoot) !== "") {
        $_SERVER["DOCUMENT_ROOT"] = trim($envDocumentRoot);
    }
}

$resolvedDocumentRoot = trim((string)($_SERVER["DOCUMENT_ROOT"] ?? ""));
if ($resolvedDocumentRoot === "") {
    $resolvedDocumentRoot = dirname(__DIR__, 2);
}

$_SERVER["DOCUMENT_ROOT"] = rtrim($resolvedDocumentRoot, "/");

$documentRoot = $_SERVER["DOCUMENT_ROOT"];
$bitrixAdminDir = $documentRoot . "/bitrix/admin";
$localAdminDir = $documentRoot . "/local/admin";

if (!is_dir($bitrixAdminDir)) {
    fwrite(STDERR, "[ERROR] Directory not found: " . $bitrixAdminDir . PHP_EOL);
    exit(1);
}

if (!is_dir($localAdminDir)) {
    fwrite(STDERR, "[ERROR] Directory not found: " . $localAdminDir . PHP_EOL);
    exit(1);
}

$wrappers = array(
    "szcube_leads.php" => "/local/admin/szcube_leads.php",
);

foreach ($wrappers as $targetFile => $localInclude) {
    $targetPath = $bitrixAdminDir . "/" . $targetFile;
    $content = "<?php\nrequire_once \$_SERVER[\"DOCUMENT_ROOT\"] . \"" . $localInclude . "\";\n";

    if (is_file($targetPath)) {
        $existing = (string)file_get_contents($targetPath);
        if ($existing === $content) {
            echo "[OK] Wrapper exists: " . $targetPath . PHP_EOL;
            continue;
        }
    }

    if (file_put_contents($targetPath, $content) === false) {
        fwrite(STDERR, "[ERROR] Failed to write: " . $targetPath . PHP_EOL);
        exit(1);
    }

    echo "[SYNC] Wrapper installed: " . $targetPath . PHP_EOL;
}

echo "[OK] Admin wrappers installed." . PHP_EOL;

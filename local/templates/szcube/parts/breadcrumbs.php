<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if (isset($APPLICATION) && method_exists($APPLICATION, "GetProperty") && $APPLICATION->GetProperty("NOT_SHOW_NAV_CHAIN") === "Y") {
    return;
}

$docRoot = rtrim((string)$_SERVER["DOCUMENT_ROOT"], "/");
$siteDir = defined("SITE_DIR") ? (string)SITE_DIR : "/";
$siteDir = "/" . trim($siteDir, "/") . "/";
if ($siteDir === "//") {
    $siteDir = "/";
}

$requestUri = isset($_SERVER["REQUEST_URI"]) ? (string)$_SERVER["REQUEST_URI"] : "/";
$requestPath = parse_url($requestUri, PHP_URL_PATH);
if (!is_string($requestPath) || $requestPath === "") {
    $requestPath = "/";
}

if (substr($requestPath, -1) === "/") {
    $currentDir = $requestPath;
} else {
    $currentDir = rtrim(dirname($requestPath), "/") . "/";
}

$currentDir = "/" . trim($currentDir, "/") . "/";
if ($currentDir === "//") {
    $currentDir = "/";
}

$chain = array();
$dir = $currentDir;

while (true) {
    $dirTrimmed = rtrim($dir, "/");
    $sectionFile = $docRoot . $dirTrimmed . "/.section.php";

    if (is_file($sectionFile)) {
        $sSectionName = "";
        $arDirProperties = array();
        include $sectionFile;

        if ($sSectionName !== "") {
            $chain[] = array(
                "TITLE" => (string)$sSectionName,
                "LINK" => $dir,
            );
        }
    }

    if ($dir === $siteDir || $dir === "/") {
        break;
    }

    $dirTrimmed = rtrim($dir, "/");
    $pos = strrpos($dirTrimmed, "/");
    if ($pos === false) {
        break;
    }

    $dir = substr($dirTrimmed, 0, $pos + 1);
    if ($dir === "") {
        $dir = "/";
    }
}

$chain = array_reverse($chain);

$pageTitle = isset($APPLICATION) && method_exists($APPLICATION, "GetTitle") ? trim((string)$APPLICATION->GetTitle(false)) : "";
if ($pageTitle !== "") {
    $lastTitle = !empty($chain) && isset($chain[count($chain) - 1]["TITLE"]) ? (string)$chain[count($chain) - 1]["TITLE"] : "";
    if ($lastTitle !== $pageTitle) {
        $chain[] = array("TITLE" => $pageTitle, "LINK" => "");
    }
}

if (empty($chain)) {
    return;
}
?>

<nav class="breadcrumbs" aria-label="Навигация">
    <ol class="breadcrumbs__list">
        <?php foreach ($chain as $index => $crumb): ?>
            <?php
            $crumbTitle = isset($crumb["TITLE"]) ? (string)$crumb["TITLE"] : "";
            $crumbLink = isset($crumb["LINK"]) ? (string)$crumb["LINK"] : "";
            $isLast = $index === (count($chain) - 1);
            ?>
            <li class="breadcrumbs__item">
                <?php if ($crumbLink !== "" && !$isLast): ?>
                    <a class="breadcrumbs__link" href="<?= htmlspecialcharsbx($crumbLink) ?>"><?= htmlspecialcharsbx($crumbTitle) ?></a>
                <?php else: ?>
                    <span class="breadcrumbs__current" aria-current="page"><?= htmlspecialcharsbx($crumbTitle) ?></span>
                <?php endif; ?>

                <?php if (!$isLast): ?>
                    <span class="breadcrumbs__sep" aria-hidden="true">/</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>

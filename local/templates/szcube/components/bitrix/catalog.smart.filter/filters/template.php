<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
  die();
}

$shortPath = __DIR__ . "/_short.php";
$popupPath = __DIR__ . "/_popup.php";

// Guard against partial deployments (manual SFTP uploads).
// If any include is missing, don't 500 the whole page.
if (!file_exists($shortPath) || !file_exists($popupPath)) {
  global $USER;
  if (is_object($USER) && method_exists($USER, "IsAdmin") && $USER->IsAdmin()) {
    echo "<div style=\"padding:12px;border:1px solid #f0b; color:#b00; background:#fff; font:14px/1.4 sans-serif;\">";
    echo "Smart filter template error: missing required file(s) in <code>" . htmlspecialchars(__DIR__) . "</code>.";
    echo "</div>";
  }
  return;
}

require $shortPath;
require $popupPath;

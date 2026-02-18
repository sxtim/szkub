<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
  die();
}

// Backward-compatible alias: old "projects" template now renders shared "filters" template.
require __DIR__ . "/../filters/template.php";


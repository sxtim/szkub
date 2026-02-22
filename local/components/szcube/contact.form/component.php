<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$modalId = isset($arParams["MODAL_ID"]) ? (string)$arParams["MODAL_ID"] : "contact";
$modalId = trim($modalId) !== "" ? trim($modalId) : "contact";

$title = isset($arParams["TITLE"]) ? (string)$arParams["TITLE"] : "Остались вопросы?";
$title = trim($title);

$arResult = array(
    "MODAL_ID" => $modalId,
    "TITLE" => $title !== "" ? $title : "Остались вопросы?",
);

$this->includeComponentTemplate();


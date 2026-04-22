<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php";

global $APPLICATION;
global $USER;

$APPLICATION->AddHeadString('<link rel="icon" href="/local/templates/szcube/img/favicon.svg" type="image/svg+xml">', true);

if (!is_object($USER) || !$USER->IsAdmin()) {
    $APPLICATION->AuthForm("Доступ запрещен");
}

if (!CModule::IncludeModule("form")) {
    require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";
    echo '<div class="adm-info-message-wrap"><div class="adm-info-message">Модуль веб-форм не установлен.</div></div>';
    require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";
    return;
}

$formSid = function_exists("szcubeLeadGetFormSid") ? (string)szcubeLeadGetFormSid() : "SZCUBE_LEADS";
$formId = function_exists("szcubeLeadFindFormIdBySid") ? (int)szcubeLeadFindFormIdBySid($formSid) : 0;
$resultId = isset($_REQUEST["RESULT_ID"]) ? (int)$_REQUEST["RESULT_ID"] : 0;
$scope = isset($_REQUEST["scope"]) ? trim((string)$_REQUEST["scope"]) : "all";

$lead = null;
$errorMessage = "";

if ($formId <= 0) {
    $errorMessage = "Форма SZCUBE_LEADS не найдена.";
} elseif ($resultId <= 0) {
    $errorMessage = "Не передан RESULT_ID.";
} else {
    $resultRow = false;
    $resultRes = CFormResult::GetByID($resultId);
    if ($resultRes) {
        $resultRow = $resultRes->Fetch();
    }

    if (!$resultRow || (int)$resultRow["FORM_ID"] !== $formId) {
        $errorMessage = "Заявка не найдена.";
    } else {
        $lead = function_exists("szcubeLeadBuildResultData")
            ? szcubeLeadBuildResultData($resultId, $formId)
            : null;
        if (is_array($lead)) {
            $lead["STANDARD_URL"] = "/bitrix/admin/form_result_edit.php?lang=ru&WEB_FORM_ID=" . $formId . "&RESULT_ID=" . $resultId . "&WEB_FORM_NAME=" . urlencode($formSid);
            $lead["LIST_URL"] = "/bitrix/admin/szcube_leads.php?lang=ru&scope=" . urlencode($scope !== "" ? $scope : "all");
        } else {
            $errorMessage = "Не удалось загрузить данные заявки.";
        }
    }
}

$APPLICATION->SetTitle("Заявка SZCUBE");
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";
?>
<style>
    .szcube-lead-view {
        padding: 8px 0 24px;
    }

    .szcube-lead-view__toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .szcube-lead-view__title {
        margin: 0;
        font-size: 28px;
        line-height: 1.15;
        font-weight: 700;
    }

    .szcube-lead-view__actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .szcube-lead-view__button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 0 14px;
        border: 1px solid #c9d2df;
        background: #fff;
        text-decoration: none;
        color: #1f2d3d;
        border-radius: 4px;
        font-size: 13px;
        line-height: 1;
    }

    .szcube-lead-view__button:hover {
        border-color: #aebbd0;
    }

    .szcube-lead-view__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(240px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .szcube-lead-view__card {
        padding: 18px 20px;
        border: 1px solid #dfe5ed;
        background: #fff;
        border-radius: 4px;
    }

    .szcube-lead-view__card-title {
        margin: 0 0 14px;
        font-size: 18px;
        line-height: 1.2;
        font-weight: 700;
    }

    .szcube-lead-view__list {
        display: grid;
        gap: 12px;
    }

    .szcube-lead-view__row {
        display: grid;
        grid-template-columns: 180px 1fr;
        gap: 16px;
        align-items: start;
    }

    .szcube-lead-view__label {
        color: #617286;
        font-size: 13px;
        line-height: 1.4;
    }

    .szcube-lead-view__value {
        color: #1f2d3d;
        font-size: 14px;
        line-height: 1.45;
        word-break: break-word;
    }

    .szcube-lead-view__muted {
        color: #77869b;
    }

    @media (max-width: 900px) {
        .szcube-lead-view__grid {
            grid-template-columns: 1fr;
        }

        .szcube-lead-view__row {
            grid-template-columns: 1fr;
            gap: 4px;
        }
    }
</style>
<div class="szcube-lead-view">
    <div class="szcube-lead-view__toolbar">
        <h1 class="szcube-lead-view__title">Заявка SZCUBE</h1>
        <div class="szcube-lead-view__actions">
            <?php if ($lead): ?>
                <a class="szcube-lead-view__button" href="<?= htmlspecialcharsbx($lead["LIST_URL"]) ?>">Назад к списку</a>
                <a class="szcube-lead-view__button" href="<?= htmlspecialcharsbx($lead["STANDARD_URL"]) ?>">Открыть стандартную карточку</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($errorMessage !== ""): ?>
        <div class="adm-info-message-wrap">
            <div class="adm-info-message"><?= htmlspecialcharsbx($errorMessage) ?></div>
        </div>
    <?php else: ?>
        <div class="szcube-lead-view__grid">
            <div class="szcube-lead-view__card">
                <h2 class="szcube-lead-view__card-title">Основное</h2>
                <div class="szcube-lead-view__list">
                    <div class="szcube-lead-view__row">
                        <div class="szcube-lead-view__label">Имя</div>
                        <div class="szcube-lead-view__value"><?= htmlspecialcharsbx($lead["NAME"]) ?></div>
                    </div>
                    <div class="szcube-lead-view__row">
                        <div class="szcube-lead-view__label">Телефон</div>
                        <div class="szcube-lead-view__value"><?= htmlspecialcharsbx($lead["PHONE"]) ?></div>
                    </div>
                    <div class="szcube-lead-view__row">
                        <div class="szcube-lead-view__label">Детали заявки</div>
                        <div class="szcube-lead-view__value">
                            <?php if ($lead["LEAD_NOTE"] !== ""): ?>
                                <?= nl2br(htmlspecialcharsbx($lead["LEAD_NOTE"])) ?>
                            <?php else: ?>
                                <span class="szcube-lead-view__muted">Нет деталей</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="szcube-lead-view__card">
                <h2 class="szcube-lead-view__card-title">Служебная информация</h2>
                <div class="szcube-lead-view__list">
                    <div class="szcube-lead-view__row">
                        <div class="szcube-lead-view__label">ID</div>
                        <div class="szcube-lead-view__value"><?= (int)$lead["ID"] ?></div>
                    </div>
                    <div class="szcube-lead-view__row">
                        <div class="szcube-lead-view__label">Дата</div>
                        <div class="szcube-lead-view__value"><?= htmlspecialcharsbx($lead["TIMESTAMP_X"]) ?></div>
                    </div>
                    <div class="szcube-lead-view__row">
                        <div class="szcube-lead-view__label">Статус</div>
                        <div class="szcube-lead-view__value"><?= htmlspecialcharsbx($lead["STATUS_TITLE"]) ?></div>
                    </div>
                    <div class="szcube-lead-view__row">
                        <div class="szcube-lead-view__label">Страница</div>
                        <div class="szcube-lead-view__value">
                            <?php if ($lead["PAGE_URL"] !== ""): ?>
                                <a href="<?= htmlspecialcharsbx($lead["PAGE_URL"]) ?>" target="_blank"><?= htmlspecialcharsbx($lead["PAGE_URL"]) ?></a>
                            <?php else: ?>
                                <span class="szcube-lead-view__muted">Нет URL</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>
<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";

<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php";

global $APPLICATION;
global $USER;

if (!is_object($USER) || !$USER->IsAdmin()) {
    $APPLICATION->AuthForm("Доступ запрещен");
}

if (!CModule::IncludeModule("form")) {
    require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";
    echo '<div class="adm-info-message-wrap"><div class="adm-info-message">Модуль веб-форм не установлен.</div></div>';
    require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";
    return;
}

if (!function_exists("szcubeLeadsDashboardFindFormId")) {
    function szcubeLeadsDashboardFindFormId($formSid)
    {
        $formSid = trim((string)$formSid);
        if ($formSid === "") {
            return 0;
        }

        $res = CForm::GetBySID($formSid);
        if ($row = $res->Fetch()) {
            return (int)$row["ID"];
        }

        return 0;
    }
}

if (!function_exists("szcubeLeadsDashboardExtractAnswerValue")) {
    function szcubeLeadsDashboardExtractAnswerValue(array $answers, $sid)
    {
        if (!isset($answers[$sid]) || !is_array($answers[$sid])) {
            return "";
        }

        foreach ($answers[$sid] as $answer) {
            if (!is_array($answer)) {
                continue;
            }

            $valueCandidates = array(
                isset($answer["USER_TEXT"]) ? trim((string)$answer["USER_TEXT"]) : "",
                isset($answer["ANSWER_TEXT"]) ? trim((string)$answer["ANSWER_TEXT"]) : "",
                isset($answer["ANSWER_VALUE"]) ? trim((string)$answer["ANSWER_VALUE"]) : "",
                isset($answer["VALUE"]) ? trim((string)$answer["VALUE"]) : "",
            );

            foreach ($valueCandidates as $value) {
                if ($value !== "") {
                    return $value;
                }
            }
        }

        return "";
    }
}

$formSid = "SZCUBE_LEADS";
$formId = szcubeLeadsDashboardFindFormId($formSid);

$types = array(
    "all" => array(
        "title" => "Все заявки",
        "lead_type" => "",
        "description" => "Все лиды из формы SZCUBE_LEADS",
    ),
    "callback" => array(
        "title" => "Форма обратной связи",
        "lead_type" => "callback",
        "description" => "Общие обращения и обратный звонок",
    ),
    "apartments" => array(
        "title" => "Квартиры",
        "lead_type" => "apartment_reserve",
        "description" => "Заявки по квартирам",
    ),
    "storerooms" => array(
        "title" => "Кладовки",
        "lead_type" => "storeroom_reserve",
        "description" => "Заявки по кладовкам",
    ),
    "parking" => array(
        "title" => "Паркинги",
        "lead_type" => "parking_reserve",
        "description" => "Заявки по парковочным местам",
    ),
    "commerce" => array(
        "title" => "Коммерция",
        "lead_type" => "commerce_reserve",
        "description" => "Заявки по коммерческим помещениям",
    ),
);

$selectedScope = isset($_REQUEST["scope"]) ? trim((string)$_REQUEST["scope"]) : "all";
if (!isset($types[$selectedScope])) {
    $selectedScope = "all";
}
$selectedResultId = isset($_REQUEST["RESULT_ID"]) ? (int)$_REQUEST["RESULT_ID"] : 0;

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && $formId > 0
    && check_bitrix_sessid()
) {
    $action = isset($_POST["action"]) ? trim((string)$_POST["action"]) : "";
    $redirectScope = isset($_POST["scope"]) ? trim((string)$_POST["scope"]) : $selectedScope;
    if (!isset($types[$redirectScope])) {
        $redirectScope = "all";
    }

    $idsToDelete = array();

    if ($action === "delete" && isset($_POST["RESULT_ID"])) {
        $resultId = (int)$_POST["RESULT_ID"];
        if ($resultId > 0) {
            $idsToDelete[] = $resultId;
        }
    } elseif ($action === "bulk_delete" && isset($_POST["result_ids"]) && is_array($_POST["result_ids"])) {
        foreach ($_POST["result_ids"] as $rawId) {
            $resultId = (int)$rawId;
            if ($resultId > 0) {
                $idsToDelete[$resultId] = $resultId;
            }
        }
        $idsToDelete = array_values($idsToDelete);
    }

    if (!empty($idsToDelete)) {
        $deletedCount = 0;
        $failedCount = 0;

        foreach ($idsToDelete as $resultId) {
            $resultRes = CFormResult::GetByID($resultId);
            $resultRow = $resultRes ? $resultRes->Fetch() : false;
            if (!$resultRow || (int)$resultRow["FORM_ID"] !== $formId) {
                $failedCount++;
                continue;
            }

            if (CFormResult::Delete($resultId)) {
                $deletedCount++;
            } else {
                $failedCount++;
            }
        }

        $redirectUrl = "/bitrix/admin/szcube_leads.php?lang=ru&scope=" . urlencode($redirectScope);
        if ($deletedCount > 0 || $failedCount > 0) {
            $redirectUrl .= "&deleted=" . $deletedCount . "&failed=" . $failedCount;
        }
        LocalRedirect($redirectUrl);
    }
}

$resultsByScope = array();
foreach (array_keys($types) as $scopeCode) {
    $resultsByScope[$scopeCode] = array();
}

if ($formId > 0) {
    $by = "s_timestamp";
    $order = "desc";
    $isFiltered = false;
    $res = CFormResult::GetList($formId, $by, $order, array(), $isFiltered, "Y");

    while ($row = $res->Fetch()) {
        $resultId = (int)$row["ID"];
        if ($resultId <= 0) {
            continue;
        }

        $result = array();
        $answers = array();
        CFormResult::GetDataByID(
            $resultId,
            array("NAME", "PHONE", "LEAD_TYPE", "LEAD_SOURCE", "LEAD_NOTE", "PAGE_URL", "CONSENT"),
            $result,
            $answers
        );

        $leadType = szcubeLeadsDashboardExtractAnswerValue($answers, "LEAD_TYPE");
        $rowData = array(
            "ID" => $resultId,
            "TIMESTAMP_X" => isset($row["TIMESTAMP_X"]) ? (string)$row["TIMESTAMP_X"] : "",
            "STATUS_TITLE" => isset($row["STATUS_TITLE"]) ? (string)$row["STATUS_TITLE"] : "",
            "NAME" => szcubeLeadsDashboardExtractAnswerValue($answers, "NAME"),
            "PHONE" => szcubeLeadsDashboardExtractAnswerValue($answers, "PHONE"),
            "LEAD_TYPE" => $leadType,
            "LEAD_SOURCE" => szcubeLeadsDashboardExtractAnswerValue($answers, "LEAD_SOURCE"),
            "LEAD_NOTE" => szcubeLeadsDashboardExtractAnswerValue($answers, "LEAD_NOTE"),
            "PAGE_URL" => szcubeLeadsDashboardExtractAnswerValue($answers, "PAGE_URL"),
            "EDIT_URL" => "/bitrix/admin/szcube_leads.php?lang=ru&RESULT_ID=" . $resultId . "&scope=" . urlencode($selectedScope),
        );

        $resultsByScope["all"][] = $rowData;

        foreach ($types as $scopeCode => $scopeConfig) {
            if ($scopeCode === "all") {
                continue;
            }

            if ($scopeConfig["lead_type"] !== "" && $scopeConfig["lead_type"] === $leadType) {
                $resultsByScope[$scopeCode][] = $rowData;
                break;
            }
        }
    }
}

$selectedLead = null;
$selectedLeadError = "";

if ($selectedResultId > 0) {
    if ($formId <= 0) {
        $selectedLeadError = "Форма SZCUBE_LEADS не найдена.";
    } else {
        $resultRow = false;
        $resultRes = CFormResult::GetByID($selectedResultId);
        if ($resultRes) {
            $resultRow = $resultRes->Fetch();
        }

        if (!$resultRow || (int)$resultRow["FORM_ID"] !== $formId) {
            $selectedLeadError = "Заявка не найдена.";
        } else {
            $result = array();
            $answers = array();
            CFormResult::GetDataByID(
                $selectedResultId,
                array("NAME", "PHONE", "LEAD_TYPE", "LEAD_SOURCE", "LEAD_NOTE", "PAGE_URL", "CONSENT"),
                $result,
                $answers
            );

            $selectedLead = array(
                "ID" => $selectedResultId,
                "TIMESTAMP_X" => isset($resultRow["TIMESTAMP_X"]) ? (string)$resultRow["TIMESTAMP_X"] : "",
                "STATUS_TITLE" => isset($resultRow["STATUS_TITLE"]) ? (string)$resultRow["STATUS_TITLE"] : "",
                "NAME" => szcubeLeadsDashboardExtractAnswerValue($answers, "NAME"),
                "PHONE" => szcubeLeadsDashboardExtractAnswerValue($answers, "PHONE"),
                "LEAD_NOTE" => szcubeLeadsDashboardExtractAnswerValue($answers, "LEAD_NOTE"),
                "PAGE_URL" => szcubeLeadsDashboardExtractAnswerValue($answers, "PAGE_URL"),
                "STANDARD_URL" => "/bitrix/admin/form_result_edit.php?lang=ru&WEB_FORM_ID=" . $formId . "&RESULT_ID=" . $selectedResultId . "&WEB_FORM_NAME=SZCUBE_LEADS",
                "LIST_URL" => "/bitrix/admin/szcube_leads.php?lang=ru&scope=" . urlencode($selectedScope),
            );
        }
    }
}

$APPLICATION->SetTitle("Заявки SZCUBE");
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";

$deletedCount = isset($_GET["deleted"]) ? (int)$_GET["deleted"] : 0;
$failedCount = isset($_GET["failed"]) ? (int)$_GET["failed"] : 0;
?>
<style>
    .szcube-leads {
        padding: 8px 0 24px;
    }

    .szcube-leads__header {
        margin-bottom: 20px;
    }

    .szcube-leads__title {
        margin: 0 0 6px;
        font-size: 28px;
        line-height: 1.15;
        font-weight: 700;
    }

    .szcube-leads__desc {
        margin: 0;
        color: #5f6b7a;
        font-size: 14px;
        line-height: 1.45;
    }

    .szcube-leads__cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .szcube-leads__card {
        display: block;
        padding: 18px 20px;
        border: 1px solid #d9e0ea;
        background: #fff;
        text-decoration: none;
        color: #1f2d3d;
        border-radius: 4px;
        transition: border-color .2s ease, box-shadow .2s ease;
    }

    .szcube-leads__card:hover {
        border-color: #b9c6d6;
        box-shadow: 0 8px 24px rgba(31, 45, 61, .08);
    }

    .szcube-leads__card.is-active {
        border-color: #2d4b97;
        box-shadow: 0 8px 24px rgba(45, 75, 151, .12);
    }

    .szcube-leads__card-title {
        margin: 0 0 8px;
        font-size: 18px;
        line-height: 1.2;
        font-weight: 700;
    }

    .szcube-leads__card-meta {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-bottom: 8px;
    }

    .szcube-leads__card-count {
        font-size: 28px;
        line-height: 1;
        font-weight: 700;
        color: #2d4b97;
    }

    .szcube-leads__card-label {
        font-size: 12px;
        line-height: 1.2;
        color: #77869b;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .szcube-leads__card-desc {
        margin: 0;
        font-size: 13px;
        line-height: 1.45;
        color: #5f6b7a;
    }

    .szcube-leads__toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;
    }

    .szcube-leads__scope-title {
        margin: 0;
        font-size: 22px;
        line-height: 1.2;
        font-weight: 700;
    }

    .szcube-leads__actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .szcube-leads__button {
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
        cursor: pointer;
    }

    .szcube-leads__button:hover {
        border-color: #aebbd0;
    }

    .szcube-leads__button:disabled,
    .szcube-leads__button.is-disabled {
        opacity: .5;
        cursor: not-allowed;
        border-color: #cfd8e4;
        color: #7f8b9b;
    }

    .szcube-leads__button:disabled:hover,
    .szcube-leads__button.is-disabled:hover {
        border-color: #cfd8e4;
    }

    .szcube-leads__button--danger {
        color: #b02929;
        border-color: #e2b8b8;
    }

    .szcube-leads__button--danger:hover {
        border-color: #d39a9a;
    }

    .szcube-leads__action-form {
        margin: 0;
    }

    .szcube-leads__table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }

    .szcube-leads__table th,
    .szcube-leads__table td {
        padding: 12px 14px;
        border: 1px solid #dfe5ed;
        vertical-align: top;
        text-align: left;
    }

    .szcube-leads__table th {
        background: #f6f8fb;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #617286;
    }

    .szcube-leads__table td {
        font-size: 13px;
        line-height: 1.4;
    }

    .szcube-leads__muted {
        color: #77869b;
    }

    .szcube-leads__note {
        max-width: 440px;
        white-space: normal;
        word-break: break-word;
    }

    .szcube-leads__empty {
        padding: 28px;
        border: 1px dashed #cfd8e4;
        background: #fff;
        color: #617286;
        text-align: center;
    }

    .szcube-leads__bulkbar {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 12px;
    }

    .szcube-leads__checkbox-cell {
        width: 42px;
    }

    .szcube-leads__detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(240px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .szcube-leads__detail-card {
        padding: 18px 20px;
        border: 1px solid #dfe5ed;
        background: #fff;
        border-radius: 4px;
    }

    .szcube-leads__detail-card-title {
        margin: 0 0 14px;
        font-size: 18px;
        line-height: 1.2;
        font-weight: 700;
    }

    .szcube-leads__detail-list {
        display: grid;
        gap: 12px;
    }

    .szcube-leads__detail-row {
        display: grid;
        grid-template-columns: 140px 1fr;
        gap: 20px;
        align-items: start;
    }

    .szcube-leads__detail-label {
        color: #617286;
        font-size: 13px;
        line-height: 1.4;
    }

    .szcube-leads__detail-value {
        color: #1f2d3d;
        font-size: 14px;
        line-height: 1.45;
        word-break: break-word;
    }

    @media (max-width: 900px) {
        .szcube-leads__detail-grid {
            grid-template-columns: 1fr;
        }

        .szcube-leads__detail-row {
            grid-template-columns: 1fr;
            gap: 4px;
        }
    }
</style>
<div class="szcube-leads">
    <?php if ($deletedCount > 0 || $failedCount > 0): ?>
        <div class="adm-info-message-wrap">
            <div class="adm-info-message">
                <?php if ($deletedCount > 0): ?>
                    Удалено заявок: <?= $deletedCount ?>.
                <?php endif; ?>
                <?php if ($failedCount > 0): ?>
                    Не удалось удалить: <?= $failedCount ?>.
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="szcube-leads__header">
        <h1 class="szcube-leads__title">Заявки SZCUBE</h1>
        <p class="szcube-leads__desc">Единая точка входа по лидам из формы <strong>SZCUBE_LEADS</strong>. Здесь заявки разложены по типам без путаницы со старыми формами.</p>
    </div>

    <?php if ($selectedResultId > 0): ?>
        <div class="szcube-leads__toolbar">
            <h2 class="szcube-leads__scope-title">Заявка SZCUBE</h2>
            <div class="szcube-leads__actions">
                <a class="szcube-leads__button" href="/bitrix/admin/szcube_leads.php?lang=ru&scope=<?= htmlspecialcharsbx($selectedScope) ?>">Назад к списку</a>
                <?php if ($selectedLead): ?>
                    <form class="szcube-leads__action-form" method="post" action="/bitrix/admin/szcube_leads.php?lang=ru&scope=<?= htmlspecialcharsbx($selectedScope) ?>" onsubmit="return confirm('Удалить заявку #<?= (int)$selectedLead["ID"] ?>?');">
                        <?= bitrix_sessid_post() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="scope" value="<?= htmlspecialcharsbx($selectedScope) ?>">
                        <input type="hidden" name="RESULT_ID" value="<?= (int)$selectedLead["ID"] ?>">
                        <button type="submit" class="szcube-leads__button szcube-leads__button--danger">Удалить заявку</button>
                    </form>
                <?php endif; ?>
                <?php if ($selectedLead): ?>
                    <a class="szcube-leads__button" href="<?= htmlspecialcharsbx($selectedLead["STANDARD_URL"]) ?>">Открыть стандартную карточку</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($selectedLeadError !== ""): ?>
            <div class="adm-info-message-wrap">
                <div class="adm-info-message"><?= htmlspecialcharsbx($selectedLeadError) ?></div>
            </div>
        <?php else: ?>
            <div class="szcube-leads__detail-grid">
                <div class="szcube-leads__detail-card">
                    <h2 class="szcube-leads__detail-card-title">Основное</h2>
                    <div class="szcube-leads__detail-list">
                        <div class="szcube-leads__detail-row">
                            <div class="szcube-leads__detail-label">Имя</div>
                            <div class="szcube-leads__detail-value"><?= htmlspecialcharsbx($selectedLead["NAME"]) ?></div>
                        </div>
                        <div class="szcube-leads__detail-row">
                            <div class="szcube-leads__detail-label">Телефон</div>
                            <div class="szcube-leads__detail-value"><?= htmlspecialcharsbx($selectedLead["PHONE"]) ?></div>
                        </div>
                        <div class="szcube-leads__detail-row">
                            <div class="szcube-leads__detail-label">Детали заявки</div>
                            <div class="szcube-leads__detail-value">
                                <?php if ($selectedLead["LEAD_NOTE"] !== ""): ?>
                                    <?= nl2br(htmlspecialcharsbx($selectedLead["LEAD_NOTE"])) ?>
                                <?php else: ?>
                                    <span class="szcube-leads__muted">Нет деталей</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="szcube-leads__detail-card">
                    <h2 class="szcube-leads__detail-card-title">Служебная информация</h2>
                    <div class="szcube-leads__detail-list">
                        <div class="szcube-leads__detail-row">
                            <div class="szcube-leads__detail-label">ID</div>
                            <div class="szcube-leads__detail-value"><?= (int)$selectedLead["ID"] ?></div>
                        </div>
                        <div class="szcube-leads__detail-row">
                            <div class="szcube-leads__detail-label">Дата</div>
                            <div class="szcube-leads__detail-value"><?= htmlspecialcharsbx($selectedLead["TIMESTAMP_X"]) ?></div>
                        </div>
                        <div class="szcube-leads__detail-row">
                            <div class="szcube-leads__detail-label">Статус</div>
                            <div class="szcube-leads__detail-value"><?= htmlspecialcharsbx($selectedLead["STATUS_TITLE"]) ?></div>
                        </div>
                        <div class="szcube-leads__detail-row">
                            <div class="szcube-leads__detail-label">Страница</div>
                            <div class="szcube-leads__detail-value">
                                <?php if ($selectedLead["PAGE_URL"] !== ""): ?>
                                    <a href="<?= htmlspecialcharsbx($selectedLead["PAGE_URL"]) ?>" target="_blank"><?= htmlspecialcharsbx($selectedLead["PAGE_URL"]) ?></a>
                                <?php else: ?>
                                    <span class="szcube-leads__muted">Нет URL</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="szcube-leads__cards">
            <?php foreach ($types as $scopeCode => $scopeConfig): ?>
                <a class="szcube-leads__card<?= $scopeCode === $selectedScope ? ' is-active' : '' ?>" href="/bitrix/admin/szcube_leads.php?lang=ru&scope=<?= htmlspecialcharsbx($scopeCode) ?>">
                    <h2 class="szcube-leads__card-title"><?= htmlspecialcharsbx($scopeConfig["title"]) ?></h2>
                    <div class="szcube-leads__card-meta">
                        <span class="szcube-leads__card-count"><?= count($resultsByScope[$scopeCode]) ?></span>
                        <span class="szcube-leads__card-label">заявок</span>
                    </div>
                    <p class="szcube-leads__card-desc"><?= htmlspecialcharsbx($scopeConfig["description"]) ?></p>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="szcube-leads__toolbar">
            <h2 class="szcube-leads__scope-title"><?= htmlspecialcharsbx($types[$selectedScope]["title"]) ?></h2>
            <div class="szcube-leads__actions">
                <?php if ($formId > 0): ?>
                    <a class="szcube-leads__button" href="/bitrix/admin/form_result_list.php?lang=ru&WEB_FORM_ID=<?= $formId ?>">Открыть список формы</a>
                <?php endif; ?>
                <a class="szcube-leads__button" href="/bitrix/admin/szcube_leads.php?lang=ru&scope=all">Сбросить фильтр</a>
            </div>
        </div>

        <?php $currentItems = $resultsByScope[$selectedScope]; ?>
        <?php if (empty($currentItems)): ?>
            <div class="szcube-leads__empty">По выбранному типу заявок пока нет.</div>
        <?php else: ?>
            <form id="szcube-leads-bulk-form" method="post" action="/bitrix/admin/szcube_leads.php?lang=ru&scope=<?= htmlspecialcharsbx($selectedScope) ?>">
                <?= bitrix_sessid_post() ?>
                <input type="hidden" name="action" value="bulk_delete">
                <input type="hidden" name="scope" value="<?= htmlspecialcharsbx($selectedScope) ?>">

                <div class="szcube-leads__bulkbar">
                    <button id="szcube-leads-bulk-delete" type="submit" class="szcube-leads__button szcube-leads__button--danger" disabled>Удалить выбранные</button>
                </div>

                <table class="szcube-leads__table">
                    <thead>
                    <tr>
                        <th class="szcube-leads__checkbox-cell"></th>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Детали</th>
                        <th>Страница</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($currentItems as $item): ?>
                        <tr>
                            <td class="szcube-leads__checkbox-cell"><input type="checkbox" name="result_ids[]" value="<?= (int)$item["ID"] ?>"></td>
                            <td><a href="<?= htmlspecialcharsbx($item["EDIT_URL"]) ?>"><?= (int)$item["ID"] ?></a></td>
                            <td><?= htmlspecialcharsbx($item["TIMESTAMP_X"]) ?></td>
                            <td><?= htmlspecialcharsbx($item["NAME"]) ?></td>
                            <td><?= htmlspecialcharsbx($item["PHONE"]) ?></td>
                            <td class="szcube-leads__note"><?= htmlspecialcharsbx($item["LEAD_NOTE"]) !== "" ? htmlspecialcharsbx($item["LEAD_NOTE"]) : '<span class="szcube-leads__muted">Нет деталей</span>' ?></td>
                            <td class="szcube-leads__note">
                                <?php if ($item["PAGE_URL"] !== ""): ?>
                                    <a href="<?= htmlspecialcharsbx($item["PAGE_URL"]) ?>" target="_blank"><?= htmlspecialcharsbx($item["PAGE_URL"]) ?></a>
                                <?php else: ?>
                                    <span class="szcube-leads__muted">Нет URL</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <script>
                (function() {
                    var form = document.getElementById('szcube-leads-bulk-form');
                    if (!form) {
                        return;
                    }

                    var submitButton = document.getElementById('szcube-leads-bulk-delete');
                    var checkboxes = form.querySelectorAll('input[name="result_ids[]"]');

                    function updateBulkDeleteState() {
                        var hasChecked = false;
                        checkboxes.forEach(function(checkbox) {
                            if (checkbox.checked) {
                                hasChecked = true;
                            }
                        });

                        submitButton.disabled = !hasChecked;
                    }

                    checkboxes.forEach(function(checkbox) {
                        checkbox.addEventListener('change', updateBulkDeleteState);
                    });

                    form.addEventListener('submit', function(event) {
                        var hasChecked = false;
                        checkboxes.forEach(function(checkbox) {
                            if (checkbox.checked) {
                                hasChecked = true;
                            }
                        });

                        if (!hasChecked) {
                            event.preventDefault();
                            alert('Не выбрано ни одной заявки');
                            updateBulkDeleteState();
                            return false;
                        }

                        if (!confirm('Удалить выбранные заявки?')) {
                            event.preventDefault();
                            return false;
                        }
                    });

                    updateBulkDeleteState();
                })();
            </script>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";

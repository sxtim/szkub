<?php
if (!defined("ADMIN_MODULE_NAME")) {
    return false;
}

$aMenu = array(
    "parent_menu" => "global_menu_services",
    "section" => "szcube_leads",
    "sort" => 1500,
    "text" => "Заявки SZCUBE",
    "title" => "Заявки SZCUBE",
    "icon" => "form_menu_icon",
    "page_icon" => "form_page_icon",
    "items_id" => "menu_szcube_leads",
    "more_url" => array(
        "/bitrix/admin/szcube_leads.php",
    ),
    "items" => array(
        array(
            "text" => "Все заявки",
            "title" => "Все заявки",
            "url" => "/bitrix/admin/szcube_leads.php?lang=ru&scope=all",
            "more_url" => array(
                "/bitrix/admin/szcube_leads.php",
            ),
        ),
        array(
            "text" => "Форма обратной связи",
            "title" => "Форма обратной связи",
            "url" => "/bitrix/admin/szcube_leads.php?lang=ru&scope=callback",
            "more_url" => array(
                "/bitrix/admin/szcube_leads.php",
            ),
        ),
        array(
            "text" => "Квартиры",
            "title" => "Заявки по квартирам",
            "url" => "/bitrix/admin/szcube_leads.php?lang=ru&scope=apartments",
            "more_url" => array(
                "/bitrix/admin/szcube_leads.php",
            ),
        ),
        array(
            "text" => "Кладовки",
            "title" => "Заявки по кладовкам",
            "url" => "/bitrix/admin/szcube_leads.php?lang=ru&scope=storerooms",
            "more_url" => array(
                "/bitrix/admin/szcube_leads.php",
            ),
        ),
        array(
            "text" => "Паркинги",
            "title" => "Заявки по паркингам",
            "url" => "/bitrix/admin/szcube_leads.php?lang=ru&scope=parking",
            "more_url" => array(
                "/bitrix/admin/szcube_leads.php",
            ),
        ),
        array(
            "text" => "Коммерция",
            "title" => "Заявки по коммерции",
            "url" => "/bitrix/admin/szcube_leads.php?lang=ru&scope=commerce",
            "more_url" => array(
                "/bitrix/admin/szcube_leads.php",
            ),
        ),
    ),
);

return $aMenu;

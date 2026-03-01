<?php
/**
 * Источник для одноразового импорта ЖК в инфоблок `projects`.
 */

if (!defined("SITE_TEMPLATE_PATH")) {
	define("SITE_TEMPLATE_PATH", "/local/templates/szcube");
}

return array(
	array(
		"code" => "kollekciya",
		"title" => "Коллекция",
		"active_from" => "2026-02-24",
		"preview" => "ЖК «Коллекция» — клубный формат в центре Воронежа.",
		"image" => SITE_TEMPLATE_PATH . "/img/photo_5467741080506797884_y.jpg",
		"class_label" => "Бизнес",
		"tag_label" => "Скидки 5%",
		"address" => "г. Воронеж, ул. Жилина 7",
		"delivery_text" => "III квартал 2026г.",
		"rooms_in_sale" => array("Студия", "1к", "2к", "3к"),
		"sale_count_text" => "173 квартиры",
		"price_from_text" => "от 6 538 000 р.",
	),
	array(
		"code" => "vertical",
		"title" => "Вертикаль",
		"active_from" => "2026-02-24",
		"preview" => "ЖК «Вертикаль» — современный формат для комфортной городской жизни.",
		"image" => SITE_TEMPLATE_PATH . "/img/figma-6c3f203f-be9a-4001-ab97-edc7f3b4a9e3.png",
		"class_label" => "Комфорт +",
		"tag_label" => "527 квартир",
		"address" => "г. Воронеж, ул. Фронтовая 5",
		"delivery_text" => "III квартал 2027г.",
		"rooms_in_sale" => array("Студия", "1к", "2к", "3к"),
		"sale_count_text" => "Скоро продажи",
		"price_from_text" => "",
	),
	array(
		"code" => "krasnoznamennaya",
		"title" => "Краснознаменная",
		"active_from" => "2026-02-24",
		"preview" => "ЖК «Краснознаменная» — проект в стадии подготовки к продажам.",
		"image" => SITE_TEMPLATE_PATH . "/img/figma-6c3f203f-be9a-4001-ab97-edc7f3b4a9e3.png",
		"class_label" => "Бизнес",
		"tag_label" => "Скоро",
		"address" => "г. Воронеж, ул. Краснознаменная",
		"delivery_text" => "I квартал 2028г.",
		"rooms_in_sale" => array("Студия", "1к", "2к", "3к"),
		"sale_count_text" => "В проекте",
		"price_from_text" => "",
	),
);


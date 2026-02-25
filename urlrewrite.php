<?
$arUrlRewrite = array(
	array(
		"CONDITION"	=>	"#^/services/#",
		"RULE"	=>	"",
		"ID"	=>	"bitrix:catalog",
		"PATH"	=>	"/services/index.php",
	),
	array(
		"CONDITION"	=>	"#^/products/#",
		"RULE"	=>	"",
		"ID"	=>	"bitrix:catalog",
		"PATH"	=>	"/products/index.php",
	),
	array(
		"CONDITION"	=>	"#^/news/#",
		"RULE"	=>	"",
		"ID"	=>	"bitrix:news",
		"PATH"	=>	"/news/index.php",
	),
	array(
		"CONDITION"	=>	"#^/projects/([^/]+?)/?$#",
		"RULE"	=>	"code=$1",
		"ID"	=>	"",
		"PATH"	=>	"/projects/detail.php",
	),
	array(
		"CONDITION"	=>	"#^/projects/#",
		"RULE"	=>	"",
		"ID"	=>	"",
		"PATH"	=>	"/projects/index.php",
	),
	array(
		"CONDITION"	=>	"#^/promotions/#",
		"RULE"	=>	"",
		"ID"	=>	"bitrix:news",
		"PATH"	=>	"/promotions/index.php",
	),
);

?>

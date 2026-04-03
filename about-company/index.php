<?php
define("ABOUT_COMPANY_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("О компании");
$APPLICATION->SetPageProperty("title", "О компании — КУБ");

$aboutCompanyHero = array(
  "paragraphs" => array(
    "КУБ комплексно развивает жилые проекты в Воронеже, проектируя не только дома, но и полноценную среду для жизни. Мы соединяем архитектуру, экономику проекта, строительную экспертизу и понятный клиентский продукт в одной девелоперской системе.",
    "Для нас важны не отдельные квадратные метры, а качество городской жизни: сценарии двора, планировочные решения, инфраструктура повседневной жизни и уверенность покупателя в результате.",
  ),
  "image" => SITE_TEMPLATE_PATH . "/img/projects/image_15.jpg",
  "image_alt" => "Жилой комплекс КУБ",
);

$aboutCompanyAwards = array(
  array(
    "logo" => "ЕРЗ",
    "caption" => "Рейтинг ЕРЗ",
  ),
  array(
    "logo" => "ДОМ.РФ",
    "caption" => "Отраслевой стандарт",
  ),
  array(
    "logo" => "ТОП 3",
    "caption" => "Федеральный уровень",
  ),
);

$aboutCompanyProjectsSectionTitle = "Наши проекты";

$aboutCompanySaleBlock = array(
  "title" => "Проекты в продаже",
  "description" => "Карточки жилых комплексов выводим в том же формате, что и в общем каталоге: с классом проекта, сроком сдачи, форматом квартир и текущим статусом продаж.",
  "contact_title" => "Подберем проект и расскажем об условиях покупки",
  "contact_text" => "Оставьте контакт, и команда КУБ свяжется с вами, чтобы подобрать подходящий жилой комплекс, рассказать о форматах квартир, сроках ввода и актуальных предложениях.",
  "background_image" => SITE_TEMPLATE_PATH . "/img/projects/div.image-lazy__image.jpg",
);

$aboutCompanySocialBlock = array(
  "intro" => array(
    "title" => "КУБ — социально ответственный девелопер",
    "text" => "Строительная компания «КУБ» уделяет большое внимание развитию социальной инфраструктуры в жилых проектах. Мы закладываем в продукт не только архитектуру и квартиры, но и сценарии повседневной жизни: безопасные дворы, детские маршруты, озеленение, удобные входные группы и общественные пространства.",
    "watermark" => SITE_TEMPLATE_PATH . "/img/figma-f0c695eb-93ca-42c4-81da-32adaa050abc.svg",
  ),
  "metric" => array(
    "title" => "9575 мест",
    "text" => "В детских садах, школах и сопутствующей инфраструктуре, которую закладываем в проектах и территориях развития.",
    "image" => SITE_TEMPLATE_PATH . "/img/tenders/people-working2.jpg",
    "alt" => "Социальная инфраструктура КУБ",
  ),
  "material" => array(
    "title" => "Используем экологически чистые и безопасные строительные материалы",
    "text" => "Выбираем решения по долговечности, энергоэффективности и комфорту среды.",
    "image" => SITE_TEMPLATE_PATH . "/img/photo_5467741080506797884_y.jpg",
    "alt" => "Архитектура и материалы КУБ",
  ),
  "progress" => array(
    "title" => "Готовы 5 школ и 10 детских садов",
    "text" => "Строятся новые социальные объекты, дворовые пространства и инфраструктура ежедневного маршрута жителей.",
  ),
);

$aboutCompanySocialGalleryColumns = array(
  array(
    "viewport_class" => "",
    "duration" => "18s",
    "items" => array(
      array(
        "image" => SITE_TEMPLATE_PATH . "/img/projects/div.image-lazy__image.jpg",
        "alt" => "Благоустроенный двор",
        "text" => "Благоустраиваем дворы",
        "height" => 212,
      ),
      array(
        "image" => SITE_TEMPLATE_PATH . "/img/news/11_43_39.png",
        "alt" => "Входные группы",
        "text" => "Продумываем входные группы",
        "height" => 134,
      ),
      array(
        "image" => SITE_TEMPLATE_PATH . "/img/promotions/photo_2026-02-25_01-16-59.jpg",
        "alt" => "Озеленение территории",
        "text" => "Формируем зеленый контур",
        "height" => 176,
      ),
    ),
  ),
  array(
    "viewport_class" => "about-company-social-gallery__viewport--offset",
    "duration" => "22s",
    "items" => array(
      array(
        "image" => SITE_TEMPLATE_PATH . "/img/promotions/photo_2026-02-25_01-18-25.jpg",
        "alt" => "Социальная среда для детей",
        "text" => "Среда для детей",
        "height" => 118,
      ),
      array(
        "image" => SITE_TEMPLATE_PATH . "/img/news/11_49_02.png",
        "alt" => "Детский сад рядом с домом",
        "text" => "Детский сад рядом с домом",
        "height" => 192,
      ),
      array(
        "image" => SITE_TEMPLATE_PATH . "/img/promotions/photo_2026-02-25_01-19-53.jpg",
        "alt" => "Общественные пространства",
        "text" => "Общественные пространства",
        "height" => 148,
      ),
    ),
  ),
);

$aboutCompanyProjectsFallback = array(
  array(
    "code" => "kollekciya",
    "title" => "Жилой комплекс Коллекция",
    "status" => "building",
    "status_label" => "В продаже",
    "thumb_image" => SITE_TEMPLATE_PATH . "/img/projects/kollecttsiya-mobile.jpg",
    "thumb_alt" => "ЖК Коллекция",
    "detail_image" => SITE_TEMPLATE_PATH . "/img/photo_5467741080506797884_y.jpg",
    "detail_alt" => "Жилой комплекс Коллекция",
    "description" => array(
      "Коллекция — квартал бизнес-класса, в котором мы делаем ставку на архитектурную цельность, спокойную дворовую среду и продуманные ежедневные сценарии для семейной жизни.",
      "Проект находится в активной стадии строительства: параллельно ведем проработку фасадов, благоустройства и общественных пространств, чтобы продукт собирался как единая городская среда.",
    ),
  ),
  array(
    "code" => "vertical",
    "title" => "Жилой комплекс Вертикаль",
    "status" => "planned",
    "status_label" => "Скоро в продаже",
    "thumb_image" => SITE_TEMPLATE_PATH . "/img/figma-6c3f203f-be9a-4001-ab97-edc7f3b4a9e3.png",
    "thumb_alt" => "ЖК Вертикаль",
    "detail_image" => SITE_TEMPLATE_PATH . "/img/projects/div.image-lazy__image.jpg",
    "detail_alt" => "Концепция жилого комплекса Вертикаль",
    "description" => array(
      "Вертикаль — проект следующего этапа развития. Здесь закладываем выразительный силуэт, панорамные виды и компактную типологию жилья с современной инженерной логикой.",
      "Сейчас проект находится в стадии проектной проработки: уточняем квартирографию, общественные зоны и транспортные сценарии вокруг комплекса.",
    ),
  ),
  array(
    "code" => "krasnoznamennaya",
    "title" => "Жилой комплекс Краснознаменная",
    "status" => "completed",
    "status_label" => "Реализован",
    "thumb_image" => SITE_TEMPLATE_PATH . "/img/projects/image_15.jpg",
    "thumb_alt" => "Реализованный проект КУБ",
    "detail_image" => SITE_TEMPLATE_PATH . "/img/figma-e344372e-8660-419b-8537-aa79b5b36e9f.jpg",
    "detail_alt" => "Реализованный жилой комплекс",
    "description" => array(
      "Реализованные проекты для нас — это проверка решений в реальной эксплуатации: как работает благоустройство, насколько устойчивы материалы и как жители проживают среду каждый день.",
      "Этот кейс показывает подход КУБ к готовому продукту: аккуратная архитектура, завершенный двор и понятная инфраструктура без перегруженности деталями.",
    ),
  ),
);

$aboutCompanyProjectsFallbackByCode = array();
foreach ($aboutCompanyProjectsFallback as $aboutCompanyProjectFallbackItem) {
  if (!empty($aboutCompanyProjectFallbackItem["code"])) {
    $aboutCompanyProjectsFallbackByCode[$aboutCompanyProjectFallbackItem["code"]] = $aboutCompanyProjectFallbackItem;
  }
}
unset($aboutCompanyProjectFallbackItem);

if (!function_exists("aboutCompanyPropertyScalar")) {
  function aboutCompanyPropertyScalar($properties, $code, $default = "")
  {
    if (!is_array($properties) || !isset($properties[$code])) {
      return (string)$default;
    }

    $value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : "";
    if (is_array($value)) {
      $value = reset($value);
    }

    $value = trim((string)$value);
    return $value !== "" ? $value : (string)$default;
  }
}

if (!function_exists("aboutCompanyPropertyFileUrl")) {
  function aboutCompanyPropertyFileUrl($properties, $code, $default = "")
  {
    if (!is_array($properties) || !isset($properties[$code])) {
      return (string)$default;
    }

    $value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : 0;
    if (is_array($value)) {
      $value = reset($value);
    }

    $fileId = (int)$value;
    if ($fileId <= 0) {
      return (string)$default;
    }

    $filePath = CFile::GetPath($fileId);
    return $filePath ? (string)$filePath : (string)$default;
  }
}

if (!function_exists("aboutCompanyPropertyEnumXmlId")) {
  function aboutCompanyPropertyEnumXmlId($properties, $code, $default = "")
  {
    if (!is_array($properties) || !isset($properties[$code])) {
      return (string)$default;
    }

    $value = isset($properties[$code]["VALUE_XML_ID"]) ? $properties[$code]["VALUE_XML_ID"] : "";
    if (is_array($value)) {
      $value = reset($value);
    }

    $value = trim((string)$value);
    return $value !== "" ? $value : (string)$default;
  }
}

if (!function_exists("aboutCompanyPropertyEnumText")) {
  function aboutCompanyPropertyEnumText($properties, $code, $default = "")
  {
    if (!is_array($properties) || !isset($properties[$code])) {
      return (string)$default;
    }

    $value = isset($properties[$code]["VALUE_ENUM"]) ? $properties[$code]["VALUE_ENUM"] : "";
    if (is_array($value)) {
      $value = reset($value);
    }
    if (trim((string)$value) === "") {
      $value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : "";
      if (is_array($value)) {
        $value = reset($value);
      }
    }

    $value = trim((string)$value);
    return $value !== "" ? $value : (string)$default;
  }
}

if (!function_exists("aboutCompanyProjectShouldShow")) {
  function aboutCompanyProjectShouldShow($properties)
  {
    $xmlId = mb_strtoupper(aboutCompanyPropertyEnumXmlId($properties, "ABOUT_COMPANY_SHOW", ""));
    $value = mb_strtoupper(aboutCompanyPropertyEnumText($properties, "ABOUT_COMPANY_SHOW", ""));

    return in_array($xmlId, array("Y", "YES", "1", "TRUE"), true)
      || in_array($value, array("ДА", "Y", "YES", "1", "TRUE"), true);
  }
}

if (!function_exists("aboutCompanySaleProjectShouldShow")) {
  function aboutCompanySaleProjectShouldShow($properties)
  {
    $xmlId = mb_strtoupper(aboutCompanyPropertyEnumXmlId($properties, "ABOUT_COMPANY_SALE_SHOW", ""));
    $value = mb_strtoupper(aboutCompanyPropertyEnumText($properties, "ABOUT_COMPANY_SALE_SHOW", ""));

    return in_array($xmlId, array("Y", "YES", "1", "TRUE"), true)
      || in_array($value, array("ДА", "Y", "YES", "1", "TRUE"), true);
  }
}

if (!function_exists("aboutCompanyProjectTitle")) {
  function aboutCompanyProjectTitle($name)
  {
    $name = trim((string)$name);
    if ($name === "") {
      return "";
    }

    if (strpos($name, "Жилой комплекс") === 0) {
      return $name;
    }

    return "Жилой комплекс " . $name;
  }
}

$projectsIblockType = "";
$projectsIblockCode = "projects";
$projectsIblockId = 0;
$aboutCompanySaleProjectIds = null;
$aboutCompanyPageIblockCode = "about_company_page";
$aboutCompanyPageIblockId = 0;
$aboutCompanyGalleryIblockCode = "about_company_social_gallery";
$aboutCompanyGalleryIblockId = 0;
$aboutCompanyProjects = $aboutCompanyProjectsFallback;
if (class_exists("\\Bitrix\\Main\\Loader") && \Bitrix\Main\Loader::includeModule("iblock")) {
  $iblockRes = CIBlock::GetList(
    array(),
    array(
      "=CODE" => $projectsIblockCode,
      "ACTIVE" => "Y",
    ),
    false
  );

  if ($iblock = $iblockRes->Fetch()) {
    $projectsIblockId = (int)$iblock["ID"];
    $projectsIblockType = (string)$iblock["IBLOCK_TYPE_ID"];
  }

  if ($projectsIblockId > 0) {
    $aboutCompanySaleProjectIds = array();
    $saleProjectRes = CIBlockElement::GetList(
      array(
        "SORT" => "ASC",
        "NAME" => "ASC",
      ),
      array(
        "IBLOCK_ID" => $projectsIblockId,
        "ACTIVE" => "Y",
      ),
      false,
      false,
      array(
        "ID",
        "IBLOCK_ID",
      )
    );

    while ($saleProjectElement = $saleProjectRes->GetNextElement()) {
      $saleProjectFields = $saleProjectElement->GetFields();
      $saleProjectProperties = $saleProjectElement->GetProperties();
      if (!aboutCompanySaleProjectShouldShow($saleProjectProperties)) {
        continue;
      }

      $aboutCompanySaleProjectIds[] = (int)$saleProjectFields["ID"];
    }
  }

  $aboutCompanyPageIblockRes = CIBlock::GetList(
    array(),
    array(
      "=CODE" => $aboutCompanyPageIblockCode,
      "ACTIVE" => "Y",
    ),
    false
  );
  if ($aboutCompanyPageIblock = $aboutCompanyPageIblockRes->Fetch()) {
    $aboutCompanyPageIblockId = (int)$aboutCompanyPageIblock["ID"];
  }

  $aboutCompanyGalleryIblockRes = CIBlock::GetList(
    array(),
    array(
      "=CODE" => $aboutCompanyGalleryIblockCode,
      "ACTIVE" => "Y",
    ),
    false
  );
  if ($aboutCompanyGalleryIblock = $aboutCompanyGalleryIblockRes->Fetch()) {
    $aboutCompanyGalleryIblockId = (int)$aboutCompanyGalleryIblock["ID"];
  }

  if ($aboutCompanyPageIblockId > 0) {
    $aboutCompanyPageRes = CIBlockElement::GetList(
      array(
        "SORT" => "ASC",
        "ID" => "ASC",
      ),
      array(
        "IBLOCK_ID" => $aboutCompanyPageIblockId,
        "ACTIVE" => "Y",
      ),
      false,
      array("nTopCount" => 1),
      array(
        "ID",
        "IBLOCK_ID",
        "NAME",
        "CODE",
      )
    );
    if ($aboutCompanyPageElement = $aboutCompanyPageRes->GetNextElement()) {
      $aboutCompanyPageFields = $aboutCompanyPageElement->GetFields();
      $aboutCompanyPageProperties = $aboutCompanyPageElement->GetProperties();

      $aboutCompanyHero["paragraphs"] = array_values(array_filter(array(
        aboutCompanyPropertyScalar($aboutCompanyPageProperties, "HERO_TEXT_1", isset($aboutCompanyHero["paragraphs"][0]) ? $aboutCompanyHero["paragraphs"][0] : ""),
        aboutCompanyPropertyScalar($aboutCompanyPageProperties, "HERO_TEXT_2", isset($aboutCompanyHero["paragraphs"][1]) ? $aboutCompanyHero["paragraphs"][1] : ""),
      )));
      $aboutCompanyHero["image"] = aboutCompanyPropertyFileUrl($aboutCompanyPageProperties, "HERO_IMAGE", $aboutCompanyHero["image"]);

      $aboutCompanyAwardsDynamic = array();
      for ($awardIndex = 1; $awardIndex <= 3; $awardIndex++) {
        $fallbackAward = isset($aboutCompanyAwards[$awardIndex - 1]) ? $aboutCompanyAwards[$awardIndex - 1] : array();
        $awardLogo = aboutCompanyPropertyScalar(
          $aboutCompanyPageProperties,
          "AWARD_" . $awardIndex . "_LOGO",
          isset($fallbackAward["logo"]) ? $fallbackAward["logo"] : ""
        );
        $awardCaption = aboutCompanyPropertyScalar(
          $aboutCompanyPageProperties,
          "AWARD_" . $awardIndex . "_CAPTION",
          isset($fallbackAward["caption"]) ? $fallbackAward["caption"] : ""
        );
        if ($awardLogo === "" && $awardCaption === "") {
          continue;
        }
        $aboutCompanyAwardsDynamic[] = array(
          "logo" => $awardLogo,
          "caption" => $awardCaption,
        );
      }
      if (!empty($aboutCompanyAwardsDynamic)) {
        $aboutCompanyAwards = $aboutCompanyAwardsDynamic;
      }

      $aboutCompanySocialBlock["intro"]["title"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SOCIAL_INTRO_TITLE", $aboutCompanySocialBlock["intro"]["title"]);
      $aboutCompanySocialBlock["intro"]["text"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SOCIAL_INTRO_TEXT", $aboutCompanySocialBlock["intro"]["text"]);
      $aboutCompanySocialBlock["metric"]["title"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SOCIAL_METRIC_TITLE", $aboutCompanySocialBlock["metric"]["title"]);
      $aboutCompanySocialBlock["metric"]["text"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SOCIAL_METRIC_TEXT", $aboutCompanySocialBlock["metric"]["text"]);
      $aboutCompanySocialBlock["metric"]["image"] = aboutCompanyPropertyFileUrl($aboutCompanyPageProperties, "SOCIAL_METRIC_IMAGE", $aboutCompanySocialBlock["metric"]["image"]);
      $aboutCompanySocialBlock["metric"]["alt"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SOCIAL_METRIC_ALT", $aboutCompanySocialBlock["metric"]["alt"]);
      $aboutCompanySocialBlock["material"]["title"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SOCIAL_MATERIAL_TITLE", $aboutCompanySocialBlock["material"]["title"]);
      $aboutCompanySocialBlock["material"]["text"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SOCIAL_MATERIAL_TEXT", $aboutCompanySocialBlock["material"]["text"]);
      $aboutCompanySocialBlock["material"]["image"] = aboutCompanyPropertyFileUrl($aboutCompanyPageProperties, "SOCIAL_MATERIAL_IMAGE", $aboutCompanySocialBlock["material"]["image"]);
      $aboutCompanySocialBlock["material"]["alt"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SOCIAL_MATERIAL_ALT", $aboutCompanySocialBlock["material"]["alt"]);
      $aboutCompanySocialBlock["progress"]["title"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SOCIAL_PROGRESS_TITLE", $aboutCompanySocialBlock["progress"]["title"]);
      $aboutCompanySocialBlock["progress"]["text"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SOCIAL_PROGRESS_TEXT", $aboutCompanySocialBlock["progress"]["text"]);

      $aboutCompanyProjectsSectionTitle = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "PROJECTS_TITLE", $aboutCompanyProjectsSectionTitle);
      $aboutCompanySaleBlock["title"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SALE_TITLE", $aboutCompanySaleBlock["title"]);
      $aboutCompanySaleBlock["description"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SALE_DESCRIPTION", $aboutCompanySaleBlock["description"]);
      $aboutCompanySaleBlock["contact_title"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SALE_CONTACT_TITLE", $aboutCompanySaleBlock["contact_title"]);
      $aboutCompanySaleBlock["contact_text"] = aboutCompanyPropertyScalar($aboutCompanyPageProperties, "SALE_CONTACT_TEXT", $aboutCompanySaleBlock["contact_text"]);
      $aboutCompanySaleBlock["background_image"] = aboutCompanyPropertyFileUrl($aboutCompanyPageProperties, "SALE_BACKGROUND_IMAGE", $aboutCompanySaleBlock["background_image"]);
    }
  }

  if ($aboutCompanyGalleryIblockId > 0) {
    $galleryColumnsConfig = array(
      "left" => array(
        "viewport_class" => "",
        "duration" => "18s",
        "items" => array(),
      ),
      "right" => array(
        "viewport_class" => "about-company-social-gallery__viewport--offset",
        "duration" => "22s",
        "items" => array(),
      ),
    );

    $galleryRes = CIBlockElement::GetList(
      array(
        "SORT" => "ASC",
        "ID" => "ASC",
      ),
      array(
        "IBLOCK_ID" => $aboutCompanyGalleryIblockId,
        "ACTIVE" => "Y",
      ),
      false,
      false,
      array(
        "ID",
        "IBLOCK_ID",
        "NAME",
        "CODE",
        "PREVIEW_PICTURE",
      )
    );
    while ($galleryElement = $galleryRes->GetNextElement()) {
      $galleryFields = $galleryElement->GetFields();
      $galleryProperties = $galleryElement->GetProperties();

      $columnKey = aboutCompanyPropertyEnumXmlId($galleryProperties, "COLUMN", "");
      if ($columnKey === "") {
        $columnLabel = mb_strtolower(aboutCompanyPropertyEnumText($galleryProperties, "COLUMN", ""));
        if ($columnLabel === "левая") {
          $columnKey = "left";
        } elseif ($columnLabel === "правая") {
          $columnKey = "right";
        }
      }
      if (!isset($galleryColumnsConfig[$columnKey])) {
        continue;
      }

      $galleryColumnsConfig[$columnKey]["items"][] = array(
        "image" => isset($galleryFields["PREVIEW_PICTURE"]) ? CFile::GetPath((int)$galleryFields["PREVIEW_PICTURE"]) : "",
        "alt" => aboutCompanyPropertyScalar($galleryProperties, "ALT", (string)$galleryFields["NAME"]),
        "text" => aboutCompanyPropertyScalar($galleryProperties, "LABEL", (string)$galleryFields["NAME"]),
        "height" => (int)aboutCompanyPropertyScalar($galleryProperties, "ITEM_HEIGHT", 180),
      );
    }

    $galleryColumnsFallbackByKey = array(
      "left" => isset($aboutCompanySocialGalleryColumns[0]) ? $aboutCompanySocialGalleryColumns[0] : array(),
      "right" => isset($aboutCompanySocialGalleryColumns[1]) ? $aboutCompanySocialGalleryColumns[1] : array(),
    );
    foreach ($galleryColumnsConfig as $galleryColumnKey => $galleryColumnValue) {
      if (empty($galleryColumnValue["items"]) && isset($galleryColumnsFallbackByKey[$galleryColumnKey]["items"])) {
        $galleryColumnsConfig[$galleryColumnKey]["items"] = $galleryColumnsFallbackByKey[$galleryColumnKey]["items"];
      }
    }

    $aboutCompanySocialGalleryColumns = array(
      $galleryColumnsConfig["left"],
      $galleryColumnsConfig["right"],
    );
  }

  if ($projectsIblockId > 0) {
    $aboutCompanyProjectsDynamic = array();
    $projectRes = CIBlockElement::GetList(
      array(
        "SORT" => "ASC",
        "NAME" => "ASC",
      ),
      array(
        "IBLOCK_ID" => $projectsIblockId,
        "ACTIVE" => "Y",
      ),
      false,
      false,
      array(
        "ID",
        "IBLOCK_ID",
        "NAME",
        "CODE",
        "PREVIEW_PICTURE",
      )
    );

    while ($projectElement = $projectRes->GetNextElement()) {
      $projectFields = $projectElement->GetFields();
      $projectProperties = $projectElement->GetProperties();
      if (!aboutCompanyProjectShouldShow($projectProperties)) {
        continue;
      }

      $projectCode = isset($projectFields["CODE"]) ? trim((string)$projectFields["CODE"]) : "";
      $projectFallback = ($projectCode !== "" && isset($aboutCompanyProjectsFallbackByCode[$projectCode]))
        ? $aboutCompanyProjectsFallbackByCode[$projectCode]
        : null;

      $projectName = isset($projectFields["NAME"]) ? trim((string)$projectFields["NAME"]) : "";
      $projectTitle = aboutCompanyProjectTitle($projectName);
      $projectThumb = isset($projectFields["PREVIEW_PICTURE"]) ? CFile::GetPath((int)$projectFields["PREVIEW_PICTURE"]) : "";
      if ($projectThumb === "" && is_array($projectFallback)) {
        $projectThumb = (string)$projectFallback["thumb_image"];
      }

      $statusCode = aboutCompanyPropertyEnumXmlId($projectProperties, "ABOUT_COMPANY_STATUS", "");
      $statusLabel = aboutCompanyPropertyEnumText($projectProperties, "ABOUT_COMPANY_STATUS", "");
      if ($statusCode === "" && is_array($projectFallback)) {
        $statusCode = (string)$projectFallback["status"];
      }
      if ($statusLabel === "" && is_array($projectFallback)) {
        $statusLabel = (string)$projectFallback["status_label"];
      }

      $projectDescription = array();
      $projectText1 = aboutCompanyPropertyScalar(
        $projectProperties,
        "ABOUT_COMPANY_TEXT_1",
        is_array($projectFallback) && isset($projectFallback["description"][0]) ? $projectFallback["description"][0] : ""
      );
      $projectText2 = aboutCompanyPropertyScalar(
        $projectProperties,
        "ABOUT_COMPANY_TEXT_2",
        is_array($projectFallback) && isset($projectFallback["description"][1]) ? $projectFallback["description"][1] : ""
      );
      if ($projectText1 !== "") {
        $projectDescription[] = $projectText1;
      }
      if ($projectText2 !== "") {
        $projectDescription[] = $projectText2;
      }

      $detailImage = aboutCompanyPropertyFileUrl(
        $projectProperties,
        "ABOUT_COMPANY_IMAGE",
        is_array($projectFallback) ? (string)$projectFallback["detail_image"] : ""
      );
      if ($detailImage === "" && is_array($projectFallback)) {
        $detailImage = (string)$projectFallback["detail_image"];
      }

      $aboutCompanyProjectsDynamic[] = array(
        "code" => $projectCode,
        "title" => $projectTitle,
        "status" => $statusCode,
        "status_label" => $statusLabel,
        "thumb_image" => $projectThumb,
        "thumb_alt" => $projectTitle,
        "detail_image" => $detailImage,
        "detail_alt" => $projectTitle,
        "description" => !empty($projectDescription)
          ? $projectDescription
          : (is_array($projectFallback) ? (array)$projectFallback["description"] : array()),
      );
    }

    if (!empty($aboutCompanyProjectsDynamic)) {
      $aboutCompanyProjects = $aboutCompanyProjectsDynamic;
    }
  }
}
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<section class="about-company-hero">
  <div class="container">
    <div class="about-company-hero__grid">
      <div class="about-company-hero__content">
        <div class="about-company-hero__intro">
          <h1 class="about-company-hero__title"><?php $APPLICATION->ShowTitle(false); ?></h1>
          <div class="about-company-hero__text">
            <?php foreach ($aboutCompanyHero["paragraphs"] as $heroParagraph): ?>
              <p><?= htmlspecialcharsbx($heroParagraph) ?></p>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="about-company-hero__awards">
          <?php foreach ($aboutCompanyAwards as $award): ?>
            <article class="about-company-award">
              <div class="about-company-award__logo" aria-hidden="true"><?= htmlspecialcharsbx($award["logo"]) ?></div>
              <div class="about-company-award__caption"><?= htmlspecialcharsbx($award["caption"]) ?></div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="about-company-hero__media">
        <img
          src="<?= htmlspecialcharsbx($aboutCompanyHero["image"]) ?>"
          alt="<?= htmlspecialcharsbx($aboutCompanyHero["image_alt"]) ?>"
        />
      </div>
    </div>
  </div>
</section>

<section class="about-company-social" id="about-company-social">
  <div class="container">
    <div class="about-company-social__grid">
      <article class="about-company-social-card about-company-social-card--intro">
        <h2 class="about-company-social-card__title about-company-social-card__title--intro">
          <?= htmlspecialcharsbx($aboutCompanySocialBlock["intro"]["title"]) ?>
        </h2>
        <div class="about-company-social-card__watermark" aria-hidden="true">
          <img
            src="<?= htmlspecialcharsbx($aboutCompanySocialBlock["intro"]["watermark"]) ?>"
            alt=""
          />
        </div>
        <p class="about-company-social-card__description">
          <?= htmlspecialcharsbx($aboutCompanySocialBlock["intro"]["text"]) ?>
        </p>
      </article>

      <article class="about-company-social-card about-company-social-card--metric">
        <div class="about-company-social-card__media">
          <img
            src="<?= htmlspecialcharsbx($aboutCompanySocialBlock["metric"]["image"]) ?>"
            alt="<?= htmlspecialcharsbx($aboutCompanySocialBlock["metric"]["alt"]) ?>"
          />
        </div>
        <div class="about-company-social-card__content">
          <div class="about-company-social-card__title about-company-social-card__title--metric">
            <?= htmlspecialcharsbx($aboutCompanySocialBlock["metric"]["title"]) ?>
          </div>
          <p class="about-company-social-card__note">
            <?= htmlspecialcharsbx($aboutCompanySocialBlock["metric"]["text"]) ?>
          </p>
        </div>
      </article>

      <article class="about-company-social-card about-company-social-card--material">
        <div class="about-company-social-card__media">
          <img
            src="<?= htmlspecialcharsbx($aboutCompanySocialBlock["material"]["image"]) ?>"
            alt="<?= htmlspecialcharsbx($aboutCompanySocialBlock["material"]["alt"]) ?>"
          />
        </div>
        <div class="about-company-social-card__overlay">
          <h3 class="about-company-social-card__title about-company-social-card__title--side">
            <?= htmlspecialcharsbx($aboutCompanySocialBlock["material"]["title"]) ?>
          </h3>
          <p class="about-company-social-card__side-text">
            <?= htmlspecialcharsbx($aboutCompanySocialBlock["material"]["text"]) ?>
          </p>
        </div>
      </article>

      <article class="about-company-social-card about-company-social-card--progress">
        <h3 class="about-company-social-card__title about-company-social-card__title--progress">
          <?= htmlspecialcharsbx($aboutCompanySocialBlock["progress"]["title"]) ?>
        </h3>
        <p class="about-company-social-card__note about-company-social-card__note--progress">
          <?= htmlspecialcharsbx($aboutCompanySocialBlock["progress"]["text"]) ?>
        </p>
      </article>

      <div class="about-company-social-gallery" aria-label="Социальная инфраструктура КУБ">
        <?php foreach ($aboutCompanySocialGalleryColumns as $column): ?>
          <div class="about-company-social-gallery__viewport <?= htmlspecialcharsbx($column["viewport_class"]) ?>">
            <div
              class="about-company-social-gallery__track"
              data-about-company-gallery-track
              style="--about-company-gallery-duration: <?= htmlspecialcharsbx($column["duration"]) ?>;"
            >
              <?php for ($copyIndex = 0; $copyIndex < 2; $copyIndex++): ?>
                <div class="about-company-social-gallery__sequence"<?= $copyIndex > 0 ? ' aria-hidden="true"' : '' ?>>
                  <?php foreach ($column["items"] as $item): ?>
                    <article
                      class="about-company-social-gallery__item"
                      style="--about-company-gallery-item-height: <?= (int)$item["height"] ?>px;"
                    >
                      <img
                        src="<?= htmlspecialcharsbx($item["image"]) ?>"
                        alt="<?= htmlspecialcharsbx($item["alt"]) ?>"
                      />
                      <?php if (!empty($item["text"])): ?>
                        <div class="about-company-social-gallery__item-text">
                          <?= htmlspecialcharsbx($item["text"]) ?>
                        </div>
                      <?php endif; ?>
                    </article>
                  <?php endforeach; ?>
                </div>
              <?php endfor; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="about-company-projects" id="about-company-projects">
  <div class="container">
    <h2 class="about-company-projects__title"><?= htmlspecialcharsbx($aboutCompanyProjectsSectionTitle) ?></h2>

    <div class="about-company-projects__rail" role="tablist" aria-label="Проекты компании">
      <?php foreach ($aboutCompanyProjects as $index => $project): ?>
        <?php
        $projectTabId = "about-company-project-tab-" . $project["code"];
        $projectPanelId = "about-company-project-panel-" . $project["code"];
        $isProjectActive = $index === 0;
        ?>
        <button
          class="about-company-projects__tab<?= $isProjectActive ? ' is-active' : '' ?>"
          id="<?= htmlspecialcharsbx($projectTabId) ?>"
          type="button"
          role="tab"
          aria-selected="<?= $isProjectActive ? 'true' : 'false' ?>"
          aria-controls="<?= htmlspecialcharsbx($projectPanelId) ?>"
          tabindex="<?= $isProjectActive ? '0' : '-1' ?>"
          data-about-company-project-tab
          data-target="<?= htmlspecialcharsbx($project["code"]) ?>"
        >
          <span class="about-company-projects__tab-thumb">
            <img
              src="<?= htmlspecialcharsbx($project["thumb_image"]) ?>"
              alt="<?= htmlspecialcharsbx($project["thumb_alt"]) ?>"
            />
          </span>
          <span class="about-company-projects__tab-content">
            <span class="about-company-projects__tab-name"><?= htmlspecialcharsbx($project["title"]) ?></span>
            <span class="about-company-projects__status about-company-projects__status--<?= htmlspecialcharsbx($project["status"]) ?>">
              <?= htmlspecialcharsbx($project["status_label"]) ?>
            </span>
          </span>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="about-company-projects__panels">
      <?php foreach ($aboutCompanyProjects as $index => $project): ?>
        <?php
        $projectTabId = "about-company-project-tab-" . $project["code"];
        $projectPanelId = "about-company-project-panel-" . $project["code"];
        $isProjectActive = $index === 0;
        ?>
        <article
          class="about-company-projects__panel<?= $isProjectActive ? ' is-active' : '' ?>"
          id="<?= htmlspecialcharsbx($projectPanelId) ?>"
          role="tabpanel"
          aria-labelledby="<?= htmlspecialcharsbx($projectTabId) ?>"
          data-about-company-project-panel="<?= htmlspecialcharsbx($project["code"]) ?>"
          <?= $isProjectActive ? '' : 'hidden' ?>
        >
          <div class="about-company-projects__panel-copy">
            <div class="about-company-projects__status about-company-projects__status--<?= htmlspecialcharsbx($project["status"]) ?>">
              <?= htmlspecialcharsbx($project["status_label"]) ?>
            </div>
            <h3 class="about-company-projects__panel-title"><?= htmlspecialcharsbx($project["title"]) ?></h3>
            <div class="about-company-projects__panel-text">
              <?php foreach ($project["description"] as $paragraph): ?>
                <p><?= htmlspecialcharsbx($paragraph) ?></p>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="about-company-projects__panel-media">
            <img
              src="<?= htmlspecialcharsbx($project["detail_image"]) ?>"
              alt="<?= htmlspecialcharsbx($project["detail_alt"]) ?>"
            />
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="about-company-sale" id="about-company-sale">
  <div class="container">
    <div class="about-company-sale__head">
      <div class="about-company-sale__head-copy">
        <h2 class="about-company-sale__title"><?= htmlspecialcharsbx($aboutCompanySaleBlock["title"]) ?></h2>
        <p class="about-company-sale__description"><?= htmlspecialcharsbx($aboutCompanySaleBlock["description"]) ?></p>
      </div>

      <a class="btn btn--light about-company-sale__link" href="/projects/">
        Все проекты
      </a>
    </div>

    <?php if ($projectsIblockId > 0): ?>
      <?php
      $GLOBALS["aboutCompanySaleProjectsFilter"] = array(
        "ID" => is_array($aboutCompanySaleProjectIds) && !empty($aboutCompanySaleProjectIds)
          ? $aboutCompanySaleProjectIds
          : array(-1),
      );
      $APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "projects_list",
        array(
          "IBLOCK_TYPE" => $projectsIblockType,
          "IBLOCK_ID" => $projectsIblockId,
          "FILTER_NAME" => "aboutCompanySaleProjectsFilter",
          "NEWS_COUNT" => "3",
          "SORT_BY1" => "SORT",
          "SORT_ORDER1" => "ASC",
          "SORT_BY2" => "NAME",
          "SORT_ORDER2" => "ASC",
          "FIELD_CODE" => array(
            0 => "NAME",
            1 => "PREVIEW_PICTURE",
            2 => "",
          ),
          "PROPERTY_CODE" => array(
            0 => "CLASS_LABEL",
            1 => "TAG_LABEL",
            2 => "ADDRESS",
            3 => "DELIVERY_TEXT",
            4 => "ROOMS_IN_SALE",
            5 => "SALE_COUNT_TEXT",
            6 => "PRICE_FROM_TEXT",
            7 => "ABOUT_COMPANY_STATUS",
            8 => "",
          ),
          "CHECK_DATES" => "N",
          "DETAIL_URL" => "/projects/#ELEMENT_CODE#/",
          "ACTIVE_DATE_FORMAT" => "d.m.Y",
          "CACHE_TYPE" => "A",
          "CACHE_TIME" => "36000000",
          "CACHE_FILTER" => "Y",
          "CACHE_GROUPS" => "Y",
          "SET_TITLE" => "N",
          "SET_BROWSER_TITLE" => "N",
          "SET_META_KEYWORDS" => "N",
          "SET_META_DESCRIPTION" => "N",
          "SET_LAST_MODIFIED" => "N",
          "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
          "ADD_SECTIONS_CHAIN" => "N",
          "HIDE_LINK_WHEN_NO_DETAIL" => "N",
          "DISPLAY_TOP_PAGER" => "N",
          "DISPLAY_BOTTOM_PAGER" => "N",
          "PAGER_SHOW_ALWAYS" => "N",
          "PAGER_TEMPLATE" => "",
          "DISPLAY_DATE" => "N",
          "DISPLAY_NAME" => "Y",
          "DISPLAY_PICTURE" => "Y",
          "DISPLAY_PREVIEW_TEXT" => "N",
          "PARENT_SECTION" => "",
          "PARENT_SECTION_CODE" => "",
          "STRICT_SECTION_CHECK" => "N",
        ),
        false
      );
      unset($GLOBALS["aboutCompanySaleProjectsFilter"]);
      ?>
    <?php else: ?>
      <div class="projects-empty">Проекты не найдены.</div>
    <?php endif; ?>

    <div
      class="about-company-sale__contact"
      style="--about-company-sale-bg-image: url('<?= htmlspecialcharsbx($aboutCompanySaleBlock["background_image"]) ?>');"
    >
      <div class="about-company-sale__contact-copy">
        <h3 class="about-company-sale__contact-title"><?= htmlspecialcharsbx($aboutCompanySaleBlock["contact_title"]) ?></h3>
        <p class="about-company-sale__contact-text"><?= htmlspecialcharsbx($aboutCompanySaleBlock["contact_text"]) ?></p>
      </div>

      <div class="about-company-sale__contact-form about-company-sale__contact-form--no-title">
        <?php
        $contactFormId = "about-company-sale-form";
        $contactFormTitle = "Узнать о проектах в продаже";
        $contactFormLeadType = "about_company_sale";
        $contactFormLeadSource = "about_company_inline";
        include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/contact-form.php";
        unset($contactFormId, $contactFormTitle, $contactFormLeadType, $contactFormLeadSource);
        ?>
      </div>
    </div>
  </div>
</section>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>

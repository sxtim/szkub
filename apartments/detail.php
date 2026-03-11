<?php
define("APARTMENT_DETAIL_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$planImage = SITE_TEMPLATE_PATH . "/img/apartments/" . rawurlencode("1 этаж 2е 92.8 с антресолью 1.jpg");
$buildingImage = SITE_TEMPLATE_PATH . "/img/projects/image_15.jpg";
$viewImage = SITE_TEMPLATE_PATH . "/img/projects/div.image-lazy__image.jpg";
$floorImage = SITE_TEMPLATE_PATH . "/img/projects/Group.svg";

if (!function_exists("apartmentDetailAppendFact")) {
	function apartmentDetailAppendFact(&$facts, $label, $value, $extra = array())
	{
		$value = is_string($value) ? trim($value) : $value;
		if ($value === "" || $value === null) {
			return;
		}

		$facts[] = array_merge(
			array(
				"label" => (string)$label,
				"value" => $value,
			),
			is_array($extra) ? $extra : array()
		);
	}
}

if (!function_exists("apartmentDetailBuildSlides")) {
	function apartmentDetailBuildSlides($lotCode, $building, $projectName, $planImage, $floorImage, $buildingImage, $viewImage)
	{
		return array(
			array(
				"label" => "Планировка",
				"title" => "Планировка 2Е",
				"description" => "Рациональная кухня-гостиная, отдельная спальня и большая антресольная зона хранения.",
				"image" => $planImage,
				"alt" => "Планировка квартиры " . $lotCode,
				"bearing" => 214,
				"kind" => "plan",
			),
			array(
				"label" => "На этаже",
				"title" => "Положение на этаже",
				"description" => "Квартира расположена в торцевой части этажа, дальше от лифтового узла.",
				"image" => $floorImage,
				"alt" => "Схема расположения квартиры на этаже",
				"bearing" => 228,
				"kind" => "scheme",
			),
			array(
				"label" => "В корпусе",
				"title" => "Корпус " . $building,
				"description" => "Выше линии двора, с открытым видом и быстрым выходом к приватной инфраструктуре проекта.",
				"image" => $buildingImage,
				"alt" => "Корпус " . $building . " " . $projectName,
				"bearing" => 206,
				"kind" => "render",
			),
			array(
				"label" => "Вид из окна",
				"title" => "Вид из окна",
				"description" => "Окна ориентированы на воду и городскую панораму, с вечерним западным светом.",
				"image" => $viewImage,
				"alt" => "Вид из окна квартиры",
				"bearing" => 247,
				"kind" => "photo",
			),
			array(
				"label" => "Визуализация",
				"title" => "Пространство в интерьере",
				"description" => "Потенциал комнаты и кухни-гостиной в спокойной современной палитре материалов.",
				"image" => $buildingImage,
				"alt" => "Визуализация квартиры",
				"bearing" => 214,
				"kind" => "render",
			),
		);
	}
}

if (!function_exists("apartmentDetailBuildPrototype")) {
	function apartmentDetailBuildPrototype($overrides, $planImage, $floorImage, $buildingImage, $viewImage)
	{
			$base = array(
				"title" => "2-комнатная, 92.8 м²",
				"title_line_1" => "2-комнатная",
				"title_line_2" => "92.8 м²",
				"project" => "ЖК Вертикаль",
			"project_url" => "/projects/vertical/",
			"building" => "11",
			"floor" => "12",
			"house_floors" => "24",
			"handover" => "III кв. 2027 г.",
			"lot" => "",
			"apartment_number" => "11",
			"price_meter" => "532 371 ₽",
			"price_total" => "22 199 873 ₽",
			"price_old" => "23 408 000 ₽",
			"finish" => "Без отделки",
			"ceiling" => "2.72 м",
			"street" => "ул. Фронтовая, 5",
			"entrance" => "2",
			"view" => "На водохранилище и город",
				"window_sides" => "Юг / Запад",
				"discount" => "Скидка 5%",
				"availability_status" => "free",
				"availability_label" => "Свободно",
				"availability_badges" => array(),
				"rooms" => "2Е",
			"area_total" => "92.8 м²",
			"area_living" => "41.7 м²",
			"area_kitchen" => "18.4 м²",
			"balcony_type" => "Французский балкон",
			"bathrooms" => "2",
			"house_type" => "Монолит-кирпич",
			"feature_flags" => array(
				array("label" => "Гардероб", "enabled" => true),
				array("label" => "Мастер-спальня", "enabled" => false),
				array("label" => "Витражное остекление", "enabled" => true),
				array("label" => "Высокие потолки", "enabled" => false),
				array("label" => "Терраса", "enabled" => false),
				array("label" => "С отделкой", "enabled" => false),
			),
			"feature_tags" => array(),
		);

			$data = array_replace($base, is_array($overrides) ? $overrides : array());
		$floorValue = trim((string)$data["floor"]);
		$houseFloorsValue = trim((string)$data["house_floors"]);
		if ($floorValue !== "" && $houseFloorsValue !== "") {
			$data["floor_display"] = $floorValue . " из " . $houseFloorsValue;
		} else {
			$data["floor_display"] = $floorValue !== "" ? $floorValue : $houseFloorsValue;
		}

			$availabilityBadges = array();
			if (isset($data["availability_badges"]) && is_array($data["availability_badges"])) {
				foreach ($data["availability_badges"] as $badge) {
					$status = isset($badge["status"]) ? trim((string)$badge["status"]) : "";
					$label = isset($badge["label"]) ? trim((string)$badge["label"]) : "";
					if ($status !== "" && $label !== "") {
						$availabilityBadges[] = array(
							"status" => $status,
							"label" => $label,
						);
					}
				}
			}
			if (empty($availabilityBadges) && trim((string)$data["availability_status"]) !== "" && trim((string)$data["availability_label"]) !== "") {
				$availabilityBadges[] = array(
					"status" => trim((string)$data["availability_status"]),
					"label" => trim((string)$data["availability_label"]),
				);
			}
			$data["availability_badges"] = $availabilityBadges;

			$data["slides"] = apartmentDetailBuildSlides(
			(string)$data["lot"],
			(string)$data["building"],
			(string)$data["project"],
			$planImage,
			$floorImage,
			$buildingImage,
			$viewImage
		);

		$primaryFacts = array();
		apartmentDetailAppendFact($primaryFacts, "ЖК", $data["project"], array("url" => $data["project_url"]));
		apartmentDetailAppendFact($primaryFacts, "Улица", $data["street"]);
		apartmentDetailAppendFact($primaryFacts, "Подъезд", $data["entrance"]);
		apartmentDetailAppendFact($primaryFacts, "Этаж", $data["floor_display"]);
		apartmentDetailAppendFact($primaryFacts, "Общая площадь", $data["area_total"]);
		apartmentDetailAppendFact($primaryFacts, "Жилая площадь", $data["area_living"]);
		apartmentDetailAppendFact($primaryFacts, "Тип дома", $data["house_type"]);
		apartmentDetailAppendFact($primaryFacts, "Срок сдачи", $data["handover"]);
		$data["primary_facts"] = $primaryFacts;

		$detailFacts = array();
		apartmentDetailAppendFact($detailFacts, "Площадь кухни", $data["area_kitchen"]);
		apartmentDetailAppendFact($detailFacts, "Высота потолков", $data["ceiling"]);
		apartmentDetailAppendFact($detailFacts, "Кол-во санузлов", $data["bathrooms"]);
		apartmentDetailAppendFact($detailFacts, "Балкон / лоджия", $data["balcony_type"]);
		apartmentDetailAppendFact($detailFacts, "Вид из окна", $data["view"]);
		apartmentDetailAppendFact($detailFacts, "Окна по сторонам", $data["window_sides"]);
		$data["detail_facts"] = $detailFacts;

		$featureTags = array();
		if (isset($data["feature_flags"]) && is_array($data["feature_flags"])) {
			foreach ($data["feature_flags"] as $featureFlag) {
				$label = isset($featureFlag["label"]) ? trim((string)$featureFlag["label"]) : "";
				$enabled = !empty($featureFlag["enabled"]);
				if ($enabled && $label !== "") {
					$featureTags[] = $label;
				}
			}
		}
		if (isset($data["feature_tags"]) && is_array($data["feature_tags"])) {
			foreach ($data["feature_tags"] as $featureTag) {
				$featureTag = trim((string)$featureTag);
				if ($featureTag !== "") {
					$featureTags[] = $featureTag;
				}
			}
		}
		$data["feature_tags"] = array_values(array_unique($featureTags));

		return $data;
	}
}

$apartments = array(
		"11-235" => apartmentDetailBuildPrototype(
			array(
				"lot" => "11-235",
			),
			$planImage,
			$floorImage,
		$buildingImage,
		$viewImage
	),
	"2e-92-8" => apartmentDetailBuildPrototype(
			array(
				"lot" => "2E-92-8",
				"price_total" => "22 540 000 ₽",
				"price_old" => "23 730 000 ₽",
				"discount" => "Скидка 5% до конца месяца",
				"availability_status" => "booked",
				"availability_label" => "Забронировано",
				"feature_tags" => array("Кухня-гостиная 18.4 м²"),
			),
		$planImage,
		$floorImage,
		$buildingImage,
		$viewImage
	),
);

$code = isset($_REQUEST["code"]) ? trim((string)$_REQUEST["code"]) : "";
$code = preg_replace("/[^a-z0-9_-]/i", "", $code);
$apartment = isset($apartments[$code]) ? $apartments[$code] : null;

if (!$apartment) {
	CHTTP::SetStatus("404 Not Found");
	@define("ERROR_404", "Y");
	$APPLICATION->SetTitle("Квартира не найдена");
} else {
	$APPLICATION->SetTitle($apartment["title"]);
	$APPLICATION->SetPageProperty("title", $apartment["title"] . " — КУБ");
}
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<?php if (!$apartment): ?>
<section class="apartment-detail apartment-detail--empty">
  <div class="container">
    <h1 class="section-title"><?php $APPLICATION->ShowTitle(false); ?></h1>
    <p>Квартира не найдена.</p>
  </div>
</section>
<?php else: ?>
<section class="apartment-detail">
  <div class="container">
	    <div class="apartment-hero">
	      <div class="apartment-hero__media">
	        <div class="apartment-hero__viewer-shell" data-apartment-gallery>
	          <?php if (!empty($apartment["discount"])): ?>
	          <div class="apartment-hero__badge apartment-hero__badge--discount"><?= htmlspecialcharsbx($apartment["discount"]) ?></div>
	          <?php endif; ?>
	          <div class="apartment-hero__rail">
	          <?php if (!empty($apartment["availability_badges"])): ?>
	            <?php $statusBadge = reset($apartment["availability_badges"]); ?>
	            <div class="apartment-hero__badge apartment-hero__badge--status apartment-hero__badge--<?= htmlspecialcharsbx($statusBadge["status"]) ?>">
	              <?= htmlspecialcharsbx($statusBadge["label"]) ?>
	            </div>
	            <?php endif; ?>
	            <div class="apartment-hero__actions">
	              <button class="apartment-hero__action" type="button" data-apartment-action="zoom" aria-label="Увеличить слайд">
	                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	                  <circle cx="10" cy="10" r="6.25" stroke="currentColor" stroke-width="1.5" />
	                  <path d="M10 7V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
	                  <path d="M7 10H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
	                  <path d="M14.5 14.5L18 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
	                </svg>
	              </button>
	              <button class="apartment-hero__action" type="button" data-apartment-action="favorite" aria-label="Добавить в избранное">
	                <svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	                  <path d="M10.9988 18.1463L9.77616 17.0337C5.43468 13.1098 2.56445 10.5081 2.56445 7.31585C2.56445 4.71413 4.59884 2.69336 7.20562 2.69336C8.67789 2.69336 10.0906 3.37417 10.9988 4.44819C11.9071 3.37417 13.3198 2.69336 14.7921 2.69336C17.3989 2.69336 19.4333 4.71413 19.4333 7.31585C19.4333 10.5081 16.563 13.1098 12.2215 17.0412L10.9988 18.1463Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
	                </svg>
	              </button>
	              <button class="apartment-hero__action" type="button" data-apartment-action="share" aria-label="Поделиться">
	                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	                  <path d="M14.8398 7.16113L7.16035 14.8406" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
	                  <path d="M9.37988 5.68164H16.3199V12.6216" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
	                </svg>
	              </button>
	              <button class="apartment-hero__action" type="button" data-apartment-action="print" aria-label="Печать">
	                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	                  <path d="M17 13.01L17.01 12.9989" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
	                  <path d="M7 17H17M6 10V3.6C6 3.26863 6.26863 3 6.6 3H17.4C17.7314 3 18 3.26863 18 3.6V10M21 20.4V14C21 11.7909 19.2091 10 17 10H7C4.79086 10 3 11.7909 3 14V20.4C3 20.7314 3.26863 21 3.6 21H20.4C20.7314 21 21 20.7314 21 20.4Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
	                </svg>
	              </button>
	            </div>
	          </div>

	          <div class="apartment-hero__viewer">
	            <div class="swiper apartment-hero__swiper" data-apartment-swiper>
	              <div class="swiper-wrapper">
	                <?php foreach ($apartment["slides"] as $index => $slide): ?>
	                <div
	                  class="swiper-slide apartment-hero__slide apartment-hero__slide--<?= htmlspecialcharsbx($slide["kind"]) ?>"
	                  data-bearing="<?= (int)$slide["bearing"] ?>"
	                >
	                  <div class="apartment-hero__slide-media">
	                    <img src="<?= htmlspecialcharsbx($slide["image"]) ?>" alt="<?= htmlspecialcharsbx($slide["alt"]) ?>" loading="lazy" />
	                  </div>
	                  <div class="apartment-hero__slide-caption">
	                    <div class="apartment-hero__slide-title"><?= htmlspecialcharsbx($slide["title"]) ?></div>
	                    <p><?= htmlspecialcharsbx($slide["description"]) ?></p>
	                  </div>
	                </div>
	                <?php endforeach; ?>
	              </div>
	            </div>
	          </div>

          <div class="apartment-hero__tabs-row">
            <div class="apartment-hero__tabs" role="tablist" aria-label="Режимы просмотра квартиры">
              <?php foreach ($apartment["slides"] as $index => $slide): ?>
              <button
                class="apartment-hero__tab<?= $index === 0 ? " is-active" : "" ?>"
                type="button"
                role="tab"
                aria-selected="<?= $index === 0 ? "true" : "false" ?>"
                data-apartment-tab="<?= $index ?>"
              >
                <?= htmlspecialcharsbx($slide["label"]) ?>
              </button>
              <?php endforeach; ?>
            </div>

            <div class="apartment-hero__nav">
              <button class="apartment-hero__nav-btn apartment-hero__nav-btn--prev" type="button" data-apartment-prev aria-label="Предыдущий слайд">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M10.5 4.5L6 9L10.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
              <button class="apartment-hero__nav-btn apartment-hero__nav-btn--next" type="button" data-apartment-next aria-label="Следующий слайд">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M7.5 4.5L12 9L7.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <aside class="apartment-hero__summary">
        <div class="apartment-hero__eyebrow">№ <?= htmlspecialcharsbx($apartment["apartment_number"]) ?></div>
        <h1 class="apartment-hero__title">
          <span class="apartment-hero__title-line"><?= htmlspecialcharsbx($apartment["title_line_1"]) ?></span>
          <span class="apartment-hero__title-line"><?= htmlspecialcharsbx($apartment["title_line_2"]) ?></span>
        </h1>

        <dl class="apartment-hero__facts">
          <?php foreach ($apartment["primary_facts"] as $fact): ?>
          <div class="apartment-hero__fact">
            <dt><?= htmlspecialcharsbx($fact["label"]) ?></dt>
            <dd>
              <?php if (!empty($fact["url"])): ?>
              <a href="<?= htmlspecialcharsbx($fact["url"]) ?>"><?= htmlspecialcharsbx($fact["value"]) ?></a>
              <?php else: ?>
              <?= htmlspecialcharsbx($fact["value"]) ?>
              <?php endif; ?>
            </dd>
          </div>
          <?php endforeach; ?>
        </dl>

        <?php if (!empty($apartment["feature_tags"])): ?>
        <div class="apartment-hero__traits">
          <div class="apartment-hero__traits-title">Особенности</div>
          <div class="apartment-hero__traits-list">
            <?php foreach ($apartment["feature_tags"] as $featureTag): ?>
            <span class="apartment-hero__trait"><?= htmlspecialcharsbx($featureTag) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="apartment-hero__price-card">
          <?php if (!empty($apartment["price_old"])): ?>
          <div class="apartment-hero__price-old"><?= htmlspecialcharsbx($apartment["price_old"]) ?></div>
          <?php endif; ?>
          <div class="apartment-hero__price-current"><?= htmlspecialcharsbx($apartment["price_total"]) ?></div>
          <div class="apartment-hero__price-meta">
            <span>Цена за м²</span>
            <strong><?= htmlspecialcharsbx($apartment["price_meter"]) ?></strong>
          </div>
        </div>

        <button
          class="btn btn--primary apartment-hero__cta"
          type="button"
          data-contact-open="contact"
          data-contact-title="Забронировать квартиру"
          data-contact-type="booking"
          data-contact-source="apartment_detail"
        >
          Забронировать
        </button>

        <div class="apartment-hero__params">
          <button class="apartment-hero__params-toggle" type="button" data-apartment-params-toggle aria-expanded="true">
            Все параметры квартиры
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </button>

          <div class="apartment-hero__params-body" data-apartment-params>
            <dl class="apartment-hero__params-list">
              <?php foreach ($apartment["detail_facts"] as $param): ?>
              <div class="apartment-hero__fact">
                <dt><?= htmlspecialcharsbx($param["label"]) ?></dt>
                <dd><?= htmlspecialcharsbx($param["value"]) ?></dd>
              </div>
              <?php endforeach; ?>
            </dl>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>

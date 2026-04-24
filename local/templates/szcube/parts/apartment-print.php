<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$printPlanSlide = isset($apartmentPrintPlanSlide) && is_array($apartmentPrintPlanSlide) ? $apartmentPrintPlanSlide : array();
$printFacts = isset($apartmentPrintFacts) && is_array($apartmentPrintFacts) ? $apartmentPrintFacts : array();
$printQrItems = isset($apartmentPrintQrItems) && is_array($apartmentPrintQrItems) ? $apartmentPrintQrItems : array();
$printDisclaimer = isset($apartmentPrintDisclaimer) ? trim((string)$apartmentPrintDisclaimer) : "";
$printGeneratedAt = isset($apartmentPrintGeneratedAt) ? trim((string)$apartmentPrintGeneratedAt) : "";
$apartmentNumber = isset($printNumberValue)
    ? trim((string)$printNumberValue)
    : (isset($apartment["apartment_number"]) ? trim((string)$apartment["apartment_number"]) : "");
$numberLabel = isset($printNumberLabel) ? trim((string)$printNumberLabel) : "Квартира №";
$entrance = isset($printEntranceValue)
    ? trim((string)$printEntranceValue)
    : (isset($apartment["entrance"]) ? trim((string)$apartment["entrance"]) : "");
$entranceLabel = isset($printEntranceLabel) ? trim((string)$printEntranceLabel) : "Подъезд";
$projectName = isset($printProjectName)
    ? trim((string)$printProjectName)
    : (isset($apartment["project"]) ? trim((string)$apartment["project"]) : "");
$statusBadges = isset($printStatusBadges) && is_array($printStatusBadges)
    ? $printStatusBadges
    : (isset($apartment["availability_badges"]) && is_array($apartment["availability_badges"]) ? $apartment["availability_badges"] : array());
$featureTags = isset($printFeatureTags) && is_array($printFeatureTags)
    ? $printFeatureTags
    : (isset($apartment["feature_tags"]) && is_array($apartment["feature_tags"]) ? $apartment["feature_tags"] : array());
$titleLine1 = isset($printTitleLine1)
    ? trim((string)$printTitleLine1)
    : (isset($apartment["title_line_1"]) ? trim((string)$apartment["title_line_1"]) : "");
$titleLine2 = isset($printTitleLine2)
    ? trim((string)$printTitleLine2)
    : (isset($apartment["title_line_2"]) ? trim((string)$apartment["title_line_2"]) : "");
$priceCurrent = isset($printPriceCurrent)
    ? trim((string)$printPriceCurrent)
    : (isset($apartment["price_total"]) ? trim((string)$apartment["price_total"]) : "");
$priceOld = isset($printPriceOld)
    ? trim((string)$printPriceOld)
    : (isset($apartment["price_old"]) ? trim((string)$apartment["price_old"]) : "");
$priceMeter = isset($printPriceMeter)
    ? trim((string)$printPriceMeter)
    : (isset($apartment["price_meter"]) ? trim((string)$apartment["price_meter"]) : "");
$officeAddress = "Воронеж, ул. Фридриха Энгельса, дом 7а офис 201";
$officeEmail = "cube-develop@yandex.ru";
$headerLogoSvg = "";
$headerChipParts = array();
$printCloseUrl = isset($apartmentDetailPublicUrl) ? trim((string)$apartmentDetailPublicUrl) : "";

$headerLogoPath = $_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/figma-f0c695eb-93ca-42c4-81da-32adaa050abc.svg";
if (is_file($headerLogoPath) && is_readable($headerLogoPath)) {
    $headerLogoSvg = (string)file_get_contents($headerLogoPath);
    $headerLogoSvg = str_replace('preserveAspectRatio="none"', 'preserveAspectRatio="xMinYMin meet"', $headerLogoSvg);
}

if ($printCloseUrl === "") {
    $requestUri = isset($_SERVER["REQUEST_URI"]) ? trim((string)$_SERVER["REQUEST_URI"]) : "";
    if ($requestUri !== "") {
        $requestParts = parse_url($requestUri);
        $requestPath = isset($requestParts["path"]) && trim((string)$requestParts["path"]) !== ""
            ? (string)$requestParts["path"]
            : "/";
        $requestQuery = array();

        if (isset($requestParts["query"]) && trim((string)$requestParts["query"]) !== "") {
            parse_str((string)$requestParts["query"], $requestQuery);
            unset($requestQuery["print"]);
        }

        $printCloseUrl = $requestPath;
        if (!empty($requestQuery)) {
            $printCloseUrl .= "?" . http_build_query($requestQuery);
        }
    }
}

if ($projectName !== "") {
    $headerChipParts[] = $projectName;
}
if ($apartmentNumber !== "") {
    $headerChipParts[] = ($numberLabel !== "" ? $numberLabel . " " : "") . $apartmentNumber;
}
if ($entrance !== "") {
    $headerChipParts[] = ($entranceLabel !== "" ? $entranceLabel . " " : "") . $entrance;
}
?>
<section
  class="apartment-print"
  data-apartment-print-page
  data-return-url="<?= htmlspecialcharsbx($printCloseUrl) ?>"
>
  <div class="apartment-print__toolbar" aria-label="Действия на странице печати">
    <div class="apartment-print__toolbar-group">
      <button
        class="apartment-print__toolbar-button apartment-print__toolbar-button--primary"
        type="button"
        data-apartment-print-action="print"
      >
        Печать
      </button>
      <?php if ($printCloseUrl !== ""): ?>
      <a class="apartment-print__toolbar-button apartment-print__toolbar-button--ghost" href="<?= htmlspecialcharsbx($printCloseUrl) ?>">
        Вернуться к квартире
      </a>
      <?php endif; ?>
    </div>
    <button class="apartment-print__toolbar-button" type="button" data-apartment-print-action="close">
      Закрыть
    </button>
  </div>
  <div class="apartment-print__sheet">
    <header class="apartment-print__header">
      <div class="apartment-print__brand">
        <div class="apartment-print__brand-row">
          <div class="apartment-print__logo" role="img" aria-label="КУБ">
            <?= $headerLogoSvg ?>
          </div>
        </div>
      </div>
      <div class="apartment-print__office">
        <div class="apartment-print__office-address"><?= htmlspecialcharsbx($officeAddress) ?></div>
        <a class="apartment-print__office-email" href="mailto:<?= htmlspecialcharsbx($officeEmail) ?>"><?= htmlspecialcharsbx($officeEmail) ?></a>
      </div>
      <div class="apartment-print__contact">
        <a href="tel:+7(473)300-68-87">+7 (473) 300-68-87</a>
        <span>Ежедневно с 9:00 до 19:00</span>
      </div>
      <?php if (!empty($headerChipParts)): ?>
      <div class="apartment-print__chip"><?= htmlspecialcharsbx(implode(" · ", $headerChipParts)) ?></div>
      <?php endif; ?>
    </header>

    <div class="apartment-print__main-card">
      <div class="apartment-print__main">
        <div class="apartment-print__plan">
          <?php if (!empty($printPlanSlide["image"])): ?>
          <img
            src="<?= htmlspecialcharsbx((string)$printPlanSlide["image"]) ?>"
            alt="<?= htmlspecialcharsbx(isset($printPlanSlide["alt"]) ? (string)$printPlanSlide["alt"] : $titleLine1) ?>"
            loading="eager"
          />
          <?php endif; ?>
        </div>

        <aside class="apartment-print__summary">
          <div class="apartment-print__summary-head">
            <?php if (!empty($statusBadges)): ?>
              <?php foreach ($statusBadges as $statusBadge): ?>
                <?php
                $statusLabel = isset($statusBadge["label"]) ? trim((string)$statusBadge["label"]) : "";
                $statusKey = isset($statusBadge["status"]) ? trim((string)$statusBadge["status"]) : "";
                if ($statusLabel === "") {
                    continue;
                }
                ?>
                <span class="apartment-print__badge apartment-print__badge--<?= htmlspecialcharsbx($statusKey !== "" ? $statusKey : "default") ?>">
                  <?= htmlspecialcharsbx($statusLabel) ?>
                </span>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="apartment-print__title-wrap">
            <?php if ($titleLine1 !== "" || $titleLine2 !== ""): ?>
            <h1 class="apartment-print__headline">
              <?php if ($titleLine1 !== ""): ?>
              <span><?= htmlspecialcharsbx($titleLine1) ?></span>
              <?php endif; ?>
              <?php if ($titleLine2 !== ""): ?>
              <span><?= htmlspecialcharsbx($titleLine2) ?></span>
              <?php endif; ?>
            </h1>
            <?php endif; ?>
            <?php if ($projectName !== ""): ?>
            <div class="apartment-print__project"><?= htmlspecialcharsbx($projectName) ?></div>
            <?php endif; ?>
          </div>

          <?php if (!empty($printFacts)): ?>
          <dl class="apartment-print__facts">
            <?php foreach ($printFacts as $fact): ?>
              <?php
              $factLabel = isset($fact["label"]) ? trim((string)$fact["label"]) : "";
              $factValue = isset($fact["value"]) ? trim((string)$fact["value"]) : "";
              if ($factLabel === "" || $factValue === "") {
                  continue;
              }
              ?>
              <div class="apartment-print__fact">
                <dt><?= htmlspecialcharsbx($factLabel) ?></dt>
                <dd><?= htmlspecialcharsbx($factValue) ?></dd>
              </div>
            <?php endforeach; ?>
          </dl>
          <?php endif; ?>

          <?php if (!empty($featureTags)): ?>
          <div class="apartment-print__traits">
            <?php foreach ($featureTags as $featureTag): ?>
              <?php $featureTag = trim((string)$featureTag); ?>
              <?php if ($featureTag === "") { continue; } ?>
              <span class="apartment-print__trait"><?= htmlspecialcharsbx($featureTag) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if ($priceCurrent !== "" || $priceOld !== "" || $priceMeter !== ""): ?>
          <div class="apartment-print__price">
            <?php if ($printGeneratedAt !== ""): ?>
            <div class="apartment-print__generated-at"><?= htmlspecialcharsbx($printGeneratedAt) ?></div>
            <?php endif; ?>
            <?php if ($priceOld !== ""): ?>
            <div class="apartment-print__price-old"><?= htmlspecialcharsbx($priceOld) ?></div>
            <?php endif; ?>
            <?php if ($priceCurrent !== ""): ?>
            <div class="apartment-print__price-current"><?= htmlspecialcharsbx($priceCurrent) ?></div>
            <?php endif; ?>
            <?php if ($priceMeter !== ""): ?>
            <div class="apartment-print__price-meter">Цена за м²: <?= htmlspecialcharsbx($priceMeter) ?></div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </aside>
      </div>
    </div>

    <?php if (!empty($printQrItems)): ?>
    <div class="apartment-print__qr-list">
      <?php foreach ($printQrItems as $printQrItem): ?>
        <?php
        $qrTitle = isset($printQrItem["title"]) ? trim((string)$printQrItem["title"]) : "";
        $qrText = isset($printQrItem["text"]) ? trim((string)$printQrItem["text"]) : "";
        $qrImage = isset($printQrItem["image"]) ? trim((string)$printQrItem["image"]) : "";
        $qrUrl = isset($printQrItem["url"]) ? trim((string)$printQrItem["url"]) : "";
        if ($qrTitle === "" || $qrImage === "") {
            continue;
        }
        ?>
        <div class="apartment-print__qr-card">
          <img class="apartment-print__qr-image" src="<?= htmlspecialcharsbx($qrImage) ?>" alt="<?= htmlspecialcharsbx($qrTitle) ?>" loading="eager" />
          <div class="apartment-print__qr-copy">
            <div class="apartment-print__qr-title"><?= htmlspecialcharsbx($qrTitle) ?></div>
            <?php if ($qrText !== ""): ?>
            <p><?= htmlspecialcharsbx($qrText) ?></p>
            <?php endif; ?>
            <?php if ($qrUrl !== ""): ?>
            <div class="apartment-print__qr-link"><?= htmlspecialcharsbx($qrUrl) ?></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <footer class="apartment-print__footer">
      <?php if ($printDisclaimer !== ""): ?>
      <p class="apartment-print__disclaimer"><?= htmlspecialcharsbx($printDisclaimer) ?></p>
      <?php endif; ?>
      <div class="apartment-print__page-number">1 из 1</div>
    </footer>
  </div>
</section>

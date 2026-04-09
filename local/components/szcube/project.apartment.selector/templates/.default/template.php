<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$project = isset($arResult["PROJECT"]) && is_array($arResult["PROJECT"]) ? $arResult["PROJECT"] : array();
$entrances = isset($arResult["ENTRANCES"]) && is_array($arResult["ENTRANCES"]) ? $arResult["ENTRANCES"] : array();
$emptyMessage = isset($arResult["EMPTY_MESSAGE"]) ? trim((string)$arResult["EMPTY_MESSAGE"]) : "";
if (empty($entrances)) {
    if ($emptyMessage !== ""): ?>
      <section class="projects-genplan" aria-label="Выбор квартиры в проекте">
        <div class="projects-selector projects-selector--empty">
          <div class="projects-selector__empty-message"><?= htmlspecialcharsbx($emptyMessage) ?></div>
        </div>
      </section>
    <?php endif;
    return;
}

$state = array(
    "initialEntranceId" => isset($arResult["INITIAL_ENTRANCE_ID"]) ? (string)$arResult["INITIAL_ENTRANCE_ID"] : "",
    "initialView" => isset($arResult["INITIAL_VIEW"]) ? (string)$arResult["INITIAL_VIEW"] : "scene",
    "initialFlatCode" => isset($arResult["INITIAL_FLAT_CODE"]) ? (string)$arResult["INITIAL_FLAT_CODE"] : "",
);
$sceneConfig = isset($project["SCENE_CONFIG"]) && is_array($project["SCENE_CONFIG"]) ? $project["SCENE_CONFIG"] : array();
$sceneSettings = isset($sceneConfig["scene"]) && is_array($sceneConfig["scene"]) ? $sceneConfig["scene"] : array();
$mobileSceneImage = isset($sceneConfig["mobile_scene_image"]) ? trim((string)$sceneConfig["mobile_scene_image"]) : "";
$stageStyle = array();
if (isset($sceneSettings["overlay"]) && is_array($sceneSettings["overlay"])) {
    if (isset($sceneSettings["overlay"]["left"])) {
        $stageStyle[] = "--selector-overlay-left: " . trim((string)$sceneSettings["overlay"]["left"]);
    }
    if (isset($sceneSettings["overlay"]["top"])) {
        $stageStyle[] = "--selector-overlay-top: " . trim((string)$sceneSettings["overlay"]["top"]);
    }
    if (isset($sceneSettings["overlay"]["width"])) {
        $stageStyle[] = "--selector-overlay-width: " . trim((string)$sceneSettings["overlay"]["width"]);
    }
}
if (isset($sceneSettings["mobile_overlay"]) && is_array($sceneSettings["mobile_overlay"])) {
    if (isset($sceneSettings["mobile_overlay"]["left"])) {
        $stageStyle[] = "--selector-overlay-left-mobile: " . trim((string)$sceneSettings["mobile_overlay"]["left"]);
    }
    if (isset($sceneSettings["mobile_overlay"]["top"])) {
        $stageStyle[] = "--selector-overlay-top-mobile: " . trim((string)$sceneSettings["mobile_overlay"]["top"]);
    }
    if (isset($sceneSettings["mobile_overlay"]["width"])) {
        $stageStyle[] = "--selector-overlay-width-mobile: " . trim((string)$sceneSettings["mobile_overlay"]["width"]);
    }
}
if (isset($sceneSettings["mobile_zoom"]) && trim((string)$sceneSettings["mobile_zoom"]) !== "") {
    $stageStyle[] = "--selector-scene-mobile-zoom: " . trim((string)$sceneSettings["mobile_zoom"]);
}
if (isset($sceneSettings["mobile_center_x"]) && trim((string)$sceneSettings["mobile_center_x"]) !== "") {
    $stageStyle[] = "--selector-scene-mobile-center-x: " . trim((string)$sceneSettings["mobile_center_x"]);
}
$stageStyleAttr = !empty($stageStyle) ? ' style="' . htmlspecialcharsbx(implode("; ", $stageStyle) . ";") . '"' : "";

$hasEntranceOne = false;
foreach ($entrances as $entrance) {
    if (isset($entrance["number"]) && trim((string)$entrance["number"]) === "1") {
        $hasEntranceOne = true;
        break;
    }
}

$scenePins = $entrances;
$sceneCards = $entrances;
$virtualEntranceOne = null;

if (!$hasEntranceOne && !empty($entrances)) {
    $virtualEntranceOne = $entrances[0];
    $virtualEntranceOne["id"] = "virtual-entrance-1";
    $virtualEntranceOne["number"] = "1";
    $virtualEntranceOne["title"] = "1 подъезд";
    $virtualEntranceOne["pin_x"] = "60";
    $virtualEntranceOne["pin_y"] = "25";
    $virtualEntranceOne["board_target_id"] = isset($entrances[0]["id"]) ? (string)$entrances[0]["id"] : "";
    $virtualEntranceOne["is_virtual"] = true;

    $scenePins[] = $virtualEntranceOne;
    $sceneCards[] = $virtualEntranceOne;
}
$popupProjectName = trim((string)$project["NAME"]);
$popupDeliveryLabel = trim((string)$project["CONSTRUCTION_SUBTITLE"]);
$popupPlaceholderImage = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";
$mapEmbedHtml = isset($project["MAP_EMBED_HTML"]) ? trim((string)$project["MAP_EMBED_HTML"]) : "";
?>
<section class="projects-genplan" aria-label="Выбор квартиры в проекте">
  <div
    class="projects-selector"
    data-project-selector
    data-project-selector-state="<?= htmlspecialcharsbx(json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
  >
    <div class="projects-selector__viewport">
      <div class="projects-selector__state projects-selector__state--scene" data-selector-view="scene">
        <div class="projects-selector__scene-stage<?= $mobileSceneImage !== '' ? ' projects-selector__scene-stage--has-mobile-image' : '' ?>"<?= $stageStyleAttr ?>>
          <picture class="projects-selector__scene-picture">
            <?php if ($mobileSceneImage !== ""): ?>
              <source media="(max-width: 640px)" srcset="<?= htmlspecialcharsbx($mobileSceneImage) ?>" />
            <?php endif; ?>
            <img
              class="projects-selector__scene-image"
              src="<?= htmlspecialcharsbx((string)$project["SCENE_IMAGE"]) ?>"
              alt="Сцена проекта"
              loading="lazy"
            />
          </picture>

          <?php if (trim((string)$project["SCENE_SVG"]) !== ""): ?>
            <div class="projects-selector__scene-overlay" data-scene-overlay aria-hidden="true">
              <div class="projects-selector__scene-overlay-frame">
                <?= $project["SCENE_SVG"] ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="projects-selector__scene-pins">
            <?php foreach ($scenePins as $index => $entrance): ?>
              <?php
              $entranceModifier = isset($entrance["number"]) ? preg_replace("/[^a-z0-9_-]+/i", "-", (string)$entrance["number"]) : (string)($index + 1);
              $entranceModifier = trim((string)$entranceModifier, "-");
              if ($entranceModifier === "") {
                  $entranceModifier = (string)($index + 1);
              }
              $pinLeft = isset($entrance["pin_x"]) ? (string)$entrance["pin_x"] : "";
              $pinTop = isset($entrance["pin_y"]) ? (string)$entrance["pin_y"] : "";
              $pinConfig = isset($sceneConfig["pins"][$entranceModifier]) && is_array($sceneConfig["pins"][$entranceModifier]) ? $sceneConfig["pins"][$entranceModifier] : array();
              $pinStyle = array(
                  "--pin-left-base: " . $pinLeft . "%",
                  "--pin-top-base: " . $pinTop . "%",
              );
              if (isset($pinConfig["desktop"]) && is_array($pinConfig["desktop"])) {
                  if (isset($pinConfig["desktop"]["left"])) {
                      $pinStyle[] = "--pin-left: " . trim((string)$pinConfig["desktop"]["left"]);
                  }
                  if (isset($pinConfig["desktop"]["top"])) {
                      $pinStyle[] = "--pin-top: " . trim((string)$pinConfig["desktop"]["top"]);
                  }
              }
              if (isset($pinConfig["mobile"]) && is_array($pinConfig["mobile"])) {
                  if (isset($pinConfig["mobile"]["left"])) {
                      $pinStyle[] = "--pin-left-mobile: " . trim((string)$pinConfig["mobile"]["left"]);
                  }
                  if (isset($pinConfig["mobile"]["top"])) {
                      $pinStyle[] = "--pin-top-mobile: " . trim((string)$pinConfig["mobile"]["top"]);
                  }
              }
              if (isset($pinConfig["card"]) && is_array($pinConfig["card"])) {
                  if (isset($pinConfig["card"]["side"])) {
                      $pinStyle[] = "--card-side: " . trim((string)$pinConfig["card"]["side"]);
                  }
                  if (isset($pinConfig["card"]["offset_x"])) {
                      $pinStyle[] = "--card-offset-x: " . trim((string)$pinConfig["card"]["offset_x"]);
                  }
                  if (isset($pinConfig["card"]["offset_y"])) {
                      $pinStyle[] = "--card-offset-y: " . trim((string)$pinConfig["card"]["offset_y"]);
                  }
              }
              ?>
              <button
                class="projects-selector__pin projects-selector__pin--entrance-<?= htmlspecialcharsbx($entranceModifier) ?><?= $index === 0 ? " is-active" : "" ?>"
                type="button"
                data-entrance-trigger="<?= htmlspecialcharsbx((string)$entrance["id"]) ?>"
                data-entrance-modifier="<?= htmlspecialcharsbx($entranceModifier) ?>"
                style="<?= htmlspecialcharsbx(implode("; ", $pinStyle) . ";") ?>"
              >
                <span class="projects-selector__pin-label"><?= htmlspecialcharsbx((string)$entrance["title"]) ?></span>
                <span class="projects-selector__pin-tail" aria-hidden="true"></span>
              </button>
            <?php endforeach; ?>
          </div>

          <?php foreach ($sceneCards as $index => $entrance): ?>
            <div
              class="projects-selector__building-card"
              data-entrance-card="<?= htmlspecialcharsbx((string)$entrance["id"]) ?>"
              hidden
            >
              <button
                class="projects-selector__popup-close projects-selector__popup-close--scene"
                type="button"
                data-selector-close-scene-card
                aria-label="Закрыть карточку подъезда"
              >
                ×
              </button>
              <div class="projects-selector__building-card-title"><?= htmlspecialcharsbx((string)$entrance["title"]) ?></div>
              <?php if (trim((string)$project["CONSTRUCTION_SUBTITLE"]) !== ""): ?>
                <div class="projects-selector__building-card-deadline">
                  <?= htmlspecialcharsbx((string)$project["CONSTRUCTION_SUBTITLE"]) ?>
                </div>
              <?php endif; ?>

              <?php if (trim((string)$entrance["subtitle"]) !== ""): ?>
                <div class="projects-selector__building-card-subtitle"><?= htmlspecialcharsbx((string)$entrance["subtitle"]) ?></div>
              <?php endif; ?>

              <div class="projects-selector__building-card-groups">
                <?php foreach ($entrance["room_groups"] as $group): ?>
                  <?php
                  $groupFilterValue = isset($group["filter_value"]) ? trim((string)$group["filter_value"]) : "";
                  $groupHref = "";
                  if ($groupFilterValue !== "" && trim((string)$project["CODE"]) !== "") {
                      $groupHref = "/apartments/?" . http_build_query(array(
                          "project" => (string)$project["CODE"],
                          "rooms" => $groupFilterValue,
                          "entrance" => isset($entrance["number"]) ? (string)$entrance["number"] : "",
                      ));
                  }
                  ?>
                  <?php if ($groupHref !== ""): ?>
                    <a class="projects-selector__building-group projects-selector__building-group--link" href="<?= htmlspecialcharsbx($groupHref) ?>">
                      <div class="projects-selector__building-group-main">
                        <span class="projects-selector__building-group-line">
                          <span class="projects-selector__building-group-label">
                            <?= htmlspecialcharsbx((string)$group["label"]) ?>
                          </span>
                          <span class="projects-selector__building-group-count" aria-label="<?= (int)$group["count"] ?> кв.">
                            <?= (int)$group["count"] ?> кв.
                          </span>
                        </span>
                      </div>
                      <div class="projects-selector__building-group-price">
                        <?php if ((float)$group["min_price"] > 0): ?>
                          от <?= htmlspecialcharsbx(szcubeProjectSelectorMoney($group["min_price"])) ?> &#8381;
                        <?php endif; ?>
                      </div>
                    </a>
                  <?php else: ?>
                    <div class="projects-selector__building-group">
                      <div class="projects-selector__building-group-main">
                        <span class="projects-selector__building-group-line">
                          <span class="projects-selector__building-group-label">
                            <?= htmlspecialcharsbx((string)$group["label"]) ?>
                          </span>
                          <span class="projects-selector__building-group-count" aria-label="<?= (int)$group["count"] ?> кв.">
                            <?= (int)$group["count"] ?> кв.
                          </span>
                        </span>
                      </div>
                      <div class="projects-selector__building-group-price">
                        <?php if ((float)$group["min_price"] > 0): ?>
                          от <?= htmlspecialcharsbx(szcubeProjectSelectorMoney($group["min_price"])) ?> &#8381;
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>

              <button
                class="projects-selector__building-card-action"
                type="button"
                data-selector-open-board="<?= htmlspecialcharsbx(isset($entrance["board_target_id"]) && (string)$entrance["board_target_id"] !== "" ? (string)$entrance["board_target_id"] : (string)$entrance["id"]) ?>"
              >
                Выбрать
              </button>
            </div>
          <?php endforeach; ?>

          <div class="projects-selector__view-switch projects-view-switch" role="tablist" aria-label="Вид отображения проекта">
            <button
              class="projects-view-switch__button"
              type="button"
              role="tab"
              aria-selected="false"
              data-selector-view-tab="scene"
              data-selector-open-scene
            >На плане</button>
            <button
              class="projects-view-switch__button"
              type="button"
              role="tab"
              aria-selected="false"
              aria-disabled="true"
              disabled
              data-selector-view-tab="map"
              data-selector-open-map
            >На карте</button>
          </div>
        </div>
      </div>

      <div class="projects-selector__state projects-selector__state--board" data-selector-view="board" hidden>
        <div class="projects-selector__board-stage">
            <div class="projects-selector__board-toolbar">
              <button class="projects-selector__board-button projects-selector__board-button--back" type="button" data-selector-back>
                Назад
              </button>

              <div class="projects-selector__board-headings">
                <div class="projects-selector__board-project-name"><?= htmlspecialcharsbx((string)$project["NAME"]) ?></div>

                <?php if (trim((string)$project["CONSTRUCTION_SUBTITLE"]) !== ""): ?>
                  <div class="projects-selector__board-deadline"><?= htmlspecialcharsbx((string)$project["CONSTRUCTION_SUBTITLE"]) ?></div>
                <?php endif; ?>
              </div>

              <div class="projects-selector__view-switch projects-view-switch" role="tablist" aria-label="Вид отображения проекта">
                <button
                  class="projects-view-switch__button"
                  type="button"
                  role="tab"
                  aria-selected="true"
                  data-selector-view-tab="scene"
                  data-selector-open-scene
                >На плане</button>
                <button
                  class="projects-view-switch__button"
                  type="button"
                  role="tab"
                  aria-selected="false"
                  aria-disabled="true"
                  disabled
                  data-selector-view-tab="map"
                  data-selector-open-map
                >На карте</button>
              </div>
            </div>

          <div class="projects-selector__board-content">
            <div class="projects-selector__lot-card" data-selector-lot-card hidden>
              <button
                class="projects-selector__popup-close projects-selector__popup-close--lot"
                type="button"
                data-selector-close-lot
                aria-label="Закрыть карточку квартиры"
              >
                ×
              </button>
              <a class="projects-selector__lot-card-link" data-lot-detail href="#">
                <article class="apartment-card">
                  <div class="apartment-card__head">
                    <div>
                      <span class="apartment-card__project" data-lot-project><?= htmlspecialcharsbx($popupProjectName) ?></span>
                      <span class="apartment-card__date" data-lot-delivery<?= $popupDeliveryLabel === "" ? " hidden" : "" ?>><?= htmlspecialcharsbx($popupDeliveryLabel) ?></span>
                    </div>
                    <button class="apartment-card__fav" type="button" aria-label="В избранное">
                      <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.37256 1.89355C5.22588 0.557201 3.30974 0.144211 1.873 1.36791C0.436265 2.5916 0.233992 4.63754 1.36227 6.08483C2.30036 7.28811 5.13934 9.826 6.0698 10.6474C6.17387 10.7393 6.22593 10.7853 6.28666 10.8033C6.33962 10.8191 6.39761 10.8191 6.45063 10.8033C6.51136 10.7853 6.56336 10.7393 6.66749 10.6474C7.59796 9.826 10.4369 7.28811 11.375 6.08483C12.5033 4.63754 12.3257 2.57873 10.8642 1.36791C9.40281 0.157083 7.51925 0.557201 6.37256 1.89355Z" stroke="#8C8C8C" stroke-width="1.27452" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </button>
                  </div>

                  <div class="apartment-card__plan">
                    <img
                      class="apartment-card__plan-image"
                      data-lot-image
                      src="<?= htmlspecialcharsbx($popupPlaceholderImage) ?>"
                      alt=""
                      hidden
                    />
                  </div>

                  <div class="apartment-card__meta" data-lot-meta hidden></div>
                  <div class="apartment-card__meta apartment-card__meta--secondary" data-lot-meta-secondary hidden></div>

                  <div class="apartment-card__price">
                    <span class="apartment-card__price-main" data-lot-price-main hidden></span>
                    <span class="apartment-card__price-old" data-lot-price-old hidden></span>
                  </div>

                  <div class="apartment-card__badges" data-lot-badges hidden></div>
                </article>
              </a>
            </div>

            <div class="projects-selector__checkerboards">
              <div class="projects-selector__board-entrance-label" data-selector-active-entrance></div>

              <?php foreach ($entrances as $index => $entrance): ?>
                <div
                  class="projects-selector__checkerboard<?= $index === 0 ? " is-active" : "" ?>"
                  data-entrance-board="<?= htmlspecialcharsbx((string)$entrance["id"]) ?>"
                  <?= $index === 0 ? "" : "hidden" ?>
                >
                  <div class="projects-selector__checkerboard-grid">
                    <div class="projects-selector__checkerboard-column">
                      <?php foreach ($entrance["checkerboard"]["rows"] as $row): ?>
                        <div class="projects-selector__checkerboard-row">
                          <div class="projects-selector__checkerboard-floor"><?= htmlspecialcharsbx(isset($row["label"]) ? (string)$row["label"] : (string)$row["number"]) ?></div>
                          <div
                            class="projects-selector__checkerboard-cells"
                            style="--checkerboard-columns: <?= isset($row["visible_columns"]) ? (int)$row["visible_columns"] : (int)$entrance["checkerboard"]["max_columns"] ?>;"
                          >
                            <?php foreach ((isset($row["visible_cells"]) && is_array($row["visible_cells"]) ? $row["visible_cells"] : $row["cells"]) as $cell): ?>
                              <?php if (is_array($cell)): ?>
                                <button
                                  class="projects-selector__lot is-<?= htmlspecialcharsbx((string)$cell["status_xml_id"]) ?>"
                                  type="button"
                                  <?php if ((string)$cell["status_xml_id"] === "sold"): ?>
                                  disabled
                                  aria-disabled="true"
                                  tabindex="-1"
                                  <?php endif; ?>
                                  data-flat-id="<?= (int)$cell["id"] ?>"
                                  data-flat-code="<?= htmlspecialcharsbx((string)$cell["code"]) ?>"
                                  data-flat-title="<?= htmlspecialcharsbx((string)$cell["title"]) ?>"
                                  data-flat-price="<?= htmlspecialcharsbx((string)$cell["price_total"]) ?>"
                                  data-flat-price-old="<?= htmlspecialcharsbx((string)$cell["price_old"]) ?>"
                                  data-flat-project="<?= htmlspecialcharsbx((string)$project["NAME"]) ?>"
                                  data-flat-rooms="<?= htmlspecialcharsbx((string)$cell["rooms"]) ?>"
                                  data-flat-area="<?= htmlspecialcharsbx((string)$cell["area_total"]) ?>"
                                  data-flat-badges="<?= htmlspecialcharsbx(str_replace("</", "<\\/", json_encode(isset($cell["badges"]) && is_array($cell["badges"]) ? $cell["badges"] : array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) ?>"
                                  data-flat-finish="<?= htmlspecialcharsbx((string)$cell["finish"]) ?>"
                                  data-flat-image="<?= htmlspecialcharsbx((string)$cell["plan_image"]) ?>"
                                  data-flat-image-alt="<?= htmlspecialcharsbx((string)$cell["plan_alt"]) ?>"
                                  data-flat-floor="<?= (int)$cell["floor"] ?>"
                                  data-flat-floor-to="<?= (int)$cell["floor_to"] ?>"
                                  data-flat-floor-short="<?= htmlspecialcharsbx((string)$cell["floor_short"]) ?>"
                                  data-flat-floor-display="<?= htmlspecialcharsbx((string)$cell["floor_display"]) ?>"
                                  data-flat-house-floors="<?= (int)$cell["house_floors"] ?>"
                                  data-flat-entrance="<?= htmlspecialcharsbx((string)$cell["entrance"]) ?>"
                                  data-flat-number="<?= htmlspecialcharsbx((string)$cell["number"]) ?>"
                                  data-flat-slot-id="<?= htmlspecialcharsbx((string)$cell["slot_id"]) ?>"
                                  data-flat-url="<?= htmlspecialcharsbx((string)$cell["url"]) ?>"
                                  data-flat-status="<?= htmlspecialcharsbx((string)$cell["status_xml_id"]) ?>"
                                  data-flat-delivery="<?= htmlspecialcharsbx((string)$project["CONSTRUCTION_SUBTITLE"]) ?>"
                                >
                                  <?= htmlspecialcharsbx((string)$cell["rooms_short"]) ?>
                                </button>
                              <?php else: ?>
                                <span class="projects-selector__lot projects-selector__lot--empty" aria-hidden="true"></span>
                              <?php endif; ?>
                            <?php endforeach; ?>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="projects-selector__board-footer">
            <div class="projects-selector__legend" aria-label="Обозначения статусов квартир">
              <span class="projects-selector__legend-item">
                <span class="projects-selector__legend-dot is-free" aria-hidden="true"></span>
                <span>Свободно</span>
              </span>
              <span class="projects-selector__legend-item">
                <span class="projects-selector__legend-dot is-booked" aria-hidden="true"></span>
                <span>Забронировано</span>
              </span>
              <span class="projects-selector__legend-item">
                <span class="projects-selector__legend-dot is-sold" aria-hidden="true"></span>
                <span>Продано</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="projects-selector__state projects-selector__state--map" data-selector-view="map" hidden>
        <div class="projects-selector__map-stage">
          <div class="projects-selector__board-toolbar">
            <button class="projects-selector__board-button projects-selector__board-button--back" type="button" data-selector-back-from-map>
              Назад
            </button>

            <div class="projects-selector__board-headings">
              <div class="projects-selector__board-project-name"><?= htmlspecialcharsbx((string)$project["NAME"]) ?></div>

              <?php if (trim((string)$project["CONSTRUCTION_SUBTITLE"]) !== ""): ?>
                <div class="projects-selector__board-deadline"><?= htmlspecialcharsbx((string)$project["CONSTRUCTION_SUBTITLE"]) ?></div>
              <?php endif; ?>
            </div>

            <div class="projects-selector__view-switch projects-view-switch" role="tablist" aria-label="Вид отображения проекта">
              <button
                class="projects-view-switch__button"
                type="button"
                role="tab"
                aria-selected="false"
                data-selector-view-tab="scene"
                data-selector-open-scene
              >На плане</button>
              <button
                class="projects-view-switch__button"
                type="button"
                role="tab"
                aria-selected="true"
                aria-disabled="true"
                disabled
                data-selector-view-tab="map"
                data-selector-open-map
              >На карте</button>
            </div>
          </div>

          <?php
          $mapClass = "szcube-map szcube-map--selector";
          $mapPlaceholderTitle = "Здесь будет карта проекта";
          $mapPlaceholderText = "Пока показываем заглушку. Позже сюда подключим реальную карту для этого ЖК.";
          include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/map-embed.php";
          ?>
        </div>
      </div>
    </div>
  </div>
</section>

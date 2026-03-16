<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$project = isset($arResult["PROJECT"]) && is_array($arResult["PROJECT"]) ? $arResult["PROJECT"] : array();
$entrances = isset($arResult["ENTRANCES"]) && is_array($arResult["ENTRANCES"]) ? $arResult["ENTRANCES"] : array();
if (empty($entrances)) {
    return;
}

$state = array(
    "initialEntranceId" => isset($arResult["INITIAL_ENTRANCE_ID"]) ? (string)$arResult["INITIAL_ENTRANCE_ID"] : "",
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
                  <div class="projects-selector__building-group">
                    <div class="projects-selector__building-group-main">
                      <span class="projects-selector__building-group-line">
                        <?= htmlspecialcharsbx((string)$group["label"]) ?>
                      </span>
                    </div>
                    <div class="projects-selector__building-group-price">
                      <?php if ((float)$group["min_price"] > 0): ?>
                        от <?= htmlspecialcharsbx(szcubeProjectSelectorMoney($group["min_price"])) ?> &#8381;
                      <?php endif; ?>
                    </div>
                  </div>
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

          <?php if (trim((string)$project["MAP_URL"]) !== ""): ?>
            <a class="projects-selector__scene-button projects-selector__scene-button--map" href="<?= htmlspecialcharsbx((string)$project["MAP_URL"]) ?>">
              <?= htmlspecialcharsbx((string)$project["MAP_LABEL"]) ?>
            </a>
          <?php else: ?>
            <button class="projects-selector__scene-button projects-selector__scene-button--map" type="button">
              <?= htmlspecialcharsbx((string)$project["MAP_LABEL"]) ?>
            </button>
          <?php endif; ?>
        </div>
      </div>

      <div class="projects-selector__state projects-selector__state--board" data-selector-view="board" hidden>
        <div class="projects-selector__board-stage">
          <div class="projects-selector__board-toolbar">
            <button class="projects-selector__board-button projects-selector__board-button--back" type="button" data-selector-back>
              Назад
            </button>

            <?php if (trim((string)$project["CONSTRUCTION_SUBTITLE"]) !== ""): ?>
              <div class="projects-selector__board-deadline"><?= htmlspecialcharsbx((string)$project["CONSTRUCTION_SUBTITLE"]) ?></div>
            <?php endif; ?>

            <button class="projects-selector__board-button projects-selector__board-button--filter" type="button">
              Фильтр
            </button>
          </div>

          <div class="projects-selector__board-entrance-badge" data-selector-active-entrance></div>

          <div class="projects-selector__board-content">
            <div class="projects-selector__lot-card" data-selector-lot-card hidden>
              <div class="projects-selector__lot-card-topline">
                <div class="projects-selector__lot-card-badge" data-lot-finish></div>
                <div class="projects-selector__lot-card-menu" aria-hidden="true">···</div>
              </div>
              <button
                class="projects-selector__popup-close projects-selector__popup-close--lot"
                type="button"
                data-selector-close-lot
                aria-label="Закрыть карточку квартиры"
              >
                ×
              </button>
              <div class="projects-selector__lot-card-media">
                <img src="" alt="" data-lot-image />
              </div>
              <a class="projects-selector__lot-card-body" href="#" data-lot-detail>
                <div class="projects-selector__lot-card-title" data-lot-title></div>
                <div class="projects-selector__lot-card-price" data-lot-price></div>
                <div class="projects-selector__lot-card-meta">
                  <span data-lot-project></span>
                </div>
                <div class="projects-selector__lot-card-meta is-secondary">
                  <span data-lot-floor></span>
                  <span data-lot-number></span>
                </div>
              </a>
            </div>

            <div class="projects-selector__checkerboards">
              <?php foreach ($entrances as $index => $entrance): ?>
                <div
                  class="projects-selector__checkerboard<?= $index === 0 ? " is-active" : "" ?>"
                  data-entrance-board="<?= htmlspecialcharsbx((string)$entrance["id"]) ?>"
                  <?= $index === 0 ? "" : "hidden" ?>
                >
                  <div class="projects-selector__checkerboard-grid">
                    <div class="projects-selector__checkerboard-column">
                      <div class="projects-selector__checkerboard-heading"><?= htmlspecialcharsbx((string)$entrance["title"]) ?></div>

                      <?php foreach ($entrance["checkerboard"]["rows"] as $row): ?>
                        <div class="projects-selector__checkerboard-row">
                          <div class="projects-selector__checkerboard-floor"><?= (int)$row["number"] ?></div>
                          <div
                            class="projects-selector__checkerboard-cells"
                            style="--checkerboard-columns: <?= (int)$entrance["checkerboard"]["max_columns"] ?>;"
                          >
                            <?php foreach ($row["cells"] as $cell): ?>
                              <?php if (is_array($cell)): ?>
                                <button
                                  class="projects-selector__lot is-<?= htmlspecialcharsbx((string)$cell["status_xml_id"]) ?>"
                                  type="button"
                                  data-flat-id="<?= (int)$cell["id"] ?>"
                                  data-flat-title="<?= htmlspecialcharsbx((string)$cell["title"]) ?>"
                                  data-flat-price="<?= htmlspecialcharsbx((string)$cell["price_total"]) ?>"
                                  data-flat-project="<?= htmlspecialcharsbx((string)$project["NAME"]) ?>"
                                  data-flat-finish="<?= htmlspecialcharsbx((string)$cell["finish"]) ?>"
                                  data-flat-image="<?= htmlspecialcharsbx((string)$cell["plan_image"]) ?>"
                                  data-flat-floor="<?= (int)$cell["floor"] ?>"
                                  data-flat-number="<?= htmlspecialcharsbx((string)$cell["number"]) ?>"
                                  data-flat-url="<?= htmlspecialcharsbx((string)$cell["url"]) ?>"
                                  data-flat-status="<?= htmlspecialcharsbx((string)$cell["status_xml_id"]) ?>"
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
            <div class="projects-selector__legend">
              <span class="projects-selector__legend-item">
                <i class="projects-selector__legend-dot is-free"></i>
                Свободно
              </span>
              <span class="projects-selector__legend-item">
                <i class="projects-selector__legend-dot is-booked"></i>
                Забронировано
              </span>
              <span class="projects-selector__legend-item">
                <i class="projects-selector__legend-dot is-sold"></i>
                Продано
              </span>
            </div>

            <?php if (count($entrances) > 1): ?>
              <div class="projects-selector__board-switches">
                <?php foreach ($entrances as $index => $entrance): ?>
                  <button
                    class="projects-selector__board-switch<?= $index === 0 ? " is-active" : "" ?>"
                    type="button"
                    data-selector-switch-entrance="<?= htmlspecialcharsbx((string)$entrance["id"]) ?>"
                  >
                    <?= htmlspecialcharsbx((string)$entrance["title"]) ?>
                  </button>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

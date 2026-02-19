<?php
define("PROJECTS_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Проекты — КУБ");
?>

<section class="projects-page">
  <div class="container">
    <h1 class="section-title">Проекты</h1>

    <div class="projects-about">
      <div class="projects-about__grid">
        <div class="projects-about__media">
          <img
            src="<?=SITE_TEMPLATE_PATH?>/img/projects/div.image-lazy__image.jpg"
            alt="Проект — изображение"
            loading="lazy"
          />
        </div>

        <h2 class="projects-about__title">
          <span class="projects-about__title-accent">ЖК «Название»</span>
          Ваша суперсила у моря
        </h2>

        <div class="projects-about__content">
          <div class="projects-about__text">
            <p>
              Lorem ipsum dolor sit amet, consectetur adipisicing elit. Quis
              nemo explicabo, perferendis dignissimos reprehenderit dolorum
              accusamus provident, doloribus vero eum, aliquid tempore.
            </p>
            <p>
              Lorem ipsum dolor sit amet consectetur adipisicing elit. Fuga
              possimus eveniet laudantium, porro tempora repellendus. Nisi
              accusamus, nobis eos sunt eaque aspernatur.
            </p>
          </div>

          <ul class="projects-about__features">
            <li class="projects-about__feature">
              <div class="projects-about__feature-label">Высокая ликвидность</div>
              <div class="projects-about__feature-value">Бизнес‑класс, мультиформат</div>
            </li>
            <li class="projects-about__feature">
              <div class="projects-about__feature-label">Благоустройство</div>
              <div class="projects-about__feature-value">Двор‑парк и зоны отдыха</div>
            </li>
            <li class="projects-about__feature">
              <div class="projects-about__feature-label">Сервис</div>
              <div class="projects-about__feature-value">Поддержка 24/7</div>
            </li>
            <li class="projects-about__feature">
              <div class="projects-about__feature-label">Инфраструктура</div>
              <div class="projects-about__feature-value">Школы, сад и магазины рядом</div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <section class="projects-genplan" aria-label="Генплан проекта">
      <div class="projects-genplan__viewport">
        <div class="projects-genplan__scene">
          <img
            class="projects-genplan__image"
            src="<?=SITE_TEMPLATE_PATH?>/img/projects/image_15.jpg"
            alt="Генплан проекта"
            loading="lazy"
          />

          <div class="projects-genplan__overlay" aria-hidden="true">
            <div class="projects-genplan__overlay-inner">
              <?=file_get_contents($_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/projects/Group.svg")?>
            </div>
          </div>

          <div class="projects-genplan__pins">
            <button class="projects-genplan__pin" type="button" style="top:26%;left:73%;">
              <span class="projects-genplan__pin-label">1 литер | 1 очередь</span>
              <svg class="projects-genplan__pin-icon" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M5.43301 7.25C5.24056 7.58333 4.75944 7.58333 4.56699 7.25L1.10289 1.25C0.910436 0.916669 1.151 0.500001 1.5359 0.500001L8.4641 0.5C8.849 0.5 9.08956 0.916666 8.89711 1.25L5.43301 7.25Z" fill="#009EAE" stroke="currentColor"></path>
              </svg>
            </button>
            <button class="projects-genplan__pin" type="button" style="top:15%;left:60%;">
              <span class="projects-genplan__pin-label">2 литер | 1 очередь</span>
              <svg class="projects-genplan__pin-icon" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M5.43301 7.25C5.24056 7.58333 4.75944 7.58333 4.56699 7.25L1.10289 1.25C0.910436 0.916669 1.151 0.500001 1.5359 0.500001L8.4641 0.5C8.849 0.5 9.08956 0.916666 8.89711 1.25L5.43301 7.25Z" fill="#009EAE" stroke="currentColor"></path>
              </svg>
            </button>
            <button class="projects-genplan__pin" type="button" style="top:12%;left:47%;">
              <span class="projects-genplan__pin-label">3 литер | 1 очередь</span>
              <svg class="projects-genplan__pin-icon" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M5.43301 7.25C5.24056 7.58333 4.75944 7.58333 4.56699 7.25L1.10289 1.25C0.910436 0.916669 1.151 0.500001 1.5359 0.500001L8.4641 0.5C8.849 0.5 9.08956 0.916666 8.89711 1.25L5.43301 7.25Z" fill="#009EAE" stroke="currentColor"></path>
              </svg>
            </button>
            <button class="projects-genplan__pin" type="button" style="top:39%;left:48%;">
              <span class="projects-genplan__pin-label">4 литер | 2 очередь</span>
              <svg class="projects-genplan__pin-icon" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M5.43301 7.25C5.24056 7.58333 4.75944 7.58333 4.56699 7.25L1.10289 1.25C0.910436 0.916669 1.151 0.500001 1.5359 0.500001L8.4641 0.5C8.849 0.5 9.08956 0.916666 8.89711 1.25L5.43301 7.25Z" fill="#009EAE" stroke="currentColor"></path>
              </svg>
            </button>
            <button class="projects-genplan__pin" type="button" style="top:42%;left:26%;">
              <span class="projects-genplan__pin-label">5 литер | 2 очередь</span>
              <svg class="projects-genplan__pin-icon" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M5.43301 7.25C5.24056 7.58333 4.75944 7.58333 4.56699 7.25L1.10289 1.25C0.910436 0.916669 1.151 0.500001 1.5359 0.500001L8.4641 0.5C8.849 0.5 9.08956 0.916666 8.89711 1.25L5.43301 7.25Z" fill="#009EAE" stroke="currentColor"></path>
              </svg>
            </button>
            <button class="projects-genplan__pin" type="button" style="top:26%;left:38%;">
              <span class="projects-genplan__pin-label">6 литер | 2 очередь</span>
              <svg class="projects-genplan__pin-icon" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M5.43301 7.25C5.24056 7.58333 4.75944 7.58333 4.56699 7.25L1.10289 1.25C0.910436 0.916669 1.151 0.500001 1.5359 0.500001L8.4641 0.5C8.849 0.5 9.08956 0.916666 8.89711 1.25L5.43301 7.25Z" fill="#009EAE" stroke="currentColor"></path>
              </svg>
            </button>
          </div>

          <button class="projects-genplan__filter" type="button">Фильтр</button>
        </div>
      </div>
    </section>
  </div>
</section>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>

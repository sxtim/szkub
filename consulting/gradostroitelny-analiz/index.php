<?php
define("GRAD_ANALYSIS_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("Градостроительный анализ");
$APPLICATION->SetPageProperty("title", "Градостроительный анализ земельного участка — КУБ");
$APPLICATION->SetPageProperty("robots", "noindex, nofollow");
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<section class="grad-analysis-hero" aria-labelledby="grad-analysis-hero-title">
  <div class="grad-analysis-hero__frame">
    <div class="grad-analysis-hero__content">
      <h1 class="grad-analysis-hero__title" id="grad-analysis-hero-title">
        Градостроительный<br>
        анализ земельного участка —
      </h1>

      <p class="grad-analysis-hero__subtitle">
        оценка перспектив участка<br>
        до принятия важных решений
      </p>

      <div class="grad-analysis-hero__intro">
        <p class="grad-analysis-hero__lead">
          Планируете приобрести земельный участок<br>
          или реализовать на нем инвестиционный проект?
        </p>

        <p>
          Проведите градостроительный анализ заранее — до покупки участка, начала проектирования и существенных финансовых вложений.
        </p>

        <p>
          Наши специалисты комплексно оценят градостроительный потенциал территории, изучат действующие документы, градостроительные ограничения и факторы, которые могут повлиять на возможность реализации вашего проекта.
        </p>
      </div>

      <div class="grad-analysis-hero__checklist">
        <h2 class="grad-analysis-hero__checklist-title">В рамках анализа мы поможем выявить:</h2>
        <ul class="grad-analysis-hero__checklist-list">
          <li><span aria-hidden="true">✓</span>действующие градостроительные ограничения и требования;</li>
          <li><span aria-hidden="true">✓</span>возможные препятствия для застройки и освоения участка;</li>
          <li><span aria-hidden="true">✓</span>особенности разрешенного использования территории;</li>
          <li><span aria-hidden="true">✓</span>факторы, влияющие на параметры будущего проекта;</li>
          <li><span aria-hidden="true">✓</span>потенциальные юридические, градостроительные и финансовые риски.</li>
        </ul>
      </div>
    </div>

    <div class="grad-analysis-hero__media">
      <img
        class="grad-analysis-hero__image"
        src="<?=SITE_TEMPLATE_PATH?>/img/grad-analysis/hero-consultant.png"
        alt="Консультант показывает план земельного участка"
        width="1536"
        height="1024"
      >

      <div class="grad-analysis-hero__overlay">
        <div class="grad-analysis-hero__result-card">
          <img
            class="grad-analysis-hero__result-icon"
            src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-document-check.svg"
            width="58"
            height="64"
            alt=""
            aria-hidden="true"
          >

          <p>
            По результатам вы получите структурированную<br>
            информацию, которая поможет оценить перспективы<br>
            участка и принять обоснованное решение до<br>
            совершения сделки или начала проектирования.
          </p>
        </div>

        <p class="grad-analysis-hero__media-message">
          Не принимайте решение вслепую — проведите<br>
          градостроительный анализ до покупки участка<br>
          или начала реализации проекта.
        </p>

        <button
          class="grad-analysis-hero__media-cta"
          type="button"
          aria-label="Заказать анализ участка"
          data-contact-open="contact"
          data-contact-title="Заказать анализ участка"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_hero"
        >
          <span class="grad-analysis-hero__media-cta-label">Заказать анализ участка</span>
          <span class="grad-analysis-hero__media-cta-arrow" aria-hidden="true">→</span>
        </button>
      </div>
    </div>
  </div>
</section>

<section class="grad-analysis-audiences" aria-labelledby="grad-analysis-audiences-title">
  <div class="container grad-analysis-audiences__container">
    <h2 class="grad-analysis-audiences__title" id="grad-analysis-audiences-title">
      Узнайте, что действительно можно сделать<br>
      с земельным участком — до того, как вложить в него деньги
    </h2>

    <p class="grad-analysis-audiences__description">
      Проверяем градостроительный потенциал участка, выявляем ограничения и оцениваем условия реализации проекта<br>
      до покупки, продажи, проектирования или инвестирования.
    </p>

    <div class="grad-analysis-audiences__top-grid">
      <article class="grad-analysis-audience-card">
        <div class="grad-analysis-audience-card__head">
          <img
            class="grad-analysis-audience-card__icon"
            src="<?=SITE_TEMPLATE_PATH?>/img/grad-analysis/audiences/icon-buyer.svg"
            alt=""
            width="36"
            height="36"
          >
          <h3 class="grad-analysis-audience-card__title">Я покупаю<br>земельный участок</h3>
          <img
            class="grad-analysis-audience-card__photo"
            src="<?=SITE_TEMPLATE_PATH?>/img/grad-analysis/audiences/buyer-card.png"
            alt="Земельный участок с домом"
            width="170"
            height="93"
          >
        </div>

        <p class="grad-analysis-audience-card__lead">Проверьте участок до сделки.</p>
        <p class="grad-analysis-audience-card__text">
          Поймите, соответствует ли его реальный градостроительный потенциал вашим планам и какие ограничения могут повлиять на будущий проект.
        </p>

        <div class="grad-analysis-audience-card__info">
          <h4 class="grad-analysis-audience-card__info-title">До покупки вы узнаете:</h4>
          <ul class="grad-analysis-audience-card__list">
            <li>какие параметры и сценарии развития территории необходимо учитывать;</li>
            <li>какие градостроительные ограничения действуют;</li>
            <li>какие вопросы требуют дополнительной проверки;</li>
            <li>какие риски могут повлиять на реализацию проекта.</li>
          </ul>
        </div>

        <button
          class="grad-analysis-audience-card__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Проверить участок перед покупкой"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_audience_buyer"
        >
          <span>Проверить участок перед покупкой</span>
          <span class="grad-analysis-audience-card__button-arrow" aria-hidden="true">→</span>
        </button>
      </article>

      <article class="grad-analysis-audience-card">
        <div class="grad-analysis-audience-card__head">
          <img
            class="grad-analysis-audience-card__icon"
            src="<?=SITE_TEMPLATE_PATH?>/img/grad-analysis/audiences/icon-developer.svg"
            alt=""
            width="34"
            height="36"
          >
          <h3 class="grad-analysis-audience-card__title">Я девелопер<br>или застройщик</h3>
          <img
            class="grad-analysis-audience-card__photo"
            src="<?=SITE_TEMPLATE_PATH?>/img/grad-analysis/audiences/developer-card.png"
            alt="Проект жилой застройки"
            width="170"
            height="93"
          >
        </div>

        <p class="grad-analysis-audience-card__lead">Оцените реализуемость проекта до<br>начала проектирования.</p>
        <p class="grad-analysis-audience-card__text">
          Получите исходную картину по территории и выявите факторы, влияющие на концепцию, параметры и сроки реализации проекта.
        </p>

        <div class="grad-analysis-audience-card__info">
          <h4 class="grad-analysis-audience-card__info-title">Анализ поможет:</h4>
          <ul class="grad-analysis-audience-card__list">
            <li>проверить исходные градостроительные условия;</li>
            <li>выявить ограничения;</li>
            <li>определить критические вопросы для дальнейшей проработки;</li>
            <li>учесть градостроительные факторы при формировании концепции.</li>
          </ul>
        </div>

        <button
          class="grad-analysis-audience-card__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Оценить участок под девелопмент"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_audience_developer"
        >
          <span>Оценить участок под девелопмент</span>
          <span class="grad-analysis-audience-card__button-arrow" aria-hidden="true">→</span>
        </button>
      </article>

      <article class="grad-analysis-audience-card">
        <div class="grad-analysis-audience-card__head">
          <img
            class="grad-analysis-audience-card__icon"
            src="<?=SITE_TEMPLATE_PATH?>/img/grad-analysis/audiences/icon-investor.svg"
            alt=""
            width="36"
            height="30"
          >
          <h3 class="grad-analysis-audience-card__title">Я инвестор</h3>
          <img
            class="grad-analysis-audience-card__photo"
            src="<?=SITE_TEMPLATE_PATH?>/img/grad-analysis/audiences/investor-card.png"
            alt="Земельный актив для инвестирования"
            width="170"
            height="93"
          >
        </div>

        <p class="grad-analysis-audience-card__lead">Проверьте земельный актив до<br>инвестиционного решения.</p>
        <p class="grad-analysis-audience-card__text">
          Градостроительный потенциал напрямую связан с возможными сценариями использования и ограничениями территории.
        </p>

        <div class="grad-analysis-audience-card__info">
          <h4 class="grad-analysis-audience-card__info-title">До принятия решения вы получите:</h4>
          <ul class="grad-analysis-audience-card__list">
            <li>структурированную информацию по участку;</li>
            <li>понимание ключевых градостроительных факторов;</li>
            <li>выявленные ограничения и препятствия;</li>
            <li>основу для дальнейшей оценки инвестиционного проекта.</li>
          </ul>
        </div>

        <button
          class="grad-analysis-audience-card__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Проверить инвестиционный актив"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_audience_investor"
        >
          <span>Проверить инвестиционный актив</span>
          <span class="grad-analysis-audience-card__button-arrow" aria-hidden="true">→</span>
        </button>
      </article>
    </div>

    <div class="grad-analysis-audiences__bottom-grid">
      <article class="grad-analysis-audience-card grad-analysis-audience-card--wide">
        <div class="grad-analysis-audience-card__head grad-analysis-audience-card__head--wide">
          <img
            class="grad-analysis-audience-card__icon grad-analysis-audience-card__icon--owner"
            src="<?=SITE_TEMPLATE_PATH?>/img/grad-analysis/audiences/icon-owner.svg"
            alt=""
            width="38"
            height="47"
          >
          <h3 class="grad-analysis-audience-card__title grad-analysis-audience-card__title--wide">Я владелец<br>земельного актива</h3>
          <img
            class="grad-analysis-audience-card__photo grad-analysis-audience-card__photo--wide"
            src="<?=SITE_TEMPLATE_PATH?>/img/grad-analysis/audiences/owner-card.png"
            alt="Земельный актив"
            width="345"
            height="99"
          >
        </div>

        <p class="grad-analysis-audience-card__lead">Узнайте, какой потенциал заложен в вашей земле.</p>
        <p class="grad-analysis-audience-card__text">
          Необязательно продавать участок или начинать строительство, чтобы принять решение о его дальнейшей судьбе. Анализ поможет оценить возможные сценарии развития территории.
        </p>

        <div class="grad-analysis-audience-card__info grad-analysis-audience-card__info--wide">
          <h4 class="grad-analysis-audience-card__info-title">Это может быть актуально, если вы планируете:</h4>
          <ul class="grad-analysis-audience-card__list">
            <li>продать участок;</li>
            <li>повысить привлекательность актива для инвестора;</li>
            <li>найти оптимальный сценарий развития;</li>
            <li>изменить концепцию использования;</li>
          </ul>
        </div>

        <button
          class="grad-analysis-audience-card__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Оценить потенциал земельного актива"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_audience_owner"
        >
          <span>Оценить потенциал земельного актива</span>
          <span class="grad-analysis-audience-card__button-arrow" aria-hidden="true">→</span>
        </button>
      </article>

      <article class="grad-analysis-audience-card grad-analysis-audience-card--wide">
        <div class="grad-analysis-audience-card__head grad-analysis-audience-card__head--wide grad-analysis-audience-card__head--realtor">
          <img
            class="grad-analysis-audience-card__icon grad-analysis-audience-card__icon--realtor"
            src="<?=SITE_TEMPLATE_PATH?>/img/grad-analysis/audiences/icon-realtor.svg"
            alt=""
            width="41"
            height="39"
          >
          <h3 class="grad-analysis-audience-card__title grad-analysis-audience-card__title--wide grad-analysis-audience-card__title--realtor">Я риэлтор</h3>
          <img
            class="grad-analysis-audience-card__photo grad-analysis-audience-card__photo--wide grad-analysis-audience-card__photo--realtor"
            src="<?=SITE_TEMPLATE_PATH?>/img/grad-analysis/audiences/realtor-card.png"
            alt="Консультация по земельному участку"
            width="366"
            height="99"
          >
        </div>

        <p class="grad-analysis-audience-card__lead">Получите больше информации об участке перед тем,<br>как предложить его клиенту.</p>
        <p class="grad-analysis-audience-card__text">
          Поймите, какой потенциал есть у участка, какие ограничения учитывать и на что обратить внимание при работе с покупателем или продавцом.
        </p>

        <div class="grad-analysis-audience-card__info-grid">
          <div class="grad-analysis-audience-card__info">
            <h4 class="grad-analysis-audience-card__info-title">Анализ поможет:</h4>
            <ul class="grad-analysis-audience-card__list">
              <li>проверить ключевые градостроительные параметры;</li>
              <li>выявить ограничения и факторы привлекательности;</li>
              <li>понять сценарии использования;</li>
            </ul>
          </div>

          <div class="grad-analysis-audience-card__info">
            <h4 class="grad-analysis-audience-card__info-title">Особенно важно, если участок:</h4>
            <ul class="grad-analysis-audience-card__list">
              <li>продается как инвестиционный актив;</li>
              <li>имеет потенциал под застройку;</li>
              <li>приобретается девелопером или инвестором;</li>
            </ul>
          </div>
        </div>

        <button
          class="grad-analysis-audience-card__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Проверить участок для клиента"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_audience_realtor"
        >
          <span>Проверить участок для клиента</span>
          <span class="grad-analysis-audience-card__button-arrow" aria-hidden="true">→</span>
        </button>
      </article>
    </div>
  </div>
</section>

<section class="grad-analysis-packages" aria-labelledby="grad-analysis-packages-title">
  <div class="container grad-analysis-packages__container">
    <h2 class="grad-analysis-packages__title" id="grad-analysis-packages-title">Пакеты анализа земельных участков</h2>
    <p class="grad-analysis-packages__description">
      Выберите формат анализа в зависимости от задачи, роли и глубины проработки участка.
    </p>

    <div class="grad-analysis-packages__grid">
      <article class="grad-analysis-package-card">
        <div class="grad-analysis-package-card__content">
          <div class="grad-analysis-package-card__audience">
            <span class="grad-analysis-package-card__icon grad-analysis-package-card__icon--buyer" aria-hidden="true"></span>
            <p>Для собственников, покупателей,<br>продавцов и риэлторов</p>
          </div>

          <h3 class="grad-analysis-package-card__title">Градостроительный анализ участка</h3>
          <p class="grad-analysis-package-card__description">Проводим экспресс-анализ земельного участка:</p>

          <ul class="grad-analysis-package-card__list">
            <li>проверяем Генеральный план;</li>
            <li>анализируем Правила землепользования и застройки (ПЗЗ);</li>
            <li>выявляем ограничения использования земельного участка, в том числе ЗОУИТ;</li>
            <li>определяем основные градостроительные параметры и ограничения.</li>
          </ul>
        </div>

        <p class="grad-analysis-package-card__term"><strong>Срок проведения:</strong> 1 рабочий день.</p>

        <button
          class="grad-analysis-package-card__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Уточнить стоимость градостроительного анализа"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_package_express"
        >
          <span>Уточнить стоимость</span>
          <span class="grad-analysis-package-card__button-arrow" aria-hidden="true">→</span>
        </button>
      </article>

      <article class="grad-analysis-package-card">
        <div class="grad-analysis-package-card__content">
          <div class="grad-analysis-package-card__audience">
            <span class="grad-analysis-package-card__icon grad-analysis-package-card__icon--developer" aria-hidden="true"></span>
            <p>Для девелоперов, инвесторов и<br>застройщиков</p>
          </div>

          <h3 class="grad-analysis-package-card__title">Анализ градостроительного<br>потенциала</h3>
          <p class="grad-analysis-package-card__description">
            Проводим комплексную предварительную оценку участка и его возможного использования.
          </p>
        </div>

        <p class="grad-analysis-package-card__term"><strong>Срок проведения:</strong> 3 рабочих дня.</p>

        <button
          class="grad-analysis-package-card__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Уточнить стоимость анализа градостроительного потенциала"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_package_potential"
        >
          <span>Уточнить стоимость</span>
          <span class="grad-analysis-package-card__button-arrow" aria-hidden="true">→</span>
        </button>
      </article>

      <article class="grad-analysis-package-card grad-analysis-package-card--audit">
        <div class="grad-analysis-package-card__content">
          <div class="grad-analysis-package-card__audience">
            <span class="grad-analysis-package-card__icon grad-analysis-package-card__icon--audit" aria-hidden="true"></span>
            <p>Для девелоперов, инвесторов и<br>застройщиков</p>
          </div>

          <h3 class="grad-analysis-package-card__title">
            Градостроительный аудит участка<br>
            под конкретный вид разрешенного<br>
            использования
          </h3>
          <p class="grad-analysis-package-card__description">
            Комплексный анализ участка с учетом планируемого проекта. В рамках аудита:
          </p>

          <ul class="grad-analysis-package-card__list">
            <li>проверяем Генплан, ПЗЗ, ограничения и ЗОУИТ;</li>
            <li>анализируем расположение, окружение и доступность;</li>
            <li>оцениваем социальную обеспеченность и рынок недвижимости;</li>
            <li>определяем потенциал под выбранный вид использования;</li>
          </ul>
        </div>

        <p class="grad-analysis-package-card__term"><strong>Срок проведения:</strong> до 45 рабочих дней.</p>

        <button
          class="grad-analysis-package-card__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Уточнить стоимость градостроительного аудита"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_package_audit"
        >
          <span>Уточнить стоимость</span>
          <span class="grad-analysis-package-card__button-arrow" aria-hidden="true">→</span>
        </button>
      </article>
    </div>
  </div>
</section>

<section class="grad-analysis-additional" aria-labelledby="grad-analysis-additional-title">
  <div class="container grad-analysis-additional__container">
    <h2 class="grad-analysis-additional__title" id="grad-analysis-additional-title">Дополнительные услуги</h2>

    <div class="grad-analysis-additional__grid">
      <article class="grad-analysis-additional-card">
        <div class="grad-analysis-additional-card__content">
          <div class="grad-analysis-additional-card__head">
            <img
              class="grad-analysis-additional-card__icon"
              src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-roadmap.svg"
              width="40"
              height="40"
              alt=""
              aria-hidden="true"
            >
            <h3 class="grad-analysis-additional-card__title">Дорожная карта реализации<br>проекта</h3>
          </div>

          <p class="grad-analysis-additional-card__description">
            Разрабатываем последовательный план<br>
            реализации проекта с указанием:
          </p>

          <ul class="grad-analysis-additional-card__list">
            <li>основных этапов;</li>
            <li>ориентировочных сроков;</li>
            <li>необходимых согласований и действий;</li>
          </ul>
        </div>

        <p class="grad-analysis-additional-card__term"><strong>Срок подготовки:</strong> 3 рабочих дня.</p>

        <button
          class="grad-analysis-additional-card__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Уточнить стоимость дорожной карты реализации проекта"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_additional_roadmap"
        >
          <span>Уточнить стоимость</span>
          <span class="grad-analysis-additional-card__button-arrow" aria-hidden="true">→</span>
        </button>
      </article>

      <article class="grad-analysis-additional-card">
        <div class="grad-analysis-additional-card__content">
          <div class="grad-analysis-additional-card__head">
            <img
              class="grad-analysis-additional-card__icon"
              src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-financial-model.svg"
              width="40"
              height="40"
              alt=""
              aria-hidden="true"
            >
            <h3 class="grad-analysis-additional-card__title">Финансовая модель проекта</h3>
          </div>

          <p class="grad-analysis-additional-card__description">
            Формируем финансовую модель проекта с учетом исходных параметров земельного участка и планируемого использования.
          </p>
        </div>

        <p class="grad-analysis-additional-card__term"><strong>Срок подготовки:</strong> после получения исходных<br>
          данных.</p>

        <button
          class="grad-analysis-additional-card__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Уточнить стоимость финансовой модели проекта"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_additional_financial_model"
        >
          <span>Уточнить стоимость</span>
          <span class="grad-analysis-additional-card__button-arrow" aria-hidden="true">→</span>
        </button>
      </article>

      <article class="grad-analysis-additional-card">
        <div class="grad-analysis-additional-card__content">
          <div class="grad-analysis-additional-card__head">
            <img
              class="grad-analysis-additional-card__icon"
              src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-engineering.svg"
              width="40"
              height="40"
              alt=""
              aria-hidden="true"
            >
            <h3 class="grad-analysis-additional-card__title">Проработка подключения к<br>инженерным сетям</h3>
          </div>

          <p class="grad-analysis-additional-card__description">
            Проводим предварительную проработку возможности подключения к инженерно-техническим сетям, анализ вариантов и основных условий подключения.
          </p>
        </div>

        <p class="grad-analysis-additional-card__term">
          <strong>Стоимость рассчитывается индивидуально.</strong><br>
          <strong>Срок проведения:</strong> до 30 рабочих дней.
        </p>

        <button
          class="grad-analysis-additional-card__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Уточнить стоимость проработки подключения к инженерным сетям"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_additional_engineering"
        >
          <span>Уточнить стоимость</span>
          <span class="grad-analysis-additional-card__button-arrow" aria-hidden="true">→</span>
        </button>
      </article>
    </div>
  </div>
</section>

<section class="grad-analysis-result" aria-labelledby="grad-analysis-result-title">
  <div class="container grad-analysis-result__container">
    <div class="grad-analysis-result__panel">
      <img
        class="grad-analysis-result__icon"
        src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-document-check.svg"
        width="58"
        height="64"
        alt=""
        aria-hidden="true"
      >

      <div class="grad-analysis-result__content">
        <h2 class="grad-analysis-result__title" id="grad-analysis-result-title">Результат</h2>
        <p class="grad-analysis-result__text">
          Вы получаете структурированную оценку земельного участка, его градостроительного потенциала,<br>
          существующих ограничений и возможностей реализации планируемого проекта.
        </p>
      </div>
    </div>
  </div>
</section>

<section class="grad-analysis-factors" aria-labelledby="grad-analysis-factors-title">
  <div class="container grad-analysis-factors__container">
    <h2 class="grad-analysis-factors__title" id="grad-analysis-factors-title">Что мы анализируем</h2>
    <p class="grad-analysis-factors__description">
      Мы изучаем комплекс факторов, которые могут повлиять на использование и развитие земельного участка:
    </p>

    <div class="grad-analysis-factors__grid">
      <article class="grad-analysis-factor">
        <img
          class="grad-analysis-factor__icon"
          src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-document-check.svg"
          width="36"
          height="40"
          alt=""
          aria-hidden="true"
        >
        <div class="grad-analysis-factor__content">
          <h3 class="grad-analysis-factor__title">Градостроительная<br>документация</h3>
          <p class="grad-analysis-factor__text">Действующие документы, параметры территории и установленные требования.</p>
        </div>
      </article>

      <article class="grad-analysis-factor">
        <img
          class="grad-analysis-factor__icon"
          src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-engineering.svg"
          width="40"
          height="40"
          alt=""
          aria-hidden="true"
        >
        <div class="grad-analysis-factor__content">
          <h3 class="grad-analysis-factor__title">Ограничения</h3>
          <p class="grad-analysis-factor__text">Факторы, способные ограничить возможности использования и застройки.</p>
        </div>
      </article>

      <article class="grad-analysis-factor">
        <img
          class="grad-analysis-factor__icon"
          src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-potential.svg"
          width="40"
          height="40"
          alt=""
          aria-hidden="true"
        >
        <div class="grad-analysis-factor__content">
          <h3 class="grad-analysis-factor__title">Градостроительный<br>потенциал</h3>
          <p class="grad-analysis-factor__text">Возможности и сценарии развития участка с учетом выявленных условий.</p>
        </div>
      </article>

      <article class="grad-analysis-factor">
        <img
          class="grad-analysis-factor__icon"
          src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-risks.svg"
          width="40"
          height="40"
          alt=""
          aria-hidden="true"
        >
        <div class="grad-analysis-factor__content">
          <h3 class="grad-analysis-factor__title">Риски реализации</h3>
          <p class="grad-analysis-factor__text">Обстоятельства, которые важно учитывать до покупки, проектирования или начала реализации проекта.</p>
        </div>
      </article>

      <article class="grad-analysis-factor">
        <img
          class="grad-analysis-factor__icon"
          src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-checklist.svg"
          width="40"
          height="40"
          alt=""
          aria-hidden="true"
        >
        <div class="grad-analysis-factor__content">
          <h3 class="grad-analysis-factor__title">Ключевые вопросы<br>для дальнейшей<br>проработки</h3>
          <p class="grad-analysis-factor__text">Что необходимо дополнительно проверить и учесть перед принятием решения.</p>
        </div>
      </article>
    </div>
  </div>
</section>

<section class="grad-analysis-timing" aria-labelledby="grad-analysis-timing-title">
  <div class="container grad-analysis-timing__container">
    <h2 class="grad-analysis-timing__title" id="grad-analysis-timing-title">
      Почему анализ нужно проводить до вложения денег?
    </h2>

    <div class="grad-analysis-timing__steps">
      <article class="grad-analysis-timing-step">
        <img
          class="grad-analysis-timing-step__icon"
          src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-deal.svg"
          width="40"
          height="40"
          alt=""
          aria-hidden="true"
        >
        <div class="grad-analysis-timing-step__content">
          <h3 class="grad-analysis-timing-step__title">До сделки</h3>
          <p class="grad-analysis-timing-step__text">
            Можно изменить решение о покупке или учесть обстоятельства в переговорах.
          </p>
        </div>
      </article>

      <article class="grad-analysis-timing-step">
        <img
          class="grad-analysis-timing-step__icon"
          src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-document-check.svg"
          width="40"
          height="40"
          alt=""
          aria-hidden="true"
        >
        <div class="grad-analysis-timing-step__content">
          <h3 class="grad-analysis-timing-step__title">До проектирования</h3>
          <p class="grad-analysis-timing-step__text">
            Можно сформировать концепцию с учетом реальных исходных условий.
          </p>
        </div>
      </article>

      <article class="grad-analysis-timing-step">
        <img
          class="grad-analysis-timing-step__icon"
          src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-potential.svg"
          width="40"
          height="40"
          alt=""
          aria-hidden="true"
        >
        <div class="grad-analysis-timing-step__content">
          <h3 class="grad-analysis-timing-step__title">До инвестирования</h3>
          <p class="grad-analysis-timing-step__text">
            Можно глубже оценить факторы, влияющие на перспективы земельного актива.
          </p>
        </div>
      </article>

      <article class="grad-analysis-timing-step">
        <img
          class="grad-analysis-timing-step__icon"
          src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-financial-model.svg"
          width="40"
          height="40"
          alt=""
          aria-hidden="true"
        >
        <div class="grad-analysis-timing-step__content">
          <h3 class="grad-analysis-timing-step__title">До продажи</h3>
          <p class="grad-analysis-timing-step__text">
            Можно аргументированно представить потенциал участка покупателю.
          </p>
        </div>
      </article>
    </div>

    <div class="grad-analysis-timing__conclusion">
      <img
        class="grad-analysis-timing__conclusion-icon"
        src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/icon-potential.svg"
        width="36"
        height="36"
        alt=""
        aria-hidden="true"
      >
      <p>Чем раньше выявлены ограничения, тем больше возможностей изменить решение с минимальными затратами.</p>
    </div>
  </div>
</section>

<section class="grad-analysis-cta" aria-labelledby="grad-analysis-cta-title">
  <div class="grad-analysis-cta__media" aria-hidden="true">
    <img
      class="grad-analysis-cta__image"
      src="<?= SITE_TEMPLATE_PATH ?>/img/grad-analysis/cta-house.webp"
      width="1040"
      height="585"
      alt=""
      loading="lazy"
      decoding="async"
    >
  </div>
  <div class="container grad-analysis-cta__container">
    <div class="grad-analysis-cta__main">
      <div class="grad-analysis-cta__lead">
        <h2 class="grad-analysis-cta__title" id="grad-analysis-cta-title">
          Не каждый земельный участок<br>
          одинаково ценен
        </h2>
        <p class="grad-analysis-cta__text">
          Цена земли — не единственный показатель<br>
          ее инвестиционной привлекательности.<br>
          Важно понимать, что именно можно реализовать<br>
          на участке, при каких условиях и какие<br>
          ограничения необходимо учитывать.
        </p>
      </div>

      <div class="grad-analysis-cta__action">
        <p class="grad-analysis-cta__action-text">
          Мы помогаем получить эту картину<br>
          до того, как решение о покупке, продаже<br>
          или развитии становится необратимым.
        </p>
        <button
          class="grad-analysis-cta__button"
          type="button"
          data-contact-open="contact"
          data-contact-title="Получить градостроительный анализ земельного участка"
          data-contact-type="consulting"
          data-contact-source="grad_analysis_cta"
        >
          <span>Получить градостроительный анализ</span>
          <span class="grad-analysis-cta__button-arrow" aria-hidden="true">→</span>
        </button>
      </div>
    </div>
  </div>
</section>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>

<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<div class="projects-modal-wrap modal-wrap-custom" data-benefit-modal hidden>
  <div class="projects-modal modal-wrap-container" role="dialog" aria-modal="true" aria-label="Преимущество проекта">
    <button class="projects-modal__close" type="button" aria-label="Закрыть" data-modal-close>
      <span class="projects-modal__close-text">
        <svg viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M1 1L9 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"></path>
          <path d="M9 1L1 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"></path>
        </svg>
      </span>
    </button>

    <div class="projects-benefit-modal">
      <div class="projects-benefit-modal__slider">
        <header class="projects-benefit-modal__header" aria-hidden="true">
          <h4 class="projects-benefit-modal__category" data-modal-category></h4>
        </header>

        <div class="projects-benefit-modal__swiper swiper" data-modal-swiper>
          <div class="swiper-wrapper" data-modal-wrapper></div>
        </div>

        <div class="projects-benefit-modal__nav" aria-label="Навигация по преимуществам">
          <div class="projects-benefit-modal__pagination" data-modal-pagination>1 / 1</div>

          <div class="projects-benefit-modal__controls" role="group" aria-label="Переключение преимуществ">
            <button class="projects-benefit-modal__navBtn" type="button" aria-label="Предыдущее" data-modal-prev>
              <svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M4.00049 6.00024L7.00049 3.00024V9.00024L4.00049 6.00024Z" fill="currentColor"></path>
              </svg>
            </button>
            <button class="projects-benefit-modal__navBtn" type="button" aria-label="Следующее" data-modal-next>
              <svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
if (!isset($pagination) || !is_array($pagination)) {
    return;
}

$pages = isset($pagination["pages"]) && is_array($pagination["pages"]) ? $pagination["pages"] : array();
if (empty($pages)) {
    return;
}

$prevUrl = isset($pagination["prev_url"]) ? trim((string)$pagination["prev_url"]) : "";
$nextUrl = isset($pagination["next_url"]) ? trim((string)$pagination["next_url"]) : "";
?>

<nav class="catalog-pagination" aria-label="Постраничная навигация">
  <?php if ($prevUrl !== ""): ?>
    <a class="catalog-pagination__arrow" href="<?= htmlspecialcharsbx($prevUrl) ?>" aria-label="Предыдущая страница">Назад</a>
  <?php endif; ?>

  <div class="catalog-pagination__pages">
    <?php foreach ($pages as $page): ?>
      <?php if (!is_array($page)) {
          continue;
      } ?>
      <?php if (($page["type"] ?? "") === "ellipsis"): ?>
        <span class="catalog-pagination__ellipsis" aria-hidden="true">…</span>
        <?php continue; ?>
      <?php endif; ?>

      <?php $number = isset($page["number"]) ? (int)$page["number"] : 0; ?>
      <?php if ($number <= 0) {
          continue;
      } ?>

      <?php if (!empty($page["current"])): ?>
        <span class="catalog-pagination__page is-active" aria-current="page"><?= $number ?></span>
      <?php else: ?>
        <a class="catalog-pagination__page" href="<?= htmlspecialcharsbx((string)($page["url"] ?? "")) ?>"><?= $number ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <?php if ($nextUrl !== ""): ?>
    <a class="catalog-pagination__arrow" href="<?= htmlspecialcharsbx($nextUrl) ?>" aria-label="Следующая страница">Далее</a>
  <?php endif; ?>
</nav>

<?
define("NEWS_PAGE", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("Новости");

$newsItems = array(
	array(
		"code" => "example-1",
		"title" => "Пример новости №1",
		"date" => "2026-02-21",
		"preview" => "Короткое описание новости (заглушка).",
		"image" => SITE_TEMPLATE_PATH . "/img/figma-2a4f429a-dd52-4323-ae0a-3f1bc0404ebc.png",
	),
	array(
		"code" => "example-2",
		"title" => "Пример новости №2",
		"date" => "2026-02-10",
		"preview" => "Короткое описание новости (заглушка).",
		"image" => SITE_TEMPLATE_PATH . "/img/figma-962f733c-d79a-402f-b82c-1e5b010739c3.png",
	),
	array(
		"code" => "example-3",
		"title" => "Пример новости №3",
		"date" => "2026-01-25",
		"preview" => "Короткое описание новости (заглушка).",
		"image" => SITE_TEMPLATE_PATH . "/img/figma-00c913e7-155b-407c-b698-3a6167b5fba3.png",
	),
);
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<section class="news">
	<div class="container">
		<h1 class="section-title"><? $APPLICATION->ShowTitle(false); ?></h1>

		<div class="news__cards">
			<? foreach ($newsItems as $item): ?>
				<a class="news-card" href="/news/<?= htmlspecialcharsbx($item["code"]) ?>/">
					<img src="<?= htmlspecialcharsbx($item["image"]) ?>" alt="Новость" />
					<time datetime="<?= htmlspecialcharsbx($item["date"]) ?>"><?= htmlspecialcharsbx($item["date"]) ?></time>
					<h3><?= htmlspecialcharsbx($item["title"]) ?></h3>
					<p><?= htmlspecialcharsbx($item["preview"]) ?></p>
				</a>
			<? endforeach; ?>
		</div>
	</div>
</section>

<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>

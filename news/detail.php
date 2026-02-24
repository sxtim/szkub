<?
define("NEWS_PAGE", true);
define("FOOTER_FLAT", true);

$code = isset($_REQUEST["code"]) ? (string)$_REQUEST["code"] : "";
$code = trim($code);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$newsItems = require $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/news-data.php";

$item = null;
if ($code !== "") {
	foreach ($newsItems as $candidate) {
		if (!is_array($candidate)) {
			continue;
		}
		if (isset($candidate["code"]) && (string)$candidate["code"] === $code) {
			$item = $candidate;
			break;
		}
	}
}

if (!$item) {
	CHTTP::SetStatus("404 Not Found");
	@define("ERROR_404", "Y");
	$APPLICATION->SetTitle("Новость не найдена");
} else {
	$APPLICATION->SetTitle($item["title"]);
}
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<? if ($item): ?>
	<?
	ob_start();
	$dateIso = isset($item["date"]) ? (string)$item["date"] : "";
	$dateText = $dateIso !== "" ? date("d.m.Y", strtotime($dateIso)) : "";
	?>
	<div class="news-detail">
		<div class="news-detail__meta">
			<time datetime="<?= htmlspecialcharsbx($dateIso) ?>"><?= htmlspecialcharsbx($dateText) ?></time>
		</div>

		<div class="news-detail__content">
			<? foreach ($item["detail"] as $block): ?>
				<?
				$type = isset($block["type"]) ? (string)$block["type"] : "";
				?>

				<? if ($type === "h3"): ?>
					<h3><?= htmlspecialcharsbx((string)$block["text"]) ?></h3>
				<? elseif ($type === "p"): ?>
					<p><?= htmlspecialcharsbx((string)$block["text"]) ?></p>
				<? elseif ($type === "ul"): ?>
					<ul>
						<? foreach ((array)$block["items"] as $li): ?>
							<li><?= htmlspecialcharsbx((string)$li) ?></li>
						<? endforeach; ?>
					</ul>
				<? elseif ($type === "ol"): ?>
					<ol>
						<? foreach ((array)$block["items"] as $li): ?>
							<li><?= htmlspecialcharsbx((string)$li) ?></li>
						<? endforeach; ?>
					</ol>
				<? endif; ?>
			<? endforeach; ?>
		</div>
	</div>
	<?
	$articleHeroExtra = ob_get_clean();

	$articleHero = array(
		"title" => $item["title"],
		"text" => isset($item["hero"]["text"]) && is_array($item["hero"]["text"]) ? $item["hero"]["text"] : array(),
		"list" => array(),
		"image" => $item["image"],
	);
	$articleHeroShowCta = false;
	include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/article-hero.php";
	?>
<? endif; ?>
<? if (!$item): ?>
	<section class="news-detail">
		<div class="container">
			<h1 class="section-title"><? $APPLICATION->ShowTitle(false); ?></h1>
			<p>Новость не найдена.</p>
		</div>
	</section>
<? endif; ?>

<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>

<?php
$page_title = 'Leagues';
require_once __DIR__ . '/includes/header.php';

$country_id = (int)($_GET['country'] ?? 0);
$leagues = get_leagues($country_id);

$active_country = null;
if ($country_id) {
    foreach ($countries as $c) {
        if ($c['id'] == $country_id) { $active_country = $c; break; }
    }
}

// Group by country
$grouped = [];
foreach ($leagues as $l) {
    $grouped[$l['country_name']][] = $l;
}
?>

<?php if ($active_country): ?>
<div class="breadcrumb">
  <span><a href="index.php">All Countries</a></span>
  <span><?= h($active_country['flag']) ?> <?= h($active_country['name']) ?></span>
</div>
<?php endif; ?>

<h1 class="page-title"><?= $active_country ? h($active_country['flag']) . ' ' . h($active_country['name']) : '⚽ All Leagues' ?></h1>

<?php foreach ($grouped as $country_name => $country_leagues): ?>
<div class="section">
  <div class="section-title"><?= h($country_leagues[0]['flag'] ?? '') ?> <?= h($country_name) ?></div>
  <div class="grid-3">
    <?php foreach ($country_leagues as $league): ?>
    <a class="league-card" href="league.php?id=<?= $league['id'] ?>">
      <span class="tier-badge">Div <?= h($league['tier']) ?></span>
      <div class="league-info">
        <div class="league-name"><?= h($league['name']) ?></div>
        <div class="league-country"><?= h($league['season']) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>

<?php if (empty($grouped)): ?>
<p class="text-muted">No leagues found.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

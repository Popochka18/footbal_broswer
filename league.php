<?php
$league_id = (int)($_GET['id'] ?? 0);
if (!$league_id) { header('Location: index.php'); exit; }

$league = get_league($league_id);
if (!$league) { http_response_code(404); die('League not found'); }

$page_title = $league['name'];
require_once __DIR__ . '/includes/header.php';

$teams = get_teams($league_id);

// Compute values
$teams_with_values = array_map(function($t) {
    $t['total_value'] = get_team_total_value($t['id']);
    $squad_st = get_db()->prepare('SELECT COUNT(*) FROM player_squad WHERE team_id=?');
    $squad_st->execute([$t['id']]);
    $t['squad_size'] = (int)$squad_st->fetchColumn();
    return $t;
}, $teams);

usort($teams_with_values, fn($a,$b) => $b['total_value'] <=> $a['total_value']);
?>

<div class="breadcrumb">
  <span><a href="index.php">Leagues</a></span>
  <span><a href="index.php?country=<?= $league['country_id'] ?>"><?= h($league['flag']) ?> <?= h($league['country_name']) ?></a></span>
  <span><?= h($league['name']) ?></span>
</div>

<h1 class="page-title"><?= h($league['flag']) ?> <?= h($league['name']) ?> <small class="text-muted" style="font-size:14px;font-weight:400"><?= h($league['season']) ?></small></h1>

<div class="stats-bar">
  <div class="stat-box">
    <div class="stat-val"><?= count($teams) ?></div>
    <div class="stat-label">Teams</div>
  </div>
  <div class="stat-box">
    <div class="stat-val"><?= array_sum(array_column($teams_with_values, 'squad_size')) ?></div>
    <div class="stat-label">Players</div>
  </div>
  <div class="stat-box">
    <div class="stat-val"><?= format_value(array_sum(array_column($teams_with_values, 'total_value'))) ?></div>
    <div class="stat-label">Total Value</div>
  </div>
</div>

<div class="section">
  <div class="section-title">Teams by Market Value</div>
  <div class="table-wrap card">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Club</th>
          <th>City</th>
          <th>Stadium</th>
          <th>Founded</th>
          <th>Squad</th>
          <th class="num">Total Value</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($teams_with_values as $i => $team): ?>
        <tr>
          <td class="center text-muted"><?= $i+1 ?></td>
          <td><a href="team.php?id=<?= $team['id'] ?>" style="font-weight:600"><?= h($team['name']) ?></a></td>
          <td><?= h($team['city']) ?></td>
          <td><?= h($team['stadium']) ?></td>
          <td><?= $team['founded'] ?: '-' ?></td>
          <td class="center"><?= $team['squad_size'] ?></td>
          <td class="num value-cell"><?= format_value($team['total_value']) ?></td>
          <td><a href="team.php?id=<?= $team['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="section grid-3">
  <?php foreach ($teams as $team): ?>
  <a class="team-card" href="team.php?id=<?= $team['id'] ?>">
    <div class="player-photo-placeholder" style="width:60px;height:60px;font-size:28px;margin:0 auto 8px">🏟</div>
    <div class="team-name"><?= h($team['name']) ?></div>
    <div class="team-value"><?= format_value(get_team_total_value($team['id'])) ?></div>
  </a>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

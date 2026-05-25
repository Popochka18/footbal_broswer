<?php
$root = '../';
$page_title = 'Info Editor';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$n_countries = $db->query('SELECT COUNT(*) FROM countries')->fetchColumn();
$n_leagues   = $db->query('SELECT COUNT(*) FROM leagues')->fetchColumn();
$n_teams     = $db->query('SELECT COUNT(*) FROM teams')->fetchColumn();
$n_players   = $db->query('SELECT COUNT(*) FROM players')->fetchColumn();
$n_transfers = $db->query('SELECT COUNT(*) FROM transfers')->fetchColumn();
?>

<h1 class="page-title">&#9998; Info Editor</h1>

<div class="admin-layout">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div>
    <div class="stats-bar">
      <div class="stat-box"><div class="stat-val"><?= $n_countries ?></div><div class="stat-label">Countries</div></div>
      <div class="stat-box"><div class="stat-val"><?= $n_leagues ?></div><div class="stat-label">Leagues</div></div>
      <div class="stat-box"><div class="stat-val"><?= $n_teams ?></div><div class="stat-label">Teams</div></div>
      <div class="stat-box"><div class="stat-val"><?= $n_players ?></div><div class="stat-label">Players</div></div>
      <div class="stat-box"><div class="stat-val"><?= $n_transfers ?></div><div class="stat-label">Transfers</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div class="card">
        <div class="card-header">Quick Actions</div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
          <a href="edit_player.php" class="btn btn-primary">+ Add Player</a>
          <a href="edit_team.php" class="btn btn-primary">+ Add Team</a>
          <a href="edit_league.php" class="btn btn-primary">+ Add League</a>
          <a href="edit_transfer.php" class="btn btn-primary">+ Add Transfer</a>
          <a href="edit_country.php" class="btn btn-outline">+ Add Country</a>
        </div>
      </div>
      <div class="card">
        <div class="card-header">Browse</div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
          <a href="countries.php" class="btn btn-secondary">Manage Countries</a>
          <a href="leagues.php" class="btn btn-secondary">Manage Leagues</a>
          <a href="teams.php" class="btn btn-secondary">Manage Teams</a>
          <a href="players.php" class="btn btn-secondary">Manage Players</a>
          <a href="transfers.php" class="btn btn-secondary">Manage Transfers</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

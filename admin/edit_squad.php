<?php
require_once __DIR__ . '/../includes/functions.php';
$root = '../';
$page_title = 'Manage Squad';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$team_id = (int)($_GET['team_id'] ?? 0);
if (!$team_id) redirect('teams.php');
$team = get_team($team_id);
if (!$team) { http_response_code(404); die('Team not found'); }

// Remove player from squad
if (isset($_GET['remove'])) {
    csrf_check();
    $db->prepare('DELETE FROM player_squad WHERE id=?')->execute([(int)$_GET['remove']]);
    redirect('edit_squad.php?team_id=' . $team_id . '&msg=removed');
}

// Add player to squad
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_player'])) {
    csrf_check();
    $pid     = (int)$_POST['player_id'];
    $jersey  = (int)($_POST['jersey_number'] ?? 0) ?: null;
    $raw     = trim($_POST['market_value_raw'] ?? '');
    $mv      = $raw !== '' ? format_value_raw($raw) : 0;
    $joined  = trim($_POST['joined'] ?? '');
    $contract= trim($_POST['contract_until'] ?? '');
    $loan    = isset($_POST['loan']) ? 1 : 0;
    $season  = trim($_POST['season'] ?? '2025/26');

    if (!$pid) $errors[] = 'Select a player.';
    if (!$errors) {
        try {
            $db->prepare('INSERT INTO player_squad(player_id,team_id,season,jersey_number,market_value,joined,contract_until,loan) VALUES(?,?,?,?,?,?,?,?)')
               ->execute([$pid, $team_id, $season, $jersey, $mv, $joined, $contract, $loan]);
        } catch (Exception $e) {
            $errors[] = 'Player already in squad for that season.';
        }
        if (!$errors) redirect('edit_squad.php?team_id=' . $team_id . '&msg=added');
    }
}

// Update market value inline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_value'])) {
    csrf_check();
    $squad_id = (int)$_POST['squad_id'];
    $raw = trim($_POST['market_value_raw'] ?? '');
    $mv  = format_value_raw($raw);
    $db->prepare('UPDATE player_squad SET market_value=? WHERE id=?')->execute([$mv, $squad_id]);
    redirect('edit_squad.php?team_id=' . $team_id . '&msg=updated');
}

$squad = get_squad($team_id);
$squad_full = $db->prepare('SELECT ps.* FROM player_squad ps WHERE ps.team_id=?');
$squad_full->execute([$team_id]);
$squad_full = $squad_full->fetchAll();

// Players not yet in this squad
$in_ids = array_column($squad_full, 'player_id');
$placeholders = $in_ids ? implode(',', array_fill(0, count($in_ids), '?')) : '0';
$available = $db->prepare("SELECT * FROM players WHERE id NOT IN ($placeholders) ORDER BY name");
$available->execute($in_ids ?: []);
$available = $available->fetchAll();

$msg = $_GET['msg'] ?? '';
$positions = ['GK','CB','LB','RB','LWB','RWB','CDM','CM','CAM','LM','RM','LW','RW','CF','ST'];

$groups = [];
foreach ($squad as $p) {
    $groups[position_group($p['position'])][] = $p;
}
$order = ['Goalkeepers','Defenders','Midfielders','Forwards','Other'];
uksort($groups, fn($a,$b) => array_search($a,$order) - array_search($b,$order));
?>

<h1 class="page-title">Squad: <?= h($team['name']) ?></h1>

<div class="admin-layout">
  <?php include 'sidebar.php'; ?>
  <div>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <?php if ($msg): ?><div class="alert alert-success" data-auto-dismiss>Done.</div><?php endif; ?>

    <div style="margin-bottom:12px;display:flex;gap:8px">
      <a href="../team.php?id=<?= $team_id ?>" class="btn btn-outline btn-sm">&#8592; View Team Page</a>
      <a href="edit_team.php?id=<?= $team_id ?>" class="btn btn-outline btn-sm">Edit Club Info</a>
    </div>

    <!-- Current squad table with inline value edit -->
    <div class="section">
      <div class="section-title">Current Squad (<?= count($squad) ?>)</div>
      <?php if ($squad): ?>
      <div class="table-wrap card">
        <table class="data-table">
          <thead><tr><th>#</th><th>Player</th><th>Pos</th><th>Age</th><th>Joined</th><th>Contract</th><th class="num">Market Value</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($groups as $gname => $gplayers): ?>
          <tr class="pos-group-row"><td colspan="8"><?= h($gname) ?></td></tr>
          <?php
          // Get squad_id for each player
          foreach ($gplayers as $p):
              $sq_row = $db->prepare('SELECT id FROM player_squad WHERE player_id=? AND team_id=?');
              $sq_row->execute([$p['id'], $team_id]);
              $sq_id = (int)$sq_row->fetchColumn();
          ?>
          <tr>
            <td class="center"><span class="jersey"><?= $p['jersey_number'] ?: '?' ?></span></td>
            <td><a href="../player.php?id=<?= $p['id'] ?>"><strong><?= h($p['name']) ?></strong></a><?= $p['loan'] ? ' <span class="loan-badge">LOAN</span>' : '' ?></td>
            <td><span class="position-badge"><?= h($p['position']) ?></span></td>
            <td><?= $p['birth_date'] ? age($p['birth_date']) : '-' ?></td>
            <td><?= $p['joined'] ? date('d.m.Y', strtotime($p['joined'])) : '-' ?></td>
            <td><?= $p['contract_until'] ? date('d.m.Y', strtotime($p['contract_until'])) : '-' ?></td>
            <td class="num">
              <form method="post" style="display:flex;align-items:center;gap:4px;justify-content:flex-end">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="squad_id" value="<?= $sq_id ?>">
                <input name="market_value_raw" value="<?= h(format_value($p['market_value'])) ?>" style="width:80px;border:1px solid #ccc;border-radius:3px;padding:3px 6px;font-size:12px;text-align:right">
                <button name="update_value" type="submit" class="btn btn-primary btn-sm" title="Save value">&#10003;</button>
              </form>
            </td>
            <td>
              <a href="edit_player.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
              <a href="edit_squad.php?team_id=<?= $team_id ?>&remove=<?= $sq_id ?>&csrf=<?= csrf_token() ?>" class="btn btn-danger btn-sm" data-confirm="Remove <?= h($p['name']) ?> from squad?">Remove</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <p class="text-muted">No players yet.</p>
      <?php endif; ?>
    </div>

    <!-- Add player to squad -->
    <div class="section">
      <div class="section-title">Add Player to Squad</div>
      <div class="form-card">
        <form method="post">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="form-grid">
            <div class="form-group">
              <label>Player</label>
              <select name="player_id" required>
                <option value="">— Select player —</option>
                <?php foreach ($available as $pl): ?>
                <option value="<?= $pl['id'] ?>"><?= h($pl['name']) ?> (<?= h($pl['position']) ?>, <?= h($pl['nationality']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group"><label>Season</label><input name="season" value="2025/26" placeholder="2025/26"></div>
            <div class="form-group"><label>Jersey #</label><input type="number" name="jersey_number" min="1" max="99"></div>
            <div class="form-group">
              <label>Market Value</label>
              <input name="market_value_raw" placeholder="e.g. 5M, 500K">
            </div>
            <div class="form-group"><label>Joined</label><input type="date" name="joined"></div>
            <div class="form-group"><label>Contract Until</label><input type="date" name="contract_until"></div>
            <div class="form-group" style="justify-content:flex-end">
              <label>&nbsp;</label>
              <label style="display:flex;align-items:center;gap:6px;font-size:14px;text-transform:none;cursor:pointer">
                <input type="checkbox" name="loan"> On loan
              </label>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" name="add_player" class="btn btn-primary">Add to Squad</button>
            <a href="edit_player.php" class="btn btn-outline">+ Create New Player</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

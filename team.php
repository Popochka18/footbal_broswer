<?php
$team_id = (int)($_GET['id'] ?? 0);
if (!$team_id) { header('Location: index.php'); exit; }

$team = get_team($team_id);
if (!$team) { http_response_code(404); die('Team not found'); }

$page_title = $team['name'];
require_once __DIR__ . '/includes/header.php';

$squad = get_squad($team_id);
$total_value = get_team_total_value($team_id);

// Group by position
$groups = [];
foreach ($squad as $player) {
    $g = position_group($player['position']);
    $groups[$g][] = $player;
}
$order = ['Goalkeepers','Defenders','Midfielders','Forwards','Other'];
uksort($groups, fn($a,$b) => array_search($a,$order) - array_search($b,$order));

// Recent transfers
$recent_transfers = get_db()->prepare('
    SELECT tr.*, p.name AS player_name, p.position,
           tf.name AS from_name, tt.name AS to_name
    FROM transfers tr
    JOIN players p ON p.id=tr.player_id
    LEFT JOIN teams tf ON tf.id=tr.from_team_id
    LEFT JOIN teams tt ON tt.id=tr.to_team_id
    WHERE tr.from_team_id=? OR tr.to_team_id=?
    ORDER BY tr.transfer_date DESC LIMIT 10
');
$recent_transfers->execute([$team_id, $team_id]);
$transfers = $recent_transfers->fetchAll();
?>

<div class="breadcrumb">
  <span><a href="index.php">Leagues</a></span>
  <span><a href="league.php?id=<?= $team['league_id'] ?>"><?= h($team['country_name']) ?> &mdash; <?= h($team['league_name']) ?></a></span>
  <span><?= h($team['name']) ?></span>
</div>

<div style="display:flex;align-items:flex-start;gap:24px;margin-bottom:24px;flex-wrap:wrap">
  <div class="player-photo-placeholder" style="width:100px;height:100px;font-size:48px;flex-shrink:0">🏟</div>
  <div style="flex:1">
    <h1 class="player-name"><?= h($team['name']) ?></h1>
    <div class="player-value-big"><?= format_value($total_value) ?></div>
    <div class="info-grid">
      <?php if ($team['city']): ?>
      <div class="info-item"><label>City</label><span><?= h($team['city']) ?></span></div>
      <?php endif; ?>
      <?php if ($team['stadium']): ?>
      <div class="info-item"><label>Stadium</label><span><?= h($team['stadium']) ?></span></div>
      <?php endif; ?>
      <?php if ($team['founded']): ?>
      <div class="info-item"><label>Founded</label><span><?= h($team['founded']) ?></span></div>
      <?php endif; ?>
      <div class="info-item"><label>League</label><span><?= h($team['league_name']) ?></span></div>
      <div class="info-item"><label>Squad</label><span><?= count($squad) ?> players</span></div>
    </div>
    <?php if ($team['info']): ?>
    <p style="margin-top:10px;color:#555"><?= h($team['info']) ?></p>
    <?php endif; ?>
  </div>
  <a href="admin/edit_team.php?id=<?= $team_id ?>" class="btn btn-outline btn-sm">&#9998; Edit</a>
</div>

<!-- Squad -->
<div class="section">
  <div class="section-title">Squad <?= count($squad) ? '(' . count($squad) . ' players)' : '' ?></div>
  <?php if (empty($squad)): ?>
  <p class="text-muted">No players in squad. <a href="admin/edit_squad.php?team_id=<?= $team_id ?>">Add players</a>.</p>
  <?php else: ?>
  <div class="table-wrap card">
    <table class="data-table">
      <thead>
        <tr>
          <th class="center">#</th>
          <th>Player</th>
          <th>Nat.</th>
          <th>Pos</th>
          <th>Age</th>
          <th>Foot</th>
          <th>Joined</th>
          <th>Contract</th>
          <th class="num">Market Value</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($groups as $group_name => $group_players): ?>
        <tr class="pos-group-row"><td colspan="9"><?= h($group_name) ?></td></tr>
        <?php foreach ($group_players as $p): ?>
        <tr class="searchable-row">
          <td class="center">
            <?php if ($p['jersey_number']): ?>
            <span class="jersey"><?= $p['jersey_number'] ?></span>
            <?php else: ?>&mdash;<?php endif; ?>
          </td>
          <td>
            <a href="player.php?id=<?= $p['id'] ?>" style="font-weight:600"><?= h($p['name']) ?></a>
            <?php if ($p['loan']): ?> <span class="loan-badge">LOAN</span><?php endif; ?>
          </td>
          <td><?= h($p['nationality']) ?></td>
          <td><span class="position-badge"><?= h($p['position']) ?></span></td>
          <td><?= $p['birth_date'] ? age($p['birth_date']) : '-' ?></td>
          <td><?= h($p['foot']) ?></td>
          <td><?= $p['joined'] ? date('d.m.Y', strtotime($p['joined'])) : '-' ?></td>
          <td><?= $p['contract_until'] ? date('d.m.Y', strtotime($p['contract_until'])) : '-' ?></td>
          <td class="num value-cell"><?= format_value($p['market_value']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="8" style="text-align:right;font-weight:700;padding:10px 12px;color:#555">Total Squad Value:</td>
          <td class="num value-cell" style="font-size:16px"><?= format_value($total_value) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Transfers -->
<?php if ($transfers): ?>
<div class="section">
  <div class="section-title">Transfer History</div>
  <div class="table-wrap card">
    <table class="data-table">
      <thead>
        <tr><th>Date</th><th>Player</th><th>Pos</th><th>From</th><th>To</th><th>Season</th><th class="num">Fee</th></tr>
      </thead>
      <tbody>
        <?php foreach ($transfers as $tr): ?>
        <tr>
          <td><?= $tr['transfer_date'] ? date('d.m.Y', strtotime($tr['transfer_date'])) : '-' ?></td>
          <td><a href="player.php?id=<?= $tr['player_id'] ?>"><?= h($tr['player_name']) ?></a></td>
          <td><span class="position-badge"><?= h($tr['position']) ?></span></td>
          <td><?= $tr['from_name'] ? h($tr['from_name']) : '<em class="text-muted">—</em>' ?></td>
          <td><?= $tr['to_name'] ? h($tr['to_name']) : '<em class="text-muted">—</em>' ?></td>
          <td><?= h($tr['season']) ?></td>
          <td class="num <?= $tr['fee_type'] === 'free' ? 'transfer-fee-free' : ($tr['fee_type'] === 'loan' ? 'transfer-fee-loan' : 'transfer-fee-paid') ?>">
            <?= $tr['fee_type'] === 'free' ? 'Free' : ($tr['fee_type'] === 'loan' ? 'Loan' : format_value($tr['fee'])) ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div style="margin-top:16px">
  <a href="admin/edit_squad.php?team_id=<?= $team_id ?>" class="btn btn-primary">&#9998; Manage Squad</a>
  <a href="admin/edit_team.php?id=<?= $team_id ?>" class="btn btn-secondary" style="margin-left:8px">&#9998; Edit Club Info</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

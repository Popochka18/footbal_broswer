<?php
require_once __DIR__ . '/includes/functions.php';

$player_id = (int)($_GET['id'] ?? 0);
if (!$player_id) { header('Location: index.php'); exit; }

$player = get_player($player_id);
if (!$player) { http_response_code(404); die('Player not found'); }

$page_title = $player['name'];
require_once __DIR__ . '/includes/header.php';

$player_teams = get_player_teams($player_id);
$transfers = get_transfers($player_id);

// Current team (first squad entry)
$current = $player_teams[0] ?? null;
$current_value = $current ? $current['market_value'] : 0;
?>

<div class="breadcrumb">
  <span><a href="index.php">Leagues</a></span>
  <?php if ($current): ?>
  <span><a href="league.php?id=<?= h($current['team_id']) ?>"><?= h($current['league_name']) ?></a></span>
  <span><a href="team.php?id=<?= $current['team_id'] ?>"><?= h($current['team_name']) ?></a></span>
  <?php endif; ?>
  <span><?= h($player['name']) ?></span>
</div>

<div class="player-profile">
  <div>
    <?php if ($player['image']): ?>
    <img class="player-photo" src="<?= h($player['image']) ?>" alt="<?= h($player['name']) ?>">
    <?php else: ?>
    <div class="player-photo-placeholder">👤</div>
    <?php endif; ?>
    <?php if ($current): ?>
    <div style="margin-top:10px;text-align:center">
      <span class="jersey" style="width:36px;height:36px;line-height:36px;font-size:15px"><?= $current['jersey_number'] ?: '?' ?></span>
      <div style="margin-top:4px;font-size:13px;color:#666"><?= h($current['team_name']) ?></div>
    </div>
    <?php endif; ?>
  </div>
  <div>
    <h1 class="player-name"><?= h($player['name']) ?></h1>
    <?php if ($player['full_name'] && $player['full_name'] !== $player['name']): ?>
    <div style="color:#888;margin-bottom:6px;font-size:13px"><?= h($player['full_name']) ?></div>
    <?php endif; ?>
    <div class="player-value-big"><?= format_value($current_value) ?></div>

    <div class="info-grid">
      <div class="info-item">
        <label>Date of Birth</label>
        <span>
          <?= $player['birth_date'] ? date('d.m.Y', strtotime($player['birth_date'])) : '-' ?>
          <?= $player['birth_date'] ? '(' . age($player['birth_date']) . ')' : '' ?>
        </span>
      </div>
      <?php if ($player['birth_place']): ?>
      <div class="info-item"><label>Place of Birth</label><span><?= h($player['birth_place']) ?></span></div>
      <?php endif; ?>
      <div class="info-item"><label>Nationality</label><span><?= h($player['nationality']) ?><?= $player['nationality2'] ? ', ' . h($player['nationality2']) : '' ?></span></div>
      <div class="info-item"><label>Position</label><span><span class="position-badge"><?= h($player['position']) ?></span></span></div>
      <?php if (!empty($player['ea_rating'])): ?>
      <div class="info-item"><label>EA Rating</label><span><span class="ea-rating"><?= (int)$player['ea_rating'] ?></span></span></div>
      <?php endif; ?>
      <?php if ($player['foot']): ?>
      <div class="info-item"><label>Foot</label><span><?= h($player['foot']) ?></span></div>
      <?php endif; ?>
      <?php if ($player['height']): ?>
      <div class="info-item"><label>Height</label><span><?= $player['height'] ?> cm</span></div>
      <?php endif; ?>
      <?php if ($current): ?>
      <div class="info-item"><label>Club</label><span><a href="team.php?id=<?= $current['team_id'] ?>"><?= h($current['team_name']) ?></a></span></div>
      <div class="info-item"><label>Joined</label><span><?= $current['joined'] ? date('d.m.Y', strtotime($current['joined'])) : '-' ?></span></div>
      <div class="info-item"><label>Contract Until</label><span><?= $current['contract_until'] ? date('d.m.Y', strtotime($current['contract_until'])) : '-' ?></span></div>
      <?php if ($current['loan']): ?>
      <div class="info-item"><label>Status</label><span class="loan-badge">On Loan</span></div>
      <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if ($player['info']): ?>
    <p style="margin-top:14px;color:#555;line-height:1.6"><?= nl2br(h($player['info'])) ?></p>
    <?php endif; ?>

    <div style="margin-top:16px">
      <a href="admin/edit_player.php?id=<?= $player_id ?>" class="btn btn-outline btn-sm">&#9998; Edit Player</a>
    </div>
  </div>
</div>

<!-- Career -->
<?php if (count($player_teams) > 1): ?>
<div class="section">
  <div class="section-title">Career</div>
  <div class="table-wrap card">
    <table class="data-table">
      <thead>
        <tr><th>Season</th><th>Club</th><th>League</th><th>#</th><th class="num">Market Value</th></tr>
      </thead>
      <tbody>
        <?php foreach ($player_teams as $pt): ?>
        <tr>
          <td><?= h($pt['season']) ?></td>
          <td><a href="team.php?id=<?= $pt['team_id'] ?>"><?= h($pt['team_name']) ?></a><?= $pt['loan'] ? ' <span class="loan-badge">LOAN</span>' : '' ?></td>
          <td><?= h($pt['league_name']) ?></td>
          <td class="center"><?= $pt['jersey_number'] ?: '-' ?></td>
          <td class="num value-cell"><?= format_value($pt['market_value']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Transfer history -->
<?php if ($transfers): ?>
<div class="section">
  <div class="section-title">Transfer History</div>
  <div class="table-wrap card">
    <table class="data-table">
      <thead>
        <tr><th>Date</th><th>Season</th><th>From</th><th>To</th><th class="num">Fee</th><th>Notes</th></tr>
      </thead>
      <tbody>
        <?php foreach ($transfers as $tr): ?>
        <tr>
          <td><?= $tr['transfer_date'] ? date('d.m.Y', strtotime($tr['transfer_date'])) : '-' ?></td>
          <td><?= h($tr['season']) ?></td>
          <td><?= $tr['from_name'] ? h($tr['from_name']) : '<em class="text-muted">—</em>' ?></td>
          <td><?= $tr['to_name'] ? h($tr['to_name']) : '<em class="text-muted">—</em>' ?></td>
          <td class="num <?= $tr['fee_type'] === 'free' ? 'transfer-fee-free' : ($tr['fee_type'] === 'loan' ? 'transfer-fee-loan' : 'transfer-fee-paid') ?>">
            <?= match($tr['fee_type']) { 'free' => 'Free', 'loan' => 'Loan', default => format_value($tr['fee']) } ?>
          </td>
          <td class="text-muted"><?= h($tr['notes']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

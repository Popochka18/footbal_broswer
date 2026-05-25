<?php
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Transfers';
require_once __DIR__ . '/includes/header.php';

$db = get_db();
$season_filter = $_GET['season'] ?? '';
$seasons = $db->query('SELECT DISTINCT season FROM transfers ORDER BY season DESC')->fetchAll(PDO::FETCH_COLUMN);

$where = '1=1';
$params = [];
if ($season_filter) { $where .= ' AND tr.season=?'; $params[] = $season_filter; }

$st = $db->prepare('
    SELECT tr.*, p.name AS player_name, p.position, p.nationality,
           tf.name AS from_name, tf.id AS from_id,
           tt.name AS to_name, tt.id AS to_id
    FROM transfers tr
    JOIN players p ON p.id=tr.player_id
    LEFT JOIN teams tf ON tf.id=tr.from_team_id
    LEFT JOIN teams tt ON tt.id=tr.to_team_id
    WHERE ' . $where . '
    ORDER BY tr.transfer_date DESC, tr.fee DESC
');
$st->execute($params);
$transfers = $st->fetchAll();

$total_fees = array_sum(array_filter(array_column($transfers, 'fee'), fn($f) => $f > 0));
?>

<h1 class="page-title">Transfer History</h1>

<form method="get" style="display:flex;gap:10px;margin-bottom:16px">
  <select name="season" style="border:1px solid #ccc;border-radius:4px;padding:8px 10px">
    <option value="">All seasons</option>
    <?php foreach ($seasons as $s): ?>
    <option value="<?= h($s) ?>" <?= $season_filter === $s ? 'selected' : '' ?>><?= h($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-primary">Filter</button>
  <?php if ($season_filter): ?><a href="transfers.php" class="btn btn-secondary">Clear</a><?php endif; ?>
</form>

<div class="stats-bar">
  <div class="stat-box">
    <div class="stat-val"><?= count($transfers) ?></div>
    <div class="stat-label">Transfers</div>
  </div>
  <div class="stat-box">
    <div class="stat-val"><?= format_value($total_fees) ?></div>
    <div class="stat-label">Total Fees</div>
  </div>
</div>

<div class="table-wrap card">
  <table class="data-table">
    <thead>
      <tr><th>Date</th><th>Player</th><th>Nat.</th><th>Pos</th><th>From</th><th>To</th><th>Season</th><th class="num">Fee</th><th>Notes</th></tr>
    </thead>
    <tbody>
      <?php foreach ($transfers as $tr): ?>
      <tr>
        <td><?= $tr['transfer_date'] ? date('d.m.Y', strtotime($tr['transfer_date'])) : '-' ?></td>
        <td><a href="player.php?id=<?= $tr['player_id'] ?>" style="font-weight:600"><?= h($tr['player_name']) ?></a></td>
        <td><?= h($tr['nationality']) ?></td>
        <td><span class="position-badge"><?= h($tr['position']) ?></span></td>
        <td><?= $tr['from_name'] ? '<a href="team.php?id=' . $tr['from_id'] . '">' . h($tr['from_name']) . '</a>' : '<em class="text-muted">—</em>' ?></td>
        <td><?= $tr['to_name'] ? '<a href="team.php?id=' . $tr['to_id'] . '">' . h($tr['to_name']) . '</a>' : '<em class="text-muted">—</em>' ?></td>
        <td><?= h($tr['season']) ?></td>
        <td class="num <?= $tr['fee_type'] === 'free' ? 'transfer-fee-free' : ($tr['fee_type'] === 'loan' ? 'transfer-fee-loan' : 'transfer-fee-paid') ?>">
          <?= match($tr['fee_type']) { 'free' => 'Free', 'loan' => 'Loan', default => format_value($tr['fee']) } ?>
        </td>
        <td class="text-muted"><?= h($tr['notes']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php if (empty($transfers)): ?><p class="text-muted mt-2">No transfers found.</p><?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

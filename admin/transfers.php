<?php
require_once __DIR__ . '/../includes/functions.php';
$root = '../';
$page_title = 'Manage Transfers';
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['delete'])) {
    csrf_check();
    get_db()->prepare('DELETE FROM transfers WHERE id=?')->execute([(int)$_GET['delete']]);
    redirect('transfers.php?msg=deleted');
}

$msg = $_GET['msg'] ?? '';
$db = get_db();
$st = $db->query('
    SELECT tr.*, p.name AS player_name, p.position,
           tf.name AS from_name, tt.name AS to_name
    FROM transfers tr
    JOIN players p ON p.id=tr.player_id
    LEFT JOIN teams tf ON tf.id=tr.from_team_id
    LEFT JOIN teams tt ON tt.id=tr.to_team_id
    ORDER BY tr.transfer_date DESC
');
$transfers = $st->fetchAll();
?>

<h1 class="page-title">Manage Transfers</h1>

<div class="admin-layout">
  <?php include 'sidebar.php'; ?>
  <div>
    <?php if ($msg==='saved'): ?><div class="alert alert-success" data-auto-dismiss>Transfer saved.</div><?php endif; ?>
    <?php if ($msg==='deleted'): ?><div class="alert alert-success" data-auto-dismiss>Transfer deleted.</div><?php endif; ?>

    <div style="margin-bottom:12px"><a href="edit_transfer.php" class="btn btn-primary">+ Add Transfer</a></div>

    <div class="table-wrap card">
      <table class="data-table">
        <thead><tr><th>Date</th><th>Player</th><th>Pos</th><th>From</th><th>To</th><th>Season</th><th class="num">Fee</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($transfers as $tr): ?>
        <tr>
          <td><?= $tr['transfer_date'] ? date('d.m.Y', strtotime($tr['transfer_date'])) : '-' ?></td>
          <td><a href="../player.php?id=<?= $tr['player_id'] ?>"><strong><?= h($tr['player_name']) ?></strong></a></td>
          <td><span class="position-badge"><?= h($tr['position']) ?></span></td>
          <td><?= h($tr['from_name'] ?? '—') ?></td>
          <td><?= h($tr['to_name'] ?? '—') ?></td>
          <td><?= h($tr['season']) ?></td>
          <td class="num <?= $tr['fee_type']==='free'?'transfer-fee-free':($tr['fee_type']==='loan'?'transfer-fee-loan':'transfer-fee-paid') ?>">
            <?= match($tr['fee_type']) {'free'=>'Free','loan'=>'Loan',default=>format_value($tr['fee'])} ?>
          </td>
          <td style="white-space:nowrap">
            <a href="edit_transfer.php?id=<?= $tr['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <a href="transfers.php?delete=<?= $tr['id'] ?>&csrf=<?= csrf_token() ?>" class="btn btn-danger btn-sm" data-confirm="Delete this transfer?">Del</a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

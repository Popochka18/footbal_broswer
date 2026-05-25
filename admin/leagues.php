<?php
$root = '../';
$page_title = 'Manage Leagues';
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['delete'])) {
    csrf_check();
    get_db()->prepare('DELETE FROM leagues WHERE id=?')->execute([(int)$_GET['delete']]);
    redirect('leagues.php?msg=deleted');
}

$msg = $_GET['msg'] ?? '';
$leagues = get_leagues();
?>

<h1 class="page-title">Manage Leagues</h1>

<div class="admin-layout">
  <?php include 'sidebar.php'; ?>
  <div>
    <?php if ($msg === 'saved'): ?><div class="alert alert-success" data-auto-dismiss>League saved.</div><?php endif; ?>
    <?php if ($msg === 'deleted'): ?><div class="alert alert-success" data-auto-dismiss>League deleted.</div><?php endif; ?>
    <div style="margin-bottom:12px"><a href="edit_league.php" class="btn btn-primary">+ Add League</a></div>
    <div class="table-wrap card">
      <table class="data-table">
        <thead><tr><th>#</th><th>Country</th><th>League</th><th>Tier</th><th>Season</th><th>Teams</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($leagues as $l):
          $tc = get_db()->prepare('SELECT COUNT(*) FROM teams WHERE league_id=?');
          $tc->execute([$l['id']]);
        ?>
        <tr>
          <td><?= $l['id'] ?></td>
          <td><?= h($l['flag']) ?> <?= h($l['country_name']) ?></td>
          <td><a href="../league.php?id=<?= $l['id'] ?>"><strong><?= h($l['name']) ?></strong></a></td>
          <td><?= $l['tier'] ?></td>
          <td><?= h($l['season']) ?></td>
          <td><?= $tc->fetchColumn() ?></td>
          <td style="white-space:nowrap">
            <a href="edit_league.php?id=<?= $l['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <a href="leagues.php?delete=<?= $l['id'] ?>&csrf=<?= csrf_token() ?>" class="btn btn-danger btn-sm" data-confirm="Delete this league and all its teams?">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

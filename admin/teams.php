<?php
$root = '../';
$page_title = 'Manage Teams';
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['delete'])) {
    csrf_check();
    get_db()->prepare('DELETE FROM teams WHERE id=?')->execute([(int)$_GET['delete']]);
    redirect('teams.php?msg=deleted');
}

$msg = $_GET['msg'] ?? '';
$league_filter = (int)($_GET['league'] ?? 0);
$teams = get_teams($league_filter);
$leagues = get_leagues();
?>

<h1 class="page-title">Manage Teams</h1>

<div class="admin-layout">
  <?php include 'sidebar.php'; ?>
  <div>
    <?php if ($msg === 'saved'): ?><div class="alert alert-success" data-auto-dismiss>Team saved.</div><?php endif; ?>
    <?php if ($msg === 'deleted'): ?><div class="alert alert-success" data-auto-dismiss>Team deleted.</div><?php endif; ?>

    <div style="display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap">
      <a href="edit_team.php" class="btn btn-primary">+ Add Team</a>
      <form method="get" style="display:flex;gap:8px">
        <select name="league" style="border:1px solid #ccc;border-radius:4px;padding:7px 10px">
          <option value="">All leagues</option>
          <?php foreach ($leagues as $l): ?>
          <option value="<?= $l['id'] ?>" <?= $league_filter==$l['id']?'selected':'' ?>><?= h($l['country_name']) ?> — <?= h($l['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
      </form>
    </div>

    <div class="table-wrap card">
      <table class="data-table">
        <thead><tr><th>#</th><th>Team</th><th>League</th><th>City</th><th>Founded</th><th>Squad</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($teams as $t):
          $sc = get_db()->prepare('SELECT COUNT(*) FROM player_squad WHERE team_id=?');
          $sc->execute([$t['id']]);
        ?>
        <tr>
          <td><?= $t['id'] ?></td>
          <td><a href="../team.php?id=<?= $t['id'] ?>"><strong><?= h($t['name']) ?></strong></a></td>
          <td><?= h($t['league_name']) ?></td>
          <td><?= h($t['city']) ?></td>
          <td><?= $t['founded'] ?: '-' ?></td>
          <td><?= $sc->fetchColumn() ?></td>
          <td style="white-space:nowrap">
            <a href="edit_squad.php?team_id=<?= $t['id'] ?>" class="btn btn-outline btn-sm">Squad</a>
            <a href="edit_team.php?id=<?= $t['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <a href="teams.php?delete=<?= $t['id'] ?>&csrf=<?= csrf_token() ?>" class="btn btn-danger btn-sm" data-confirm="Delete team <?= h($t['name']) ?>?">Del</a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

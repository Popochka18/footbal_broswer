<?php
$root = '../';
$page_title = 'Manage Players';
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['delete'])) {
    csrf_check();
    get_db()->prepare('DELETE FROM players WHERE id=?')->execute([(int)$_GET['delete']]);
    redirect('players.php?msg=deleted');
}

$msg = $_GET['msg'] ?? '';
$search = trim($_GET['q'] ?? '');
$db = get_db();

$where = '1=1'; $params = [];
if ($search) { $where .= ' AND (p.name LIKE ? OR p.nationality LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$st = $db->prepare('SELECT p.*, ps.market_value, t.name AS team_name, t.id AS team_id FROM players p LEFT JOIN player_squad ps ON ps.player_id=p.id LEFT JOIN teams t ON t.id=ps.team_id WHERE ' . $where . ' ORDER BY ps.market_value DESC, p.name LIMIT 300');
$st->execute($params);
$players = $st->fetchAll();
?>

<h1 class="page-title">Manage Players</h1>

<div class="admin-layout">
  <?php include 'sidebar.php'; ?>
  <div>
    <?php if ($msg==='saved'): ?><div class="alert alert-success" data-auto-dismiss>Player saved.</div><?php endif; ?>
    <?php if ($msg==='deleted'): ?><div class="alert alert-success" data-auto-dismiss>Player deleted.</div><?php endif; ?>

    <div style="display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap">
      <a href="edit_player.php" class="btn btn-primary">+ Add Player</a>
      <form method="get" style="display:flex;gap:8px;flex:1">
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search by name or nationality..." style="flex:1;min-width:200px;border:1px solid #ccc;border-radius:4px;padding:7px 12px">
        <button type="submit" class="btn btn-secondary">Search</button>
        <?php if ($search): ?><a href="players.php" class="btn btn-outline">Clear</a><?php endif; ?>
      </form>
    </div>

    <div class="table-wrap card">
      <table class="data-table">
        <thead><tr><th>#</th><th>Player</th><th>Pos</th><th>Nat.</th><th>Age</th><th>Club</th><th class="num">Value</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($players as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><a href="../player.php?id=<?= $p['id'] ?>"><strong><?= h($p['name']) ?></strong></a></td>
          <td><span class="position-badge"><?= h($p['position']) ?></span></td>
          <td><?= h($p['nationality']) ?></td>
          <td><?= $p['birth_date'] ? age($p['birth_date']) : '-' ?></td>
          <td><?= $p['team_name'] ? '<a href="../team.php?id=' . $p['team_id'] . '">' . h($p['team_name']) . '</a>' : '<em class="text-muted">Free</em>' ?></td>
          <td class="num value-cell"><?= format_value($p['market_value']) ?></td>
          <td style="white-space:nowrap">
            <a href="edit_player.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <a href="players.php?delete=<?= $p['id'] ?>&csrf=<?= csrf_token() ?>" class="btn btn-danger btn-sm" data-confirm="Delete <?= h($p['name']) ?>?">Del</a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

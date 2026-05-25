<?php
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Players';
require_once __DIR__ . '/includes/header.php';

$db = get_db();
$pos_filter = $_GET['pos'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = ['1=1'];
$params = [];
if ($pos_filter) { $where[] = 'p.position=?'; $params[] = $pos_filter; }
if ($search) { $where[] = '(p.name LIKE ? OR p.nationality LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$st = $db->prepare('
    SELECT p.*, ps.market_value, ps.jersey_number, ps.team_id, ps.loan,
           t.name AS team_name
    FROM players p
    LEFT JOIN player_squad ps ON ps.player_id=p.id
    LEFT JOIN teams t ON t.id=ps.team_id
    WHERE ' . implode(' AND ', $where) . '
    ORDER BY ps.market_value DESC, p.name
    LIMIT 200
');
$st->execute($params);
$players = $st->fetchAll();

$positions = $db->query('SELECT DISTINCT position FROM players ORDER BY position')->fetchAll(PDO::FETCH_COLUMN);
?>

<h1 class="page-title">Players</h1>

<form method="get" style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
  <input type="text" name="q" value="<?= h($search) ?>" placeholder="Search players..." style="flex:1;min-width:200px;border:1px solid #ccc;border-radius:4px;padding:8px 12px">
  <select name="pos" style="border:1px solid #ccc;border-radius:4px;padding:8px 10px">
    <option value="">All positions</option>
    <?php foreach ($positions as $pos): ?>
    <option value="<?= h($pos) ?>" <?= $pos_filter === $pos ? 'selected' : '' ?>><?= h($pos) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-primary">Filter</button>
  <?php if ($pos_filter || $search): ?><a href="players.php" class="btn btn-secondary">Clear</a><?php endif; ?>
</form>

<div class="table-wrap card">
  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Player</th>
        <th>Nationality</th>
        <th>Pos</th>
        <th>Age</th>
        <th>Club</th>
        <th class="num">Market Value</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($players as $i => $p): ?>
      <tr>
        <td class="center text-muted"><?= $i+1 ?></td>
        <td><a href="player.php?id=<?= $p['id'] ?>" style="font-weight:600"><?= h($p['name']) ?></a></td>
        <td><?= h($p['nationality']) ?></td>
        <td><span class="position-badge"><?= h($p['position']) ?></span></td>
        <td><?= $p['birth_date'] ? age($p['birth_date']) : '-' ?></td>
        <td><?= $p['team_name'] ? '<a href="team.php?id=' . $p['team_id'] . '">' . h($p['team_name']) . '</a>' : '<em class="text-muted">Free agent</em>' ?></td>
        <td class="num value-cell"><?= format_value($p['market_value']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php if (empty($players)): ?><p class="text-muted mt-2">No players found.</p><?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
$root = '../';
$page_title = 'Edit Team';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$team = null;
if ($id) { $st = $db->prepare('SELECT * FROM teams WHERE id=?'); $st->execute([$id]); $team = $st->fetch(); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'league_id'  => (int)$_POST['league_id'],
        'name'       => trim($_POST['name'] ?? ''),
        'short_name' => trim($_POST['short_name'] ?? ''),
        'city'       => trim($_POST['city'] ?? ''),
        'stadium'    => trim($_POST['stadium'] ?? ''),
        'founded'    => (int)($_POST['founded'] ?? 0) ?: null,
        'colors'     => trim($_POST['colors'] ?? ''),
        'info'       => trim($_POST['info'] ?? ''),
    ];
    if (!$data['name']) $errors[] = 'Team name is required.';
    if (!$data['league_id']) $errors[] = 'League is required.';
    if (!$errors) {
        if ($id) {
            $db->prepare('UPDATE teams SET league_id=?,name=?,short_name=?,city=?,stadium=?,founded=?,colors=?,info=? WHERE id=?')
               ->execute([...$data, $id]);
        } else {
            $db->prepare('INSERT INTO teams(league_id,name,short_name,city,stadium,founded,colors,info) VALUES(?,?,?,?,?,?,?,?)')
               ->execute(array_values($data));
            $id = (int)$db->lastInsertId();
        }
        redirect('teams.php?msg=saved');
    }
}

$v = $team ?: ['league_id'=>'','name'=>'','short_name'=>'','city'=>'','stadium'=>'','founded'=>'','colors'=>'','info'=>''];
$leagues = get_leagues();
?>

<h1 class="page-title"><?= $id ? 'Edit Team: ' . h($v['name']) : 'Add Team' ?></h1>

<div class="admin-layout">
  <?php include 'sidebar.php'; ?>
  <div>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-grid">
          <div class="form-group">
            <label>League</label>
            <select name="league_id" required>
              <option value="">— Select —</option>
              <?php foreach ($leagues as $l): ?>
              <option value="<?= $l['id'] ?>" <?= $v['league_id']==$l['id']?'selected':'' ?>><?= h($l['country_name']) ?> — <?= h($l['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Team Name</label><input name="name" value="<?= h($v['name']) ?>" required></div>
          <div class="form-group"><label>Short Name</label><input name="short_name" value="<?= h($v['short_name']) ?>" placeholder="e.g. Man City"></div>
          <div class="form-group"><label>City</label><input name="city" value="<?= h($v['city']) ?>"></div>
          <div class="form-group"><label>Stadium</label><input name="stadium" value="<?= h($v['stadium']) ?>"></div>
          <div class="form-group"><label>Founded (Year)</label><input type="number" name="founded" value="<?= h($v['founded']) ?>" min="1800" max="2025"></div>
          <div class="form-group"><label>Kit Colors</label><input name="colors" value="<?= h($v['colors']) ?>" placeholder="e.g. Blue, White"></div>
          <div class="form-group full"><label>Club Info / Description</label><textarea name="info" rows="4"><?= h($v['info']) ?></textarea></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Team</button>
          <a href="teams.php" class="btn btn-secondary">Cancel</a>
          <?php if ($id): ?>
          <a href="../team.php?id=<?= $id ?>" class="btn btn-outline">View Squad</a>
          <a href="edit_squad.php?team_id=<?= $id ?>" class="btn btn-outline">Manage Squad</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

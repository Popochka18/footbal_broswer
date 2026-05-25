<?php
$root = '../';
$page_title = 'Edit League';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$league = null;
if ($id) { $st = $db->prepare('SELECT * FROM leagues WHERE id=?'); $st->execute([$id]); $league = $st->fetch(); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'country_id' => (int)$_POST['country_id'],
        'name'       => trim($_POST['name'] ?? ''),
        'short_name' => trim($_POST['short_name'] ?? ''),
        'tier'       => (int)($_POST['tier'] ?? 1),
        'season'     => trim($_POST['season'] ?? ''),
    ];
    if (!$data['name']) $errors[] = 'Name is required.';
    if (!$data['country_id']) $errors[] = 'Country is required.';
    if (!$errors) {
        if ($id) {
            $db->prepare('UPDATE leagues SET country_id=?,name=?,short_name=?,tier=?,season=? WHERE id=?')
               ->execute([...$data, $id]);
        } else {
            $db->prepare('INSERT INTO leagues(country_id,name,short_name,tier,season) VALUES(?,?,?,?,?)')
               ->execute(array_values($data));
        }
        redirect('leagues.php?msg=saved');
    }
}
$v = $league ?: ['country_id'=>'','name'=>'','short_name'=>'','tier'=>1,'season'=>'2025/26'];
?>

<h1 class="page-title"><?= $id ? 'Edit League' : 'Add League' ?></h1>

<div class="admin-layout">
  <?php include 'sidebar.php'; ?>
  <div>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-grid">
          <div class="form-group">
            <label>Country</label>
            <select name="country_id" required>
              <option value="">— Select —</option>
              <?php foreach ($countries as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $v['country_id']==$c['id']?'selected':'' ?>><?= h($c['flag']) ?> <?= h($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>League Name</label><input name="name" value="<?= h($v['name']) ?>" required placeholder="e.g. Premier League"></div>
          <div class="form-group"><label>Short Name</label><input name="short_name" value="<?= h($v['short_name']) ?>" placeholder="e.g. EPL"></div>
          <div class="form-group">
            <label>Division / Tier</label>
            <select name="tier">
              <?php for ($i=1;$i<=5;$i++): ?>
              <option value="<?= $i ?>" <?= $v['tier']==$i?'selected':'' ?>>Div <?= $i ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group"><label>Season</label><input name="season" value="<?= h($v['season']) ?>" placeholder="2025/26"></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save League</button>
          <a href="leagues.php" class="btn btn-secondary">Cancel</a>
          <?php if ($id): ?><a href="../league.php?id=<?= $id ?>" class="btn btn-outline">View</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

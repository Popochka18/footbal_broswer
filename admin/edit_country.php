<?php
require_once __DIR__ . '/../includes/functions.php';
$root = '../';
$page_title = 'Edit Country';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$country = $id ? $db->prepare('SELECT * FROM countries WHERE id=?') : null;
if ($country) { $country->execute([$id]); $country = $country->fetch(); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $flag = trim($_POST['flag'] ?? '');
    if (!$name) $errors[] = 'Name is required.';
    if (!$errors) {
        if ($id) {
            $db->prepare('UPDATE countries SET name=?, flag=? WHERE id=?')->execute([$name, $flag, $id]);
        } else {
            $db->prepare('INSERT INTO countries(name,flag) VALUES(?,?)')->execute([$name, $flag]);
        }
        redirect('countries.php?msg=saved');
    }
}
$v = $country ?: ['name'=>'','flag'=>''];
?>

<h1 class="page-title"><?= $id ? 'Edit Country' : 'Add Country' ?></h1>

<div class="admin-layout">
  <?php include 'sidebar.php'; ?>
  <div>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-grid">
          <div class="form-group"><label>Country Name</label><input name="name" value="<?= h($v['name']) ?>" required placeholder="e.g. England"></div>
          <div class="form-group"><label>Flag Emoji</label><input name="flag" value="<?= h($v['flag']) ?>" placeholder="🏴󠁧󠁢󠁥󠁮󠁧󠁿"></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Country</button>
          <a href="countries.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
$root = '../';
$page_title = 'Manage Countries';
require_once __DIR__ . '/../includes/header.php';

if (isset($_GET['delete'])) {
    csrf_check();
    $db = get_db();
    $db->prepare('DELETE FROM countries WHERE id=?')->execute([(int)$_GET['delete']]);
    redirect('countries.php?msg=deleted');
}

$msg = $_GET['msg'] ?? '';
?>

<h1 class="page-title">Manage Countries</h1>

<div class="admin-layout">
  <?php include 'sidebar.php'; ?>
  <div>
    <?php if ($msg === 'saved'): ?><div class="alert alert-success" data-auto-dismiss>Country saved.</div><?php endif; ?>
    <?php if ($msg === 'deleted'): ?><div class="alert alert-success" data-auto-dismiss>Country deleted.</div><?php endif; ?>
    <div style="margin-bottom:12px"><a href="edit_country.php" class="btn btn-primary">+ Add Country</a></div>
    <div class="table-wrap card">
      <table class="data-table">
        <thead><tr><th>#</th><th>Flag</th><th>Name</th><th>Leagues</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($countries as $c):
          $lcount = get_db()->prepare('SELECT COUNT(*) FROM leagues WHERE country_id=?');
          $lcount->execute([$c['id']]);
        ?>
        <tr>
          <td><?= $c['id'] ?></td>
          <td><span style="font-size:20px"><?= h($c['flag']) ?></span></td>
          <td><strong><?= h($c['name']) ?></strong></td>
          <td><?= $lcount->fetchColumn() ?></td>
          <td style="white-space:nowrap">
            <a href="edit_country.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <a href="countries.php?delete=<?= $c['id'] ?>&csrf=<?= csrf_token() ?>" class="btn btn-danger btn-sm" data-confirm="Delete this country? This will also delete all leagues and teams in it.">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

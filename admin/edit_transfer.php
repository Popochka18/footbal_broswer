<?php
$root = '../';
$page_title = 'Edit Transfer';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$transfer = null;
if ($id) { $st = $db->prepare('SELECT * FROM transfers WHERE id=?'); $st->execute([$id]); $transfer = $st->fetch(); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'player_id'    => (int)$_POST['player_id'],
        'from_team_id' => (int)($_POST['from_team_id'] ?? 0) ?: null,
        'to_team_id'   => (int)($_POST['to_team_id'] ?? 0) ?: null,
        'transfer_date'=> trim($_POST['transfer_date'] ?? ''),
        'fee'          => format_value_raw(trim($_POST['fee_raw'] ?? '')),
        'fee_type'     => trim($_POST['fee_type'] ?? 'paid'),
        'season'       => trim($_POST['season'] ?? ''),
        'notes'        => trim($_POST['notes'] ?? ''),
    ];
    if (!$data['player_id']) $errors[] = 'Player is required.';
    if (!$errors) {
        if ($id) {
            $db->prepare('UPDATE transfers SET player_id=?,from_team_id=?,to_team_id=?,transfer_date=?,fee=?,fee_type=?,season=?,notes=? WHERE id=?')
               ->execute([...$data, $id]);
        } else {
            $db->prepare('INSERT INTO transfers(player_id,from_team_id,to_team_id,transfer_date,fee,fee_type,season,notes) VALUES(?,?,?,?,?,?,?,?)')
               ->execute(array_values($data));
        }
        redirect('transfers.php?msg=saved');
    }
}

$v = $transfer ?: ['player_id'=>'','from_team_id'=>'','to_team_id'=>'','transfer_date'=>'','fee'=>0,'fee_type'=>'paid','season'=>'','notes'=>''];
$players = $db->query('SELECT id,name,position FROM players ORDER BY name')->fetchAll();
$teams   = get_teams();
?>

<h1 class="page-title"><?= $id ? 'Edit Transfer' : 'Add Transfer' ?></h1>

<div class="admin-layout">
  <?php include 'sidebar.php'; ?>
  <div>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <div class="form-card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="form-grid">
          <div class="form-group">
            <label>Player *</label>
            <select name="player_id" required>
              <option value="">— Select —</option>
              <?php foreach ($players as $p): ?>
              <option value="<?= $p['id'] ?>" <?= $v['player_id']==$p['id']?'selected':'' ?>><?= h($p['name']) ?> (<?= h($p['position']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Transfer Date</label><input type="date" name="transfer_date" value="<?= h($v['transfer_date']) ?>"></div>
          <div class="form-group">
            <label>From Club</label>
            <select name="from_team_id">
              <option value="">— Unknown / Youth —</option>
              <?php foreach ($teams as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $v['from_team_id']==$t['id']?'selected':'' ?>><?= h($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>To Club</label>
            <select name="to_team_id">
              <option value="">— Unknown —</option>
              <?php foreach ($teams as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $v['to_team_id']==$t['id']?'selected':'' ?>><?= h($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Fee Type</label>
            <select name="fee_type">
              <option value="paid" <?= $v['fee_type']==='paid'?'selected':'' ?>>Paid</option>
              <option value="free" <?= $v['fee_type']==='free'?'selected':'' ?>>Free Transfer</option>
              <option value="loan" <?= $v['fee_type']==='loan'?'selected':'' ?>>Loan</option>
            </select>
          </div>
          <div class="form-group"><label>Fee Amount <small style="text-transform:none;font-weight:400">(e.g. 50M, 500K)</small></label><input name="fee_raw" value="<?= $v['fee'] ? format_value($v['fee']) : '' ?>" placeholder="e.g. 50M"></div>
          <div class="form-group"><label>Season</label><input name="season" value="<?= h($v['season']) ?>" placeholder="e.g. 2023/24"></div>
          <div class="form-group full"><label>Notes</label><textarea name="notes"><?= h($v['notes']) ?></textarea></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Transfer</button>
          <a href="transfers.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

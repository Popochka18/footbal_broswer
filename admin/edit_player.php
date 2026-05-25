<?php
$root = '../';
$page_title = 'Edit Player';
require_once __DIR__ . '/../includes/header.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$player = null;
if ($id) { $st = $db->prepare('SELECT * FROM players WHERE id=?'); $st->execute([$id]); $player = $st->fetch(); }

// Squad entry
$squad = null;
if ($id) {
    $sq = $db->prepare('SELECT * FROM player_squad WHERE player_id=? ORDER BY season DESC LIMIT 1');
    $sq->execute([$id]);
    $squad = $sq->fetch();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdata = [
        'name'         => trim($_POST['name'] ?? ''),
        'full_name'    => trim($_POST['full_name'] ?? ''),
        'nationality'  => trim($_POST['nationality'] ?? ''),
        'nationality2' => trim($_POST['nationality2'] ?? ''),
        'birth_date'   => trim($_POST['birth_date'] ?? ''),
        'birth_place'  => trim($_POST['birth_place'] ?? ''),
        'position'     => trim($_POST['position'] ?? ''),
        'foot'         => trim($_POST['foot'] ?? ''),
        'height'       => (int)($_POST['height'] ?? 0) ?: null,
        'weight'       => (int)($_POST['weight'] ?? 0) ?: null,
        'image'        => trim($_POST['image'] ?? ''),
        'info'         => trim($_POST['info'] ?? ''),
    ];
    if (!$pdata['name']) $errors[] = 'Player name is required.';

    // Squad data
    $team_id    = (int)($_POST['team_id'] ?? 0);
    $jersey     = (int)($_POST['jersey_number'] ?? 0) ?: null;
    $raw_value  = trim($_POST['market_value_raw'] ?? '');
    $mv         = $raw_value !== '' ? format_value_raw($raw_value) : 0;
    $joined     = trim($_POST['joined'] ?? '');
    $contract   = trim($_POST['contract_until'] ?? '');
    $loan       = isset($_POST['loan']) ? 1 : 0;
    $season     = trim($_POST['season'] ?? '2025/26');

    if (!$errors) {
        if ($id) {
            $db->prepare('UPDATE players SET name=?,full_name=?,nationality=?,nationality2=?,birth_date=?,birth_place=?,position=?,foot=?,height=?,weight=?,image=?,info=? WHERE id=?')
               ->execute([...$pdata, $id]);
        } else {
            $db->prepare('INSERT INTO players(name,full_name,nationality,nationality2,birth_date,birth_place,position,foot,height,weight,image,info) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')
               ->execute(array_values($pdata));
            $id = (int)$db->lastInsertId();
        }
        // Update squad
        if ($team_id) {
            $exists = $db->prepare('SELECT id FROM player_squad WHERE player_id=? AND team_id=? AND season=?');
            $exists->execute([$id, $team_id, $season]);
            if ($exists->fetch()) {
                $db->prepare('UPDATE player_squad SET jersey_number=?,market_value=?,joined=?,contract_until=?,loan=? WHERE player_id=? AND team_id=? AND season=?')
                   ->execute([$jersey, $mv, $joined, $contract, $loan, $id, $team_id, $season]);
            } else {
                $db->prepare('INSERT INTO player_squad(player_id,team_id,season,jersey_number,market_value,joined,contract_until,loan) VALUES(?,?,?,?,?,?,?,?)')
                   ->execute([$id, $team_id, $season, $jersey, $mv, $joined, $contract, $loan]);
            }
        }
        redirect('players.php?msg=saved');
    }
}

$v  = $player ?: ['name'=>'','full_name'=>'','nationality'=>'','nationality2'=>'','birth_date'=>'','birth_place'=>'','position'=>'','foot'=>'','height'=>'','weight'=>'','image'=>'','info'=>''];
$sv = $squad  ?: ['team_id'=>'','season'=>'2025/26','jersey_number'=>'','market_value'=>0,'joined'=>'','contract_until'=>'','loan'=>0];
$teams = get_teams();

$positions = ['GK','CB','LB','RB','LWB','RWB','CDM','CM','CAM','LM','RM','LW','RW','CF','ST'];
?>

<h1 class="page-title"><?= $id ? 'Edit Player: ' . h($v['name']) : 'Add Player' ?></h1>

<div class="admin-layout">
  <?php include 'sidebar.php'; ?>
  <div>
    <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
    <div class="form-card" style="max-width:900px">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <div class="section-title" style="margin-bottom:12px">Player Info</div>
        <div class="form-grid">
          <div class="form-group"><label>Name *</label><input name="name" value="<?= h($v['name']) ?>" required></div>
          <div class="form-group"><label>Full Name</label><input name="full_name" value="<?= h($v['full_name']) ?>"></div>
          <div class="form-group"><label>Nationality</label><input name="nationality" value="<?= h($v['nationality']) ?>" placeholder="e.g. Brazil"></div>
          <div class="form-group"><label>2nd Nationality</label><input name="nationality2" value="<?= h($v['nationality2']) ?>"></div>
          <div class="form-group"><label>Date of Birth</label><input type="date" name="birth_date" value="<?= h($v['birth_date']) ?>"></div>
          <div class="form-group"><label>Place of Birth</label><input name="birth_place" value="<?= h($v['birth_place']) ?>"></div>
          <div class="form-group">
            <label>Position</label>
            <select name="position">
              <option value="">— Select —</option>
              <?php foreach ($positions as $pos): ?>
              <option value="<?= $pos ?>" <?= $v['position']===$pos?'selected':'' ?>><?= $pos ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Preferred Foot</label>
            <select name="foot">
              <option value="">— Select —</option>
              <option value="R" <?= $v['foot']==='R'?'selected':'' ?>>Right</option>
              <option value="L" <?= $v['foot']==='L'?'selected':'' ?>>Left</option>
              <option value="Both" <?= $v['foot']==='Both'?'selected':'' ?>>Both</option>
            </select>
          </div>
          <div class="form-group"><label>Height (cm)</label><input type="number" name="height" value="<?= h($v['height']) ?>" min="150" max="220"></div>
          <div class="form-group"><label>Weight (kg)</label><input type="number" name="weight" value="<?= h($v['weight']) ?>" min="50" max="120"></div>
          <div class="form-group full"><label>Photo URL</label><input name="image" value="<?= h($v['image']) ?>" placeholder="https://..."></div>
          <div class="form-group full"><label>Bio / Notes</label><textarea name="info" rows="3"><?= h($v['info']) ?></textarea></div>
        </div>

        <div class="section-title" style="margin:20px 0 12px">Current Club & Contract</div>
        <div class="form-grid">
          <div class="form-group">
            <label>Team</label>
            <select name="team_id">
              <option value="">— Free Agent —</option>
              <?php foreach ($teams as $t): ?>
              <option value="<?= $t['id'] ?>" <?= $sv['team_id']==$t['id']?'selected':'' ?>><?= h($t['league_name']) ?> — <?= h($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Season</label><input name="season" value="<?= h($sv['season']) ?>" placeholder="2025/26"></div>
          <div class="form-group"><label>Jersey Number</label><input type="number" name="jersey_number" value="<?= h($sv['jersey_number']) ?>" min="1" max="99"></div>
          <div class="form-group">
            <label>Market Value <small style="text-transform:none;font-weight:400">(e.g. 50M, 500K, 1500000)</small></label>
            <input name="market_value_raw" value="<?= $sv['market_value'] ? format_value($sv['market_value']) : '' ?>" placeholder="e.g. 50M">
          </div>
          <div class="form-group"><label>Joined Club</label><input type="date" name="joined" value="<?= h($sv['joined']) ?>"></div>
          <div class="form-group"><label>Contract Until</label><input type="date" name="contract_until" value="<?= h($sv['contract_until']) ?>"></div>
          <div class="form-group" style="justify-content:flex-end">
            <label>&nbsp;</label>
            <label style="display:flex;align-items:center;gap:6px;font-size:14px;text-transform:none;cursor:pointer">
              <input type="checkbox" name="loan" <?= $sv['loan']?'checked':'' ?>> On loan
            </label>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Player</button>
          <a href="players.php" class="btn btn-secondary">Cancel</a>
          <?php if ($id): ?><a href="../player.php?id=<?= $id ?>" class="btn btn-outline">View Profile</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

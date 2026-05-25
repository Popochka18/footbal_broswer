<?php
$cur = basename($_SERVER['PHP_SELF']);
?>
<div class="admin-sidebar">
  <div class="sidebar-section">
    <h3>Editor</h3>
    <a href="index.php" class="<?= $cur==='index.php'?'active':'' ?>">Dashboard</a>
  </div>
  <div class="sidebar-section">
    <h3>Countries &amp; Leagues</h3>
    <a href="countries.php" class="<?= $cur==='countries.php'?'active':'' ?>">Countries</a>
    <a href="leagues.php" class="<?= $cur==='leagues.php'?'active':'' ?>">Leagues</a>
  </div>
  <div class="sidebar-section">
    <h3>Teams &amp; Players</h3>
    <a href="teams.php" class="<?= $cur==='teams.php'?'active':'' ?>">Teams</a>
    <a href="players.php" class="<?= $cur==='players.php'?'active':'' ?>">Players</a>
  </div>
  <div class="sidebar-section">
    <h3>Transfers</h3>
    <a href="transfers.php" class="<?= $cur==='transfers.php'?'active':'' ?>">All Transfers</a>
    <a href="edit_transfer.php" class="<?= $cur==='edit_transfer.php'?'active':'' ?>">Add Transfer</a>
  </div>
  <div class="sidebar-section">
    <h3>Site</h3>
    <a href="../index.php">&#8592; Public Site</a>
  </div>
</div>

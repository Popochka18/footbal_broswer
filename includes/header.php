<?php
session_start();
require_once __DIR__ . '/functions.php';
$countries = get_countries();
$current_page = basename($_SERVER['PHP_SELF']);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($page_title ?? 'FootballDB') ?> &mdash; FootballDB</title>
<link rel="stylesheet" href="<?= $root ?? '' ?>public/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container">
    <a class="logo" href="<?= $root ?? '' ?>index.php">
      <span class="logo-icon">&#9917;</span> FootballDB
    </a>
    <nav class="main-nav">
      <div class="nav-dropdown">
        <button class="nav-btn">Countries &#9660;</button>
        <div class="dropdown-menu">
          <?php foreach ($countries as $c): ?>
          <a href="<?= $root ?? '' ?>index.php?country=<?= $c['id'] ?>">
            <span class="flag"><?= h($c['flag']) ?></span> <?= h($c['name']) ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <a class="nav-link" href="<?= $root ?? '' ?>players.php">Players</a>
      <a class="nav-link" href="<?= $root ?? '' ?>transfers.php">Transfers</a>
      <a class="nav-link admin-link" href="<?= $root ?? '' ?>admin/index.php">&#9998; Editor</a>
    </nav>
  </div>
</header>
<main class="container">

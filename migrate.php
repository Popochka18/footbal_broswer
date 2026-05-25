<?php
// php migrate.php  —  idempotent schema migrations for existing DBs
require_once __DIR__ . '/includes/functions.php';

$db = get_db();
$cols = $db->query("PRAGMA table_info(players)")->fetchAll(PDO::FETCH_COLUMN, 1);

if (!in_array('ea_rating', $cols, true)) {
    $db->exec('ALTER TABLE players ADD COLUMN ea_rating INTEGER DEFAULT NULL');
    echo "Added players.ea_rating\n";
} else {
    echo "players.ea_rating already exists\n";
}

echo "Migration complete.\n";

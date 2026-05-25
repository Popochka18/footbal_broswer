<?php
define('DB_PATH', __DIR__ . '/../db/football.db');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        if (!extension_loaded('pdo_sqlite')) {
            http_response_code(500);
            die('<h2>Missing PHP extension: pdo_sqlite</h2>'
              . '<p>Install it and restart your web server:</p>'
              . '<pre>sudo apt install php-sqlite3      # Debian/Ubuntu' . "\n"
              . 'sudo dnf install php-pdo php-sqlite3   # Fedora/RHEL' . "\n"
              . 'sudo pacman -S php-sqlite              # Arch</pre>');
        }
        if (!file_exists(DB_PATH)) {
            http_response_code(500);
            die('<h2>Database not found</h2><p>Run <code>php setup.php</code> from the project root to create and seed the database.</p>');
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}

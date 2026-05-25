<?php
require_once __DIR__ . '/db.php';

function format_value(int|null $value): string {
    if ($value === null || $value === 0) return '-';
    if ($value >= 1_000_000) return '€' . number_format($value / 1_000_000, 1) . 'M';
    if ($value >= 1_000) return '€' . number_format($value / 1_000, 0) . 'K';
    return '€' . $value;
}

function format_value_raw(string $input): int {
    $input = trim(str_replace(['€', ' '], '', $input));
    if (str_ends_with($input, 'M')) return (int)(floatval($input) * 1_000_000);
    if (str_ends_with($input, 'K')) return (int)(floatval($input) * 1_000);
    return (int)$input;
}

function age(string $birth_date): string {
    if (!$birth_date) return '-';
    return (new DateTime())->diff(new DateTime($birth_date))->y . '';
}

function h(mixed $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function get_countries(): array {
    return get_db()->query('SELECT * FROM countries ORDER BY name')->fetchAll();
}

function get_leagues(int $country_id = 0): array {
    $db = get_db();
    if ($country_id) {
        $st = $db->prepare('SELECT l.*, c.name AS country_name, c.flag FROM leagues l JOIN countries c ON c.id=l.country_id WHERE l.country_id=? ORDER BY l.tier, l.name');
        $st->execute([$country_id]);
    } else {
        $st = $db->query('SELECT l.*, c.name AS country_name, c.flag FROM leagues l JOIN countries c ON c.id=l.country_id ORDER BY c.name, l.tier, l.name');
    }
    return $st->fetchAll();
}

function get_league(int $id): array|false {
    $st = get_db()->prepare('SELECT l.*, c.name AS country_name, c.flag FROM leagues l JOIN countries c ON c.id=l.country_id WHERE l.id=?');
    $st->execute([$id]);
    return $st->fetch();
}

function get_teams(int $league_id = 0): array {
    $db = get_db();
    if ($league_id) {
        $st = $db->prepare('SELECT t.*, l.name AS league_name FROM teams t JOIN leagues l ON l.id=t.league_id WHERE t.league_id=? ORDER BY t.name');
        $st->execute([$league_id]);
    } else {
        $st = $db->query('SELECT t.*, l.name AS league_name FROM teams t JOIN leagues l ON l.id=t.league_id ORDER BY l.name, t.name');
    }
    return $st->fetchAll();
}

function get_team(int $id): array|false {
    $st = get_db()->prepare('SELECT t.*, l.name AS league_name, l.id AS league_id, c.name AS country_name FROM teams t JOIN leagues l ON l.id=t.league_id JOIN countries c ON c.id=l.country_id WHERE t.id=?');
    $st->execute([$id]);
    return $st->fetch();
}

function get_squad(int $team_id): array {
    $st = get_db()->prepare('
        SELECT p.*, ps.jersey_number, ps.market_value, ps.joined, ps.contract_until, ps.loan
        FROM players p
        JOIN player_squad ps ON ps.player_id=p.id AND ps.team_id=?
        ORDER BY CASE p.position WHEN "GK" THEN 1 WHEN "CB" THEN 2 WHEN "LB" THEN 3 WHEN "RB" THEN 4 WHEN "LWB" THEN 5 WHEN "RWB" THEN 6 WHEN "CDM" THEN 7 WHEN "CM" THEN 8 WHEN "CAM" THEN 9 WHEN "LM" THEN 10 WHEN "RM" THEN 11 WHEN "LW" THEN 12 WHEN "RW" THEN 13 WHEN "CF" THEN 14 WHEN "ST" THEN 15 ELSE 16 END, p.name
    ');
    $st->execute([$team_id]);
    return $st->fetchAll();
}

function get_player(int $id): array|false {
    $st = get_db()->prepare('SELECT * FROM players WHERE id=?');
    $st->execute([$id]);
    return $st->fetch();
}

function get_player_teams(int $player_id): array {
    $st = get_db()->prepare('
        SELECT ps.*, t.name AS team_name, t.id AS team_id, l.name AS league_name
        FROM player_squad ps
        JOIN teams t ON t.id=ps.team_id
        JOIN leagues l ON l.id=t.league_id
        WHERE ps.player_id=?
        ORDER BY ps.season DESC
    ');
    $st->execute([$player_id]);
    return $st->fetchAll();
}

function get_transfers(int $player_id): array {
    $st = get_db()->prepare('
        SELECT tr.*,
               tf.name AS from_name, tt.name AS to_name
        FROM transfers tr
        LEFT JOIN teams tf ON tf.id=tr.from_team_id
        LEFT JOIN teams tt ON tt.id=tr.to_team_id
        WHERE tr.player_id=?
        ORDER BY tr.transfer_date DESC
    ');
    $st->execute([$player_id]);
    return $st->fetchAll();
}

function get_team_total_value(int $team_id): int {
    $st = get_db()->prepare('SELECT COALESCE(SUM(market_value),0) FROM player_squad WHERE team_id=?');
    $st->execute([$team_id]);
    return (int)$st->fetchColumn();
}

function position_group(string $pos): string {
    return match(true) {
        $pos === 'GK' => 'Goalkeepers',
        in_array($pos, ['CB','LB','RB','LWB','RWB']) => 'Defenders',
        in_array($pos, ['CDM','CM','CAM','LM','RM']) => 'Midfielders',
        in_array($pos, ['LW','RW','CF','ST']) => 'Forwards',
        default => 'Other',
    };
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(403);
        die('CSRF check failed');
    }
}

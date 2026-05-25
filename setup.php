<?php
// Run once: php setup.php
define('DB_PATH', __DIR__ . '/db/football.db');

if (!extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "ERROR: PHP extension 'pdo_sqlite' is not installed.\n\n");
    fwrite(STDERR, "Install it with one of:\n");
    fwrite(STDERR, "  Debian/Ubuntu:  sudo apt install php-sqlite3\n");
    fwrite(STDERR, "  Fedora/RHEL:    sudo dnf install php-pdo php-sqlite3\n");
    fwrite(STDERR, "  Arch:           sudo pacman -S php-sqlite\n\n");
    fwrite(STDERR, "Then verify with:  php -m | grep -i sqlite\n");
    exit(1);
}

if (!is_dir(__DIR__ . '/db')) mkdir(__DIR__ . '/db', 0775, true);

$pdo = new PDO('sqlite:' . DB_PATH);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec('
CREATE TABLE IF NOT EXISTS countries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  flag TEXT DEFAULT ""
);

CREATE TABLE IF NOT EXISTS leagues (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  country_id INTEGER NOT NULL REFERENCES countries(id) ON DELETE CASCADE,
  name TEXT NOT NULL,
  short_name TEXT DEFAULT "",
  tier INTEGER DEFAULT 1,
  logo TEXT DEFAULT "",
  season TEXT DEFAULT "2025/26"
);

CREATE TABLE IF NOT EXISTS teams (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  league_id INTEGER NOT NULL REFERENCES leagues(id) ON DELETE CASCADE,
  name TEXT NOT NULL,
  short_name TEXT DEFAULT "",
  city TEXT DEFAULT "",
  stadium TEXT DEFAULT "",
  founded INTEGER DEFAULT NULL,
  logo TEXT DEFAULT "",
  colors TEXT DEFAULT "",
  info TEXT DEFAULT ""
);

CREATE TABLE IF NOT EXISTS players (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  full_name TEXT DEFAULT "",
  nationality TEXT DEFAULT "",
  nationality2 TEXT DEFAULT "",
  birth_date TEXT DEFAULT "",
  birth_place TEXT DEFAULT "",
  position TEXT DEFAULT "",
  foot TEXT DEFAULT "",
  height INTEGER DEFAULT NULL,
  weight INTEGER DEFAULT NULL,
  image TEXT DEFAULT "",
  info TEXT DEFAULT ""
);

CREATE TABLE IF NOT EXISTS player_squad (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  player_id INTEGER NOT NULL REFERENCES players(id) ON DELETE CASCADE,
  team_id INTEGER NOT NULL REFERENCES teams(id) ON DELETE CASCADE,
  season TEXT DEFAULT "2025/26",
  jersey_number INTEGER DEFAULT NULL,
  market_value INTEGER DEFAULT 0,
  joined TEXT DEFAULT "",
  contract_until TEXT DEFAULT "",
  loan INTEGER DEFAULT 0,
  UNIQUE(player_id, team_id, season)
);

CREATE TABLE IF NOT EXISTS transfers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  player_id INTEGER NOT NULL REFERENCES players(id) ON DELETE CASCADE,
  from_team_id INTEGER DEFAULT NULL REFERENCES teams(id) ON DELETE SET NULL,
  to_team_id INTEGER DEFAULT NULL REFERENCES teams(id) ON DELETE SET NULL,
  transfer_date TEXT DEFAULT "",
  fee INTEGER DEFAULT 0,
  fee_type TEXT DEFAULT "paid",
  season TEXT DEFAULT "",
  notes TEXT DEFAULT ""
);
');

echo "Tables created.\n";

// ── Seed data ────────────────────────────────────────────────────────────
$pdo->exec('DELETE FROM transfers; DELETE FROM player_squad; DELETE FROM players; DELETE FROM teams; DELETE FROM leagues; DELETE FROM countries;');

// Countries
$countries = [
  ['Russia','🇷🇺'], ['England','🏴󠁧󠁢󠁥󠁮󠁧󠁿'], ['Germany','🇩🇪'], ['Spain','🇪🇸'], ['France','🇫🇷'], ['Italy','🇮🇹'],
];
$ic = $pdo->prepare('INSERT INTO countries(name,flag) VALUES(?,?)');
foreach ($countries as $c) $ic->execute($c);

$get_country = function(string $name) use ($pdo): int {
  return (int)$pdo->query("SELECT id FROM countries WHERE name='$name'")->fetchColumn();
};

// Leagues
$il = $pdo->prepare('INSERT INTO leagues(country_id,name,short_name,tier,season) VALUES(?,?,?,?,?)');
$il->execute([$get_country('Russia'), 'Russian Premier League', 'RPL', 1, '2025/26']);
$il->execute([$get_country('Russia'), 'FNL', 'FNL', 2, '2025/26']);
$il->execute([$get_country('England'), 'Premier League', 'EPL', 1, '2025/26']);
$il->execute([$get_country('England'), 'Championship', 'EFL', 2, '2025/26']);
$il->execute([$get_country('Germany'), 'Bundesliga', 'BL', 1, '2025/26']);
$il->execute([$get_country('Spain'), 'La Liga', 'LL', 1, '2025/26']);
$il->execute([$get_country('France'), 'Ligue 1', 'L1', 1, '2025/26']);
$il->execute([$get_country('Italy'), 'Serie A', 'SA', 1, '2025/26']);

$get_league = function(string $name) use ($pdo): int {
  $st = $pdo->prepare('SELECT id FROM leagues WHERE name=?');
  $st->execute([$name]);
  return (int)$st->fetchColumn();
};

// Teams (RPL)
$it = $pdo->prepare('INSERT INTO teams(league_id,name,short_name,city,stadium,founded,info) VALUES(?,?,?,?,?,?,?)');
$rpl = $get_league('Russian Premier League');
$teams_rpl = [
  ['Zenit St. Petersburg','Zenit','Saint Petersburg','Gazprom Arena',1925,'Record champions of modern Russian football'],
  ['CSKA Moscow','CSKA','Moscow','VEB Arena',1911,'Famous for their UEFA Cup victories'],
  ['Spartak Moscow','Spartak','Moscow','Otkritie Arena',1922,'The most popular club in Russia'],
  ['Lokomotiv Moscow','Lokomotiv','Moscow','RZD Arena',1923,'Railway workers\' club'],
  ['Krasnodar','Krasnodar','Krasnodar','Krasnodar Stadium',2008,'Privately owned modern club'],
  ['Dynamo Moscow','Dynamo','Moscow','VTB Arena',1923,'One of the oldest clubs in Russia'],
];
foreach ($teams_rpl as $t) $it->execute(array_merge([$rpl], $t));

// Teams (EPL)
$epl = $get_league('Premier League');
$teams_epl = [
  ['Manchester City','Man City','Manchester','Etihad Stadium',1880,'Backed by Abu Dhabi, multiple-title winners'],
  ['Arsenal','Arsenal','London','Emirates Stadium',1886,'The Gunners'],
  ['Liverpool','Liverpool','Liverpool','Anfield',1892,'You\'ll Never Walk Alone'],
  ['Chelsea','Chelsea','London','Stamford Bridge',1905,'The Blues'],
  ['Manchester United','Man Utd','Manchester','Old Trafford',1878,'Most successful English club'],
];
foreach ($teams_epl as $t) $it->execute(array_merge([$epl], $t));

// Teams (Bundesliga)
$bund = $get_league('Bundesliga');
$teams_bund = [
  ['Bayern Munich','Bayern','Munich','Allianz Arena',1900,'Record Bundesliga champions'],
  ['Borussia Dortmund','Dortmund','Dortmund','Signal Iduna Park',1909,'Yellow Wall'],
  ['Bayer Leverkusen','Leverkusen','Leverkusen','BayArena',1904,'Pharmacy club'],
];
foreach ($teams_bund as $t) $it->execute(array_merge([$bund], $t));

// Teams (La Liga)
$laliga = $get_league('La Liga');
$teams_laliga = [
  ['Real Madrid','R. Madrid','Madrid','Santiago Bernabéu',1902,'Most Champions League titles'],
  ['FC Barcelona','Barcelona','Barcelona','Spotify Camp Nou',1899,'Més que un club'],
  ['Atletico Madrid','Atletico','Madrid','Civitas Metropolitano',1903,'Colchoneros'],
];
foreach ($teams_laliga as $t) $it->execute(array_merge([$laliga], $t));

$get_team = function(string $name) use ($pdo): int {
  $st = $pdo->prepare('SELECT id FROM teams WHERE name=?');
  $st->execute([$name]);
  return (int)$st->fetchColumn();
};

echo "Countries, leagues, teams seeded.\n";

// ── Players ──────────────────────────────────────────────────────────────
$ip = $pdo->prepare('INSERT INTO players(name,full_name,nationality,birth_date,birth_place,position,foot,height) VALUES(?,?,?,?,?,?,?,?)');
$ips = $pdo->prepare('INSERT INTO player_squad(player_id,team_id,season,jersey_number,market_value,joined,contract_until) VALUES(?,?,?,?,?,?,?)');

$players = [
  // Zenit
  ['Mikhail Kerzhakov','Mikhail Sergeyevich Kerzhakov','Russia','1987-01-28','Kingisepp','GK','R',183, 'Zenit St. Petersburg',1,'2020-07-01','2026-06-30', 1_500_000],
  ['Yaroslav Rakitskiy','Yaroslav Andriyovych Rakitskiy','Ukraine','1989-08-03','Kramatorsk','CB','R',185, 'Zenit St. Petersburg',44,'2019-01-01','2026-06-30', 2_000_000],
  ['Douglas Santos','Douglas Willian Rodrigues dos Santos','Brazil','1994-04-07','Feira de Santana','LB','L',177, 'Zenit St. Petersburg',6,'2018-08-10','2027-06-30', 3_000_000],
  ['Andrey Mostovoy','Andrey Aleksandrovich Mostovoy','Russia','1998-05-17','Gatchina','LW','R',178, 'Zenit St. Petersburg',17,'2017-07-01','2026-06-30', 5_000_000],
  ['Claudinho','Claudinho Firmino Santos Alegria','Brazil','1997-04-04','Apucarana','CAM','R',169, 'Zenit St. Petersburg',10,'2021-07-01','2026-06-30', 8_000_000],
  ['Artem Dzyuba','Artem Aleksandrovich Dzyuba','Russia','1988-08-22','Moscow','ST','R',195, 'Zenit St. Petersburg',22,'2015-07-01','2026-06-30', 3_000_000],
  // CSKA
  ['Igor Akinfeev','Igor Vladimirovich Akinfeev','Russia','1986-04-08','Vidnoye','GK','R',186, 'CSKA Moscow',35,'2003-01-01','2027-06-30', 1_000_000],
  ['Mario Fernandes','Mario Lucio Duarte Costa','Brazil','1990-09-19','Maceió','RB','R',178, 'CSKA Moscow',2,'2012-08-31','2026-06-30', 2_500_000],
  ['Nikola Vlasic','Nikola Vlasic','Croatia','1997-10-04','Split','CAM','R',178, 'CSKA Moscow',10,'2018-07-01','2026-06-30', 12_000_000],
  // Spartak
  ['Alexander Maksimenko','Alexander Nikolaevich Maksimenko','Russia','1998-03-07','Nevinnomyssk','GK','R',193, 'Spartak Moscow',1,'2017-01-01','2027-06-30', 3_000_000],
  ['Quincy Promes','Quincy Promes','Netherlands','1992-01-04','Amsterdam','LW','R',178, 'Spartak Moscow',10,'2019-01-31','2026-06-30', 5_000_000],
  // Man City
  ['Ederson','Ederson Santana de Moraes','Brazil','1993-08-17','Osasco','GK','L',188, 'Manchester City',31,'2017-06-01','2026-06-30', 35_000_000],
  ['Ruben Dias','Rubén Gonçalo Silva Nascimento Dias','Portugal','1997-05-14','Agualva-Cacém','CB','R',187, 'Manchester City',3,'2020-09-29','2027-06-30', 65_000_000],
  ['Kevin De Bruyne','Kevin De Bruyne','Belgium','1991-06-28','Ghent','CM','R',181, 'Manchester City',17,'2015-08-30','2026-06-30', 40_000_000],
  ['Erling Haaland','Erling Braut Haaland','Norway','2000-07-21','Leeds','ST','L',194, 'Manchester City',9,'2022-07-01','2027-06-30', 180_000_000],
  // Arsenal
  ['David Raya','David Raya Martin','Spain','1995-09-15','Barcelona','GK','R',183, 'Arsenal',22,'2023-09-01','2028-06-30', 30_000_000],
  ['William Saliba','William Saliba','France','2001-03-24','Bondy','CB','R',192, 'Arsenal',12,'2022-07-01','2028-06-30', 70_000_000],
  ['Bukayo Saka','Bukayo Ayoyinka Saka','England','2001-09-05','London','RW','L',178, 'Arsenal',7,'2018-07-01','2027-06-30', 130_000_000],
  // Bayern
  ['Manuel Neuer','Manuel Peter Neuer','Germany','1986-03-27','Gelsenkirchen','GK','R',193, 'Bayern Munich',1,'2011-07-01','2026-06-30', 8_000_000],
  ['Thomas Müller','Thomas Müller','Germany','1989-09-13','Weilheim','CAM','R',186, 'Bayern Munich',25,'2000-07-01','2026-06-30', 8_000_000],
  ['Harry Kane','Harry Edward Kane','England','1993-07-28','London','ST','R',188, 'Bayern Munich',9,'2023-08-12','2027-06-30', 100_000_000],
  // Real Madrid
  ['Thibaut Courtois','Thibaut Nicolas Marc Courtois','Belgium','1992-05-11','Bree','GK','L',199, 'Real Madrid',1,'2018-07-03','2026-06-30', 25_000_000],
  ['Vinicius Jr.','Vinicius José Paixão de Oliveira Júnior','Brazil','2000-07-12','São Gonçalo','LW','R',176, 'Real Madrid',7,'2018-07-01','2028-06-30', 180_000_000],
  ['Jude Bellingham','Jude Victor William Bellingham','England','2003-06-29','Stourbridge','CM','R',186, 'Real Madrid',5,'2023-06-14','2029-06-30', 200_000_000],
  // Barcelona
  ['Marc-André ter Stegen','Marc-André ter Stegen','Germany','1992-04-30','Mönchengladbach','GK','L',187, 'FC Barcelona',1,'2014-07-01','2028-06-30', 25_000_000],
  ['Pedri','Pedro González López','Spain','2002-11-25','Santa Cruz de Tenerife','CM','R',174, 'FC Barcelona',8,'2020-07-01','2026-06-30', 90_000_000],
  ['Robert Lewandowski','Robert Lewandowski','Poland','1988-08-21','Warsaw','ST','R',185, 'FC Barcelona',9,'2022-07-19','2026-06-30', 15_000_000],
];

foreach ($players as $row) {
  [$name, $full_name, $nationality, $birth_date, $birth_place, $position, $foot, $height,
   $team_name, $jersey, $joined, $contract, $value] = $row;
  $ip->execute([$name, $full_name, $nationality, $birth_date, $birth_place, $position, $foot, $height]);
  $player_id = (int)$pdo->lastInsertId();
  $team_id = $get_team($team_name);
  $ips->execute([$player_id, $team_id, '2025/26', $jersey, $value, $joined, $contract]);
}

echo "Players seeded.\n";

// ── Transfers ────────────────────────────────────────────────────────────
$itr = $pdo->prepare('INSERT INTO transfers(player_id,from_team_id,to_team_id,transfer_date,fee,fee_type,season,notes) VALUES(?,?,?,?,?,?,?,?)');

$get_player_id = function(string $name) use ($pdo): int {
  $st = $pdo->prepare('SELECT id FROM players WHERE name=?');
  $st->execute([$name]);
  return (int)$st->fetchColumn();
};

$transfers = [
  ['Erling Haaland', 'Borussia Dortmund', 'Manchester City', '2022-07-01', 60_000_000, 'paid', '2022/23', 'Activated release clause'],
  ['Harry Kane', 'Manchester United', 'Bayern Munich', '2023-08-12', 100_000_000, 'paid', '2023/24', 'Long-awaited transfer'],
  ['Jude Bellingham', 'Borussia Dortmund', 'Real Madrid', '2023-06-14', 103_000_000, 'paid', '2023/24', ''],
  ['Vinicius Jr.', null, 'Real Madrid', '2018-07-01', 45_000_000, 'paid', '2018/19', 'Youth signing from Flamengo'],
  ['Bukayo Saka', null, 'Arsenal', '2018-07-01', 0, 'free', '2018/19', 'Academy graduate'],
  ['Pedri', null, 'FC Barcelona', '2020-07-01', 5_000_000, 'paid', '2020/21', 'From Las Palmas'],
  ['Kevin De Bruyne', null, 'Manchester City', '2015-08-30', 76_000_000, 'paid', '2015/16', 'From Wolfsburg'],
  ['William Saliba', null, 'Arsenal', '2019-07-25', 30_000_000, 'paid', '2019/20', 'Loan back to Saint-Étienne'],
  ['Nikola Vlasic', null, 'CSKA Moscow', '2018-07-01', 14_000_000, 'paid', '2018/19', 'From Everton'],
];

foreach ($transfers as $tr) {
  [$p_name, $from_name, $to_name, $date, $fee, $type, $season, $notes] = $tr;
  $pid = $get_player_id($p_name);
  $from_id = $from_name ? $get_team($from_name) : null;
  $to_id = $to_name ? $get_team($to_name) : null;
  if ($from_id === 0) $from_id = null;
  if ($to_id === 0) $to_id = null;
  $itr->execute([$pid, $from_id, $to_id, $date, $fee, $type, $season, $notes]);
}

echo "Transfers seeded.\n";
echo "Setup complete! DB: " . DB_PATH . "\n";

<?php
// php import_spartak.php
require_once __DIR__ . '/includes/functions.php';

$db = get_db();

// ── Find Spartak Moscow ──────────────────────────────────────────────────
$st = $db->prepare("SELECT id FROM teams WHERE name LIKE '%Spartak%' LIMIT 1");
$st->execute();
$team_id = (int)$st->fetchColumn();
if (!$team_id) { die("Spartak Moscow not found in DB. Run setup.php first.\n"); }
echo "Spartak Moscow team_id: $team_id\n";

// ── Helpers ──────────────────────────────────────────────────────────────
function parse_ru_date(string $s): string {
    if (!$s) return '';
    $months = [
        'янв' => '01','февр' => '02','фев' => '02','марта' => '03','мар' => '03',
        'апр' => '04','мая' => '05','июня' => '06','июн' => '06',
        'июля' => '07','июл' => '07','авг' => '08','сент' => '09','сен' => '09',
        'окт' => '10','нояб' => '11','ноя' => '11','дек' => '12',
    ];
    if (preg_match('/(\d{1,2})\s+([а-яё\.]+)\s+(\d{4})/ui', $s, $m)) {
        $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
        $mon_key = rtrim(mb_strtolower($m[2]), '.');
        $mon = $months[$mon_key] ?? '01';
        return "{$m[3]}-{$mon}-{$day}";
    }
    return '';
}

function parse_height(string $s): ?int {
    $s = str_replace(',', '.', $s);
    if (preg_match('/[\d.]+/', $s, $m)) return (int)(floatval($m[0]) * 100);
    return null;
}

function map_position(string $ru): string {
    return match(true) {
        str_contains($ru, 'Вратарь')          => 'GK',
        str_contains($ru, 'Центр. защитник')   => 'CB',
        str_contains($ru, 'Левый защитник')    => 'LB',
        str_contains($ru, 'Правый защитник')   => 'RB',
        str_contains($ru, 'Опорный')           => 'CDM',
        str_contains($ru, 'Центр. полузащитник')=> 'CM',
        str_contains($ru, 'Левый полузащитник')=> 'LM',
        str_contains($ru, 'Правый полузащитник')=> 'RM',
        str_contains($ru, 'Атак. полузащитник')=> 'CAM',
        str_contains($ru, 'Левый Вингер')      => 'LW',
        str_contains($ru, 'Правый Вингер')     => 'RW',
        str_contains($ru, 'нап.')              => 'ST',
        default                                => 'CM',
    };
}

function map_foot(string $ru): string {
    return match($ru) { 'левая' => 'L', 'правая' => 'R', 'обе' => 'Both', default => '' };
}

function map_nationality(string $ru): string {
    $map = [
        'Россия' => 'Russia', 'Сербия' => 'Serbia', 'Камерун' => 'Cameroon',
        'Гана' => 'Ghana', 'Молдова' => 'Moldova', 'Португалия' => 'Portugal',
        'Люксембург' => 'Luxembourg', 'Аргентина' => 'Argentina',
        'Бразилия' => 'Brazil', 'ДР Конго' => 'DR Congo',
        'Коста-Рика' => 'Costa Rica', 'Тринидад и Тобаго' => 'Trinidad and Tobago',
        'Украина' => 'Ukraine', 'Беларусь' => 'Belarus', 'Казахстан' => 'Kazakhstan',
        'Узбекистан' => 'Uzbekistan', 'Грузия' => 'Georgia',
        'Нигерия' => 'Nigeria', 'Кот-д\'Ивуар' => "Ivory Coast",
    ];
    return $map[$ru] ?? $ru;
}

// ── Squad data ────────────────────────────────────────────────────────────
$players = [
  ["Aleksandr Maksimenko","GK","Россия","1998-03-19","1,90m","левая","01 июля 2018 г.","30 июня 2027 г.",98],
  ["Ilya Pomazun","GK","Россия","1996-08-16","1,95m","правая","02 февр. 2025 г.","30 июня 2027 г.",1],
  ["Aleksandr Dovbnya","GK","Россия","1987-04-10","1,94m","правая","26 июля 2024 г.","30 июня 2026 г.",56],
  ["Srdjan Babic","CB","Сербия","1996-04-22","1,94m","левая","21 авг. 2023 г.","30 июня 2027 г.",6],
  ["Ruslan Litvinov","CB","Россия","2001-08-18","1,84m","правая","01 сент. 2021 г.","30 июня 2029 г.",68],
  ["Christopher Wooh","CB","Камерун","2001-09-18","1,91m","правая","05 сент. 2025 г.","30 июня 2029 г.",3],
  ["Alexander Djiku","CB","Гана","1994-08-09","1,82m","правая","09 сент. 2025 г.","30 июня 2027 г.",4],
  ["Oleg Reabciuk","LB","Молдова","1998-01-16","1,77m","левая","07 авг. 2023 г.","30 июня 2026 г.",2],
  ["Ilya Samoshnikov","LB","Россия","1997-11-14","1,77m","правая","19 авг. 2025 г.","30 июня 2028 г.",14],
  ["Daniil Denisov","RB","Россия","2002-10-21","1,85m","правая","01 июля 2022 г.","30 июня 2030 г.",97],
  ["Nail Umyarov","CDM","Россия","2000-06-27","1,82m","правая","01 янв. 2019 г.","30 июня 2029 г.",18],
  ["Christopher Martins","CDM","Люксембург","1997-02-19","1,88m","правая","01 июля 2022 г.","30 июня 2028 г.",35],
  ["Gedson Fernandes","CM","Португалия","1999-01-09","1,83m","правая","31 июля 2025 г.","30 июня 2029 г.",83],
  ["Roman Zobnin","CM","Россия","1994-02-11","1,82m","правая","01 июля 2016 г.","30 июня 2027 г.",47],
  ["Vladislav Saus","RM","Россия","2003-08-06","1,77m","правая","26 янв. 2026 г.","30 июня 2030 г.",17],
  ["Esequiel Barco","CAM","Аргентина","1999-03-29","1,66m","правая","25 июля 2024 г.","30 июня 2027 г.",5],
  ["Daniil Zorin","CAM","Россия","2004-02-22","1,80m","правая","01 июля 2023 г.","30 июня 2028 г.",28],
  ["Marquinhos","LW","Бразилия","1999-10-24","1,65m","правая","03 авг. 2024 г.","30 июня 2029 г.",10],
  ["Igor Dmitriev","LW","Россия","2004-07-24","1,78m","левая","26 июля 2024 г.","30 июня 2030 г.",27],
  ["Pavel Polekh","LW","Россия","2009-12-02","1,74m","","","30 июня 2028 г.",62],
  ["Pablo Solari","RW","Аргентина","2001-03-22","1,78m","правая","11 февр. 2025 г.","30 июня 2029 г.",7],
  ["Théo Bongonda","RW","ДР Конго","1995-11-20","1,75m","левая","12 июля 2023 г.","30 июня 2026 г.",77],
  ["Nikita Massalyga","RW","Россия","2007-10-09","1,87m","левая","01 янв. 2026 г.","30 июня 2028 г.",24],
  ["Manfred Ugalde","ST","Коста-Рика","2002-05-25","1,73m","правая","29 янв. 2024 г.","30 июня 2028 г.",9],
  ["Levi García","ST","Тринидад и Тобаго","1997-11-20","1,81m","левая","07 февр. 2025 г.","30 июня 2028 г.",11],
  ["Anton Zabolotnyi","ST","Россия","1991-06-13","1,91m","правая","01 июля 2025 г.","30 июня 2026 г.",91],
];

// ── Wipe old squad for Spartak ────────────────────────────────────────────
$db->prepare("DELETE FROM player_squad WHERE team_id=? AND season='2025/26'")->execute([$team_id]);
echo "Old 2025/26 squad cleared.\n";

// ── Insert / update players and squad entries ─────────────────────────────
$ins_p  = $db->prepare('INSERT INTO players(name,nationality,birth_date,position,foot,height) VALUES(?,?,?,?,?,?)');
$upd_p  = $db->prepare('UPDATE players SET nationality=?,birth_date=?,position=?,foot=?,height=? WHERE id=?');
$ins_sq = $db->prepare("INSERT INTO player_squad(player_id,team_id,season,jersey_number,market_value,joined,contract_until) VALUES(?,?,'2025/26',?,0,?,?)");

$count = 0;
foreach ($players as [$name, $pos_ru, $nat_ru, $dob, $height_raw, $foot_raw, $joined_raw, $contract_raw, $jersey]) {
    $pos      = is_string($pos_ru) && strlen($pos_ru) <= 4 ? $pos_ru : map_position($pos_ru);
    $nat      = map_nationality($nat_ru);
    $foot     = map_foot($foot_raw);
    $height   = parse_height($height_raw);
    $joined   = str_contains($joined_raw, '.') ? parse_ru_date($joined_raw) : $joined_raw;
    $contract = str_contains($contract_raw, '.') ? parse_ru_date($contract_raw) : $contract_raw;
    // dob is already ISO in our array
    $birth    = $dob;

    // Check if player already exists
    $find = $db->prepare('SELECT id FROM players WHERE name=?');
    $find->execute([$name]);
    $pid = $find->fetchColumn();

    if ($pid) {
        $upd_p->execute([$nat, $birth, $pos, $foot, $height, $pid]);
        echo "  Updated: $name\n";
    } else {
        $ins_p->execute([$name, $nat, $birth, $pos, $foot, $height]);
        $pid = (int)$db->lastInsertId();
        echo "  Inserted: $name\n";
    }

    $ins_sq->execute([$pid, $team_id, $jersey, $joined, $contract]);
    $count++;
}

echo "\nDone. $count players imported into Spartak Moscow squad (2025/26).\n";

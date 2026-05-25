<?php
// ──────────────────────────────────────────────────────────────────────────
// php import.php --source=<transfermarkt|sofifa|sortitoutsi|auto>
//                --team=<id|name> [--season=2025/26]
//                [--json=path/to/data.json | -]
//                [--url=<page-url>]                  (fetch attempt; usually blocked)
//                [--snippet]                         (print the browser snippet for --source)
//
// Reads a JSON squad from stdin or a file, normalises fields, upserts
// players + player_squad rows. Computes ea_rating when missing.
// ──────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/includes/functions.php';

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $s, string $enc = 'UTF-8'): string { return strtolower($s); }
}

// ── CLI args ──────────────────────────────────────────────────────────────
$opts = getopt('', ['source::','team::','season::','json::','url::','snippet','help']);
if (isset($opts['help']) || (PHP_SAPI === 'cli' && $argc === 1)) usage_and_exit();

$source  = $opts['source'] ?? 'auto';
$team_in = $opts['team']   ?? null;
$season  = $opts['season'] ?? '2025/26';

if (isset($opts['snippet'])) { print_snippet($source); exit; }
if (!$team_in) fail("--team is required (id or name substring)");

// ── Locate team ───────────────────────────────────────────────────────────
$db = get_db();
$team_id = is_numeric($team_in) ? (int)$team_in : 0;
if (!$team_id) {
    $st = $db->prepare("SELECT id,name FROM teams WHERE name LIKE ? LIMIT 2");
    $st->execute(['%' . $team_in . '%']);
    $rows = $st->fetchAll();
    if (count($rows) !== 1) fail("Team match ambiguous or not found: " . json_encode($rows));
    $team_id = (int)$rows[0]['id'];
    echo "Resolved team: " . $rows[0]['name'] . " (id $team_id)\n";
}

// ── Acquire raw input ─────────────────────────────────────────────────────
$raw = null;
if (isset($opts['url']) && $opts['url'] !== '') {
    $raw = try_fetch($opts['url']);
    if ($raw === null) {
        fwrite(STDERR, "Fetch failed (sites block bots). Use --snippet to copy a browser extractor, then pipe its JSON to this script.\n");
        exit(2);
    }
} elseif (isset($opts['json']) && $opts['json'] !== '' && $opts['json'] !== '-') {
    if (!is_file($opts['json'])) fail("File not found: {$opts['json']}");
    $raw = file_get_contents($opts['json']);
    if ($raw === false || $raw === '') fail("Could not read or empty file: {$opts['json']}");
} else {
    // stdin (also catches "-")
    if (posix_isatty(STDIN)) {
        fail("No input. Provide one of:\n"
           . "    --json=<path-to-file>   read JSON from a file\n"
           . "    --url=<page-url>        try to fetch (usually blocked)\n"
           . "    - (and pipe JSON)       e.g.  cat squad.json | php import.php ... -\n\n"
           . "  To get the JSON: run  php import.php --source=<src> --snippet,\n"
           . "  paste the snippet into your browser's DevTools console on the squad page,\n"
           . "  then save the copied clipboard contents to a file or pipe it here.");
    }
    $raw = stream_get_contents(STDIN);
    if ($raw === '' || $raw === false) fail("stdin was empty. Pipe JSON, e.g.  cat squad.json | php import.php ... -");
}

$data = json_decode($raw, true);
if (!is_array($data)) fail("Input is not valid JSON array. " . json_last_error_msg());

// ── Auto-detect source if not given ───────────────────────────────────────
if ($source === 'auto') $source = detect_source($data);
echo "Source: $source  |  Records: " . count($data) . "\n";

// ── Normalise → import ────────────────────────────────────────────────────
$db->prepare("DELETE FROM player_squad WHERE team_id=? AND season=?")->execute([$team_id, $season]);

$find_p = $db->prepare('SELECT id FROM players WHERE LOWER(name)=LOWER(?)');
$ins_p  = $db->prepare('INSERT INTO players(name,nationality,birth_date,position,foot,height,ea_rating) VALUES(?,?,?,?,?,?,?)');
$upd_p  = $db->prepare('UPDATE players SET nationality=COALESCE(NULLIF(?, ""), nationality),
                                           birth_date  =COALESCE(NULLIF(?, ""), birth_date),
                                           position    =COALESCE(NULLIF(?, ""), position),
                                           foot        =COALESCE(NULLIF(?, ""), foot),
                                           height      =COALESCE(?, height),
                                           ea_rating   =COALESCE(?, ea_rating)
                       WHERE id=?');
$ins_sq = $db->prepare('INSERT INTO player_squad(player_id,team_id,season,jersey_number,market_value,joined,contract_until) VALUES(?,?,?,?,?,?,?)');

$count = 0;
foreach ($data as $row) {
    $p = normalise($row, $source);
    if (!$p['name']) continue;

    $find_p->execute([$p['name']]);
    $pid = (int)$find_p->fetchColumn();

    if (!$p['ea_rating']) $p['ea_rating'] = estimate_ea_rating($p);

    if ($pid) {
        $upd_p->execute([$p['nationality'], $p['birth_date'], $p['position'], $p['foot'], $p['height'], $p['ea_rating'], $pid]);
    } else {
        $ins_p->execute([$p['name'], $p['nationality'], $p['birth_date'], $p['position'], $p['foot'], $p['height'], $p['ea_rating']]);
        $pid = (int)$db->lastInsertId();
    }
    $ins_sq->execute([$pid, $team_id, $season, $p['jersey'], $p['market_value'], $p['joined'], $p['contract_until']]);

    printf("  %-25s %-4s %3d EA  %s\n", substr($p['name'],0,25), $p['position'] ?: '-', $p['ea_rating'] ?: 0,
        $p['market_value'] ? format_value($p['market_value']) : '');
    $count++;
}

echo "\nDone. $count players imported into team $team_id ($season).\n";


// ──────────────────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────────────────

function usage_and_exit(): never {
    echo <<<TXT
Usage:
  php import.php --source=<transfermarkt|sofifa|sortitoutsi|auto> \\
                 --team=<id_or_name> [--season=2025/26] \\
                 [--json=file.json | --url=https://... | - ]
  php import.php --source=<src> --snippet      # print browser extractor

Examples:
  php import.php --source=sofifa --team="Spartak" --json=spartak.json
  curl ... | php import.php --source=transfermarkt --team=3 -
  php import.php --source=sofifa --snippet | xclip -selection clipboard

TXT;
    exit(0);
}

function fail(string $msg): never { fwrite(STDERR, "ERROR: $msg\n"); exit(1); }

function detect_source(array $rows): string {
    $first = $rows[0] ?? [];
    if (isset($first['overall'])) return 'sofifa';
    if (isset($first['ca']) || isset($first['fm_ability'])) return 'sortitoutsi';
    return 'transfermarkt';
}

function pick(array $row, array $keys): string {
    foreach ($keys as $k) if (isset($row[$k]) && $row[$k] !== '') return (string)$row[$k];
    return '';
}

/** Normalise a row from any source to a canonical shape. */
function normalise(array $row, string $source): array {
    $name        = pick($row, ['name','full_name','player_name','player']);
    $position    = map_position(pick($row, ['position','pos','role']));
    $nationality = map_nationality(pick($row, ['nationality','country','nation']));
    $birth_date  = parse_date(pick($row, ['birth_date','dob','date_of_birth','born']));
    $foot        = map_foot(pick($row, ['foot','preferred_foot','foot_pref']));
    $height      = parse_height(pick($row, ['height','height_cm']));
    $jersey      = (int)(pick($row, ['jersey','jersey_number','shirt','number']) ?: 0) ?: null;
    $market_val  = parse_money(pick($row, ['market_value','value','mv','price']));
    $ea_rating   = (int)(pick($row, ['ea_rating','overall','rating','ovr']) ?: 0) ?: null;
    $joined      = parse_date(pick($row, ['joined','join_date','signed','joined_club']));
    $contract    = parse_date(pick($row, ['contract_until','contract','contract_expires','expires']));

    // Transfermarkt JS snippet often misaligns columns: `value` may be a date
    if (pick($row, ['value']) && parse_date(pick($row, ['value'])) && $market_val === 0) {
        if (!$contract) $contract = parse_date($row['value']);
    }

    return [
        'name' => $name, 'position' => $position, 'nationality' => $nationality,
        'birth_date' => $birth_date, 'foot' => $foot, 'height' => $height,
        'jersey' => $jersey, 'market_value' => $market_val, 'ea_rating' => $ea_rating,
        'joined' => $joined, 'contract_until' => $contract,
    ];
}

/** Estimate FIFA/EA-style 50-94 overall when sofifa doesn't provide one. */
function estimate_ea_rating(array $p): ?int {
    $mv = $p['market_value'] ?? 0;
    if (!$mv) return null;
    // log10 of millions, scaled. €1M ≈ 65, €10M ≈ 73, €50M ≈ 80, €200M ≈ 87.
    $mv_m = max(0.1, $mv / 1_000_000);
    $base = 60 + log10($mv_m + 1) * 14;

    // Age curve: peak 25-29
    if ($p['birth_date']) {
        $age = (int)age($p['birth_date']);
        if ($age && $age < 22) $base -= (22 - $age) * 0.6;
        if ($age && $age > 31) $base -= ($age - 31) * 1.2;
    }
    return max(50, min(94, (int)round($base)));
}

/** Try fetching a public URL with realistic headers. Returns null on block/error. */
function try_fetch(string $url): ?string {
    $ctx = stream_context_create(['http' => [
        'method'  => 'GET',
        'timeout' => 8,
        'header'  => "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36\r\n"
                   . "Accept: text/html,application/xhtml+xml,application/json;q=0.9\r\n"
                   . "Accept-Language: en-US,en;q=0.8\r\n",
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) return null;
    $status = 0;
    foreach (($http_response_header ?? []) as $h) if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $h, $m)) $status = (int)$m[1];
    if ($status >= 400) return null;
    return $body;
}

// ── Field mappers ─────────────────────────────────────────────────────────

function map_position(string $raw): string {
    $raw = trim($raw);
    if ($raw === '') return '';
    // Already a known short code
    $codes = ['GK','CB','LB','RB','LWB','RWB','CDM','CM','CAM','LM','RM','LW','RW','CF','ST'];
    if (in_array(strtoupper($raw), $codes, true)) return strtoupper($raw);

    $r = mb_strtolower($raw, 'UTF-8');
    return match(true) {
        str_contains($r,'goalk') || str_contains($r,'врат')          => 'GK',
        str_contains($r,'left back') || str_contains($r,'левый защ') => 'LB',
        str_contains($r,'right back') || str_contains($r,'правый защ')=> 'RB',
        str_contains($r,'centre-back') || str_contains($r,'center-back')
          || str_contains($r,'centre back') || str_contains($r,'центр. защ') => 'CB',
        str_contains($r,'defensive mid') || str_contains($r,'опорн')  => 'CDM',
        str_contains($r,'attacking mid') || str_contains($r,'атак. полу') => 'CAM',
        str_contains($r,'left mid') || str_contains($r,'левый полу')  => 'LM',
        str_contains($r,'right mid') || str_contains($r,'правый полу') => 'RM',
        str_contains($r,'left wing') || str_contains($r,'левый винг') => 'LW',
        str_contains($r,'right wing') || str_contains($r,'правый винг')=> 'RW',
        str_contains($r,'centre forward') || str_contains($r,'center forward')
          || str_contains($r,'striker') || str_contains($r,'нап')      => 'ST',
        str_contains($r,'mid') || str_contains($r,'полу')              => 'CM',
        str_contains($r,'def') || str_contains($r,'защ')               => 'CB',
        str_contains($r,'forw') || str_contains($r,'fwd')              => 'ST',
        default => '',
    };
}

function map_foot(string $raw): string {
    $r = mb_strtolower(trim($raw), 'UTF-8');
    return match(true) {
        $r === 'l' || str_contains($r,'left')  || str_contains($r,'лев')  => 'L',
        $r === 'r' || str_contains($r,'right') || str_contains($r,'прав') => 'R',
        str_contains($r,'both') || str_contains($r,'обе')                 => 'Both',
        default => '',
    };
}

function map_nationality(string $raw): string {
    static $map = [
        'Россия'=>'Russia','Сербия'=>'Serbia','Камерун'=>'Cameroon','Гана'=>'Ghana',
        'Молдова'=>'Moldova','Португалия'=>'Portugal','Люксембург'=>'Luxembourg',
        'Аргентина'=>'Argentina','Бразилия'=>'Brazil','ДР Конго'=>'DR Congo',
        'Коста-Рика'=>'Costa Rica','Тринидад и Тобаго'=>'Trinidad and Tobago',
        'Украина'=>'Ukraine','Беларусь'=>'Belarus','Казахстан'=>'Kazakhstan',
        'Узбекистан'=>'Uzbekistan','Грузия'=>'Georgia','Нигерия'=>'Nigeria',
        "Кот-д'Ивуар"=>"Ivory Coast",'Англия'=>'England','Испания'=>'Spain',
        'Германия'=>'Germany','Франция'=>'France','Италия'=>'Italy',
        'Нидерланды'=>'Netherlands','Бельгия'=>'Belgium','Норвегия'=>'Norway',
        'Польша'=>'Poland','Хорватия'=>'Croatia',
    ];
    $r = trim($raw);
    return $map[$r] ?? $r;
}

function parse_height(string $raw): ?int {
    if ($raw === '') return null;
    $s = str_replace(',', '.', $raw);
    if (!preg_match('/[\d.]+/', $s, $m)) return null;
    $v = (float)$m[0];
    if ($v < 3)  $v *= 100;     // "1.90m"
    if ($v < 50) $v *= 100;     // safety
    return (int)round($v);
}

function parse_money(string $raw): int {
    if ($raw === '') return 0;
    // Reject anything that looks like a date — Transfermarkt scrapers often
    // mis-align "value" with the contract-date column.
    if (parse_date($raw) !== '') return 0;
    if (preg_match('/(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|янв|февр|март|апр|мая|июн|июл|авг|сент|окт|нояб|дек)/iu', $raw)) return 0;

    $s = preg_replace('/[€$£\s]/u', '', $raw);
    $s = str_replace(',', '.', $s);
    if (preg_match('/([\d.]+)\s*(m|млн)\b/iu', $s, $m)) return (int)round((float)$m[1] * 1_000_000);
    if (preg_match('/([\d.]+)\s*(k|тыс)\b/iu', $s, $m)) return (int)round((float)$m[1] * 1_000);
    if (preg_match('/^[\d.]+$/', $s)) {
        $v = (float)$s;
        return $v > 1000 ? (int)$v : (int)round($v * 1_000_000);
    }
    return 0;
}

function parse_date(string $raw): string {
    if ($raw === '') return '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) return $raw;

    // Russian: "01 июля 2018 г."
    $ru = [
        'январ'=>'01','феврал'=>'02','март'=>'03','апрел'=>'04','мая'=>'05','мае'=>'05','май'=>'05',
        'июн'=>'06','июл'=>'07','август'=>'08','авг'=>'08','сентябр'=>'09','сент'=>'09','сен'=>'09',
        'октябр'=>'10','окт'=>'10','ноябр'=>'11','нояб'=>'11','ноя'=>'11','декабр'=>'12','дек'=>'12',
        'янв'=>'01','февр'=>'02','фев'=>'02','мар'=>'03','апр'=>'04',
    ];
    if (preg_match('/(\d{1,2})\s+([а-яё\.]+)\s+(\d{4})/ui', $raw, $m)) {
        $key = rtrim(mb_strtolower($m[2], 'UTF-8'), '.');
        foreach ($ru as $needle => $num) if (str_starts_with($key, $needle)) {
            return sprintf('%s-%s-%02d', $m[3], $num, (int)$m[1]);
        }
    }

    // English: "Jul 28, 1993" or "28 Jul 1993"
    $ts = strtotime($raw);
    return $ts ? date('Y-m-d', $ts) : '';
}

// ──────────────────────────────────────────────────────────────────────────
// Browser snippets — printed by --snippet for the chosen source.
// ──────────────────────────────────────────────────────────────────────────
function print_snippet(string $source): void {
    $snippets = [
        'transfermarkt' => <<<'JS'
// Run on a Transfermarkt squad page (e.g. /spartak-moskva/kader/verein/232).
// Copies the squad as JSON to your clipboard.
copy(JSON.stringify([...document.querySelectorAll('table.items tbody tr.odd, table.items tbody tr.even')].map(r => {
  const c = r.querySelectorAll('td');
  return {
    name:        r.querySelector('.hauptlink a')?.innerText.trim(),
    jersey:      r.querySelector('.rn_nummer')?.innerText.trim(),
    position:    c[4]?.innerText.trim().split('\n').pop(),
    dob:         c[5]?.innerText.trim().split(' (')[0],
    nationality: r.querySelector('img.flaggenrahmen')?.title,
    height:      [...c].find(td => /\d+,\d{2}m/.test(td.innerText))?.innerText.trim(),
    foot:        [...c].find(td => /^(left|right|both)$/i.test(td.innerText.trim()))?.innerText.trim(),
    joined:      [...c].map(td => td.innerText.trim()).find(t => /\b(19|20)\d{2}\b/.test(t)),
    contract:    c[c.length-2]?.innerText.trim(),
    market_value:c[c.length-1]?.innerText.trim(),
  };
}), null, 2));
console.log('Squad JSON copied to clipboard.');
JS,
        'sofifa' => <<<'JS'
// Run on a SoFIFA team page (e.g. sofifa.com/team/...) to copy player ratings.
copy(JSON.stringify([...document.querySelectorAll('table tbody tr')].map(r => {
  const cells = r.querySelectorAll('td');
  const link  = r.querySelector('a[href*="/player/"]');
  if (!link) return null;
  const num = (sel) => { const el = r.querySelector(sel); return el ? parseInt(el.innerText.trim()) : null; };
  return {
    name:        link.innerText.trim(),
    overall:     num('td:nth-child(3)'),
    potential:   num('td:nth-child(4)'),
    position:    r.querySelector('.pos')?.innerText.trim(),
    age:         num('td:nth-child(2)'),
    nationality: r.querySelector('img[title]')?.title,
    market_value:r.querySelector('td:nth-child(7)')?.innerText.trim(),
    contract:    r.querySelector('td:nth-child(9)')?.innerText.trim(),
  };
}).filter(Boolean), null, 2));
console.log('SoFIFA squad copied.');
JS,
        'sortitoutsi' => <<<'JS'
// Run on a SortItOutSI/FM squad page. Copies players + FM CA as ea_rating.
copy(JSON.stringify([...document.querySelectorAll('table tbody tr')].map(r => {
  const link = r.querySelector('a[href*="/players/"]');
  if (!link) return null;
  const cells = [...r.querySelectorAll('td')].map(td => td.innerText.trim());
  const ca = parseInt(cells.find(c => /^\d{1,3}$/.test(c) && +c >= 50 && +c <= 200) || 0);
  return {
    name:        link.innerText.trim(),
    position:    cells[2],
    nationality: r.querySelector('img[title]')?.title || cells[3],
    age:         parseInt(cells.find(c => /^\d{1,2}$/.test(c)) || 0),
    ea_rating:   ca ? Math.round(ca / 2.2) : null,    // crude FM CA → EA overall
    market_value:cells.find(c => /[€$]/.test(c)) || '',
  };
}).filter(Boolean), null, 2));
console.log('FM squad copied.');
JS,
    ];

    $key = $source === 'auto' ? 'transfermarkt' : $source;
    if (!isset($snippets[$key])) fail("Unknown source: $source. Available: " . implode(', ', array_keys($snippets)));
    echo "// === Browser snippet for: $key ===\n";
    echo "// Open the squad page in your browser, F12 → Console, paste this, hit Enter.\n";
    echo "// Then run: pbpaste | php import.php --source=$key --team=<id_or_name> -\n\n";
    echo $snippets[$key] . "\n";
}

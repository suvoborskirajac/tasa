<?php
/**
 * eBird L7416435 — аутоматски endpoint
 * PIO Рајац · hotspot L7416435
 *
 * Враћа JSON са:
 *  - archiveChecklists  — јавне чеклисте са hotspot-а (eBird recent checklists)
 *  - speciesRegistry    — све забележене врсте (eBird Illustrated Checklist)
 *  - pinnedChecklists   — ручно закачене чеклисте
 *
 * Аутоматски се освежава: нова птица на eBird → следеће учитавање странице → постоји
 *
 * Постављање: wp-content/plugins/ptice-ebird-monitoring-fixed/ebird-l7416435-data.php
 */

define('EBD_HOTSPOT',     'L7416435');
define('EBD_TTL_ARCHIVE', 3600);        // 1 сат — архива чеклиста
define('EBD_TTL_SPECIES', 86400);       // 24 сата — списак врста
define('EBD_TTL_PINNED',  604800);      // 7 дана — закачене чеклисте

// --- ЕБИРД API КЉУЧ (опционо) ---
// Уписати кључ ако постоји: https://ebird.org/api/keygen
// Без кључа ради scraping illustrated checklist странице
define('EBIRD_API_KEY', '');

// ─── ПОМОЋНЕ ФУНКЦИЈЕ ────────────────────────────────────────

function ebd_cache_dir() {
    $d = __DIR__ . '/ebird-cache/ebd-data';
    if (!is_dir($d)) @mkdir($d, 0755, true);
    return $d;
}
function ebd_cache_get($key, $ttl) {
    $f = ebd_cache_dir() . '/' . md5($key) . '.json';
    if (is_file($f) && time() - filemtime($f) < $ttl) {
        $j = json_decode(@file_get_contents($f), true);
        if (is_array($j)) return $j;
    }
    return null;
}
function ebd_cache_put($key, $data) {
    $f = ebd_cache_dir() . '/' . md5($key) . '.json';
    @file_put_contents($f, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
function ebd_fetch($url, $headers = []) {
    $ua = 'Mozilla/5.0 (compatible; PIO-Rajac/2.0; +https://piorajac.rs/)';
    $default_headers = [
        'Accept: application/json, text/html, */*;q=0.8',
        'Accept-Language: en-US,en;q=0.9,sr;q=0.8',
        'Referer: https://ebird.org/'
    ];
    $all_headers = array_merge($default_headers, $headers);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => $ua,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTPHEADER     => $all_headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($body !== false && $status >= 200 && $status < 400) return (string) $body;
    }
    $ctx = stream_context_create(['http' => [
        'method'        => 'GET',
        'timeout'       => 30,
        'ignore_errors' => true,
        'header'        => "User-Agent: $ua\r\n" . implode("\r\n", $all_headers) . "\r\n"
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    return $body !== false ? (string) $body : '';
}

// ─── 1. LISTE ВРСТА ──────────────────────────────────────────
// Повлачи са eBird Illustrated Checklist странице и/или API-ја
// Аутоматски: нова врста на eBird → следеће освежавање → појављује се

function ebd_fetch_species_list() {
    $cache_key = 'species-' . EBD_HOTSPOT;
    $cached = ebd_cache_get($cache_key, EBD_TTL_SPECIES);
    if ($cached) return $cached;

    $species = [];

    // Метод 1: eBird API v2 (са кључем)
    if (EBIRD_API_KEY) {
        $url  = 'https://api.ebird.org/v2/product/spplist/' . rawurlencode(EBD_HOTSPOT);
        $body = ebd_fetch($url, ['X-eBirdApiToken: ' . EBIRD_API_KEY]);
        if ($body) {
            $codes = json_decode($body, true);
            if (is_array($codes)) {
                foreach ($codes as $code) {
                    if (is_string($code) && $code) {
                        $species[] = ['speciesCode' => strtolower(trim($code)), 'sciName' => '', 'comName' => ''];
                    }
                }
            }
        }
    }

    // Метод 2: eBird Illustrated Checklist HTML scraping (без кључа)
    if (empty($species)) {
        $url  = 'https://ebird.org/hotspot/' . EBD_HOTSPOT . '/illustrated-checklist';
        $body = ebd_fetch($url);
        if ($body) {
            // Извлачи taxonCode из HTML атрибута
            if (preg_match_all('/data-taxon-code=["\']([a-z0-9]+)["\']/', $body, $m)) {
                foreach (array_unique($m[1]) as $code) {
                    $species[] = ['speciesCode' => $code, 'sciName' => '', 'comName' => ''];
                }
            }
            // Алтернативно: JS низ са врстама
            if (empty($species) && preg_match_all('/"speciesCode"\s*:\s*"([a-z0-9]+)"/', $body, $m)) {
                foreach (array_unique($m[1]) as $code) {
                    $species[] = ['speciesCode' => $code, 'sciName' => '', 'comName' => ''];
                }
            }
        }
    }

    // Метод 3: eBird recent species за hotspot
    if (empty($species)) {
        $url  = 'https://api.ebird.org/v2/data/obs/' . rawurlencode(EBD_HOTSPOT) . '/recent?back=30';
        $hdrs = EBIRD_API_KEY ? ['X-eBirdApiToken: ' . EBIRD_API_KEY] : [];
        $body = ebd_fetch($url, $hdrs);
        if ($body) {
            $obs = json_decode($body, true);
            if (is_array($obs)) {
                $seen = [];
                foreach ($obs as $o) {
                    $code = strtolower(trim($o['speciesCode'] ?? ''));
                    if ($code && !isset($seen[$code])) {
                        $seen[$code] = true;
                        $species[] = [
                            'speciesCode' => $code,
                            'sciName'     => $o['sciName']  ?? '',
                            'comName'     => $o['comName']  ?? '',
                            'locName'     => $o['locName']  ?? '',
                            'obsDt'       => $o['obsDt']    ?? '',
                        ];
                    }
                }
            }
        }
    }

    ebd_cache_put($cache_key, $species);
    return $species;
}

// ─── 2. АРХИВА ЧЕКЛИСТА ──────────────────────────────────────
// Повлачи листу јавних чеклиста са hotspot-а
// Аутоматски: нова eBird чеклиста → освежава се у року од EBD_TTL_ARCHIVE секунди

function ebd_fetch_archive_checklists() {
    $cache_key = 'archive-' . EBD_HOTSPOT;
    $cached = ebd_cache_get($cache_key, EBD_TTL_ARCHIVE);
    if ($cached) return $cached;

    $checklists = [];

    // eBird API v2: recent checklists for hotspot
    $url  = 'https://api.ebird.org/v2/product/lists/' . rawurlencode(EBD_HOTSPOT) . '?maxResults=50';
    $hdrs = EBIRD_API_KEY ? ['X-eBirdApiToken: ' . EBIRD_API_KEY] : [];
    $body = ebd_fetch($url, $hdrs);

    if ($body) {
        $data = json_decode($body, true);
        if (is_array($data)) {
            foreach ($data as $cl) {
                $sub_id = $cl['subId'] ?? '';
                if (!$sub_id) continue;
                $checklists[] = [
                    'subId'           => $sub_id,
                    'url'             => 'https://ebird.org/checklist/' . $sub_id,
                    'locName'         => $cl['loc']['name'] ?? ($cl['locName'] ?? 'Рајац'),
                    'userDisplayName' => $cl['userDisplayName'] ?? 'eBird посматрач',
                    'obsDt'           => substr($cl['obsDt'] ?? '', 0, 10),
                    'obsTime'         => $cl['obsTime'] ?? '',
                    'numSpecies'      => (int) ($cl['numSpecies'] ?? 0),
                    'numObservers'    => (int) ($cl['numObservers'] ?? 1),
                    'durationMinutes' => (int) ($cl['durationHrs'] * 60 ?? 0),
                    'distanceKm'      => (float) ($cl['distanceKm'] ?? 0),
                    'protocolName'    => $cl['protocolName'] ?? '',
                    'allObsReported'  => (bool) ($cl['allObsReported'] ?? false),
                    'comments'        => $cl['comments'] ?? '',
                    'species'         => [], // species se učitavaju na zahtev
                ];
            }
        }
    }

    // Ако API није вратио ништа (без кључа) — HTML scraping recent checklists
    if (empty($checklists)) {
        $url  = 'https://ebird.org/hotspot/' . EBD_HOTSPOT . '/recent-checklists';
        $body = ebd_fetch($url);
        if ($body) {
            // Извлачи subId из HTML линкова
            preg_match_all('/href=["\']https:\/\/ebird\.org\/checklist\/(S\d+)["\']/', $body, $m);
            preg_match_all('/\/(S\d+)/', $body, $m2);
            $sub_ids = array_unique(array_merge($m[1] ?? [], $m2[1] ?? []));
            foreach (array_slice($sub_ids, 0, 30) as $sub_id) {
                // Датум из HTML текста поред линка
                $checklists[] = [
                    'subId'      => $sub_id,
                    'url'        => 'https://ebird.org/checklist/' . $sub_id,
                    'locName'    => 'Рајац',
                    'obsDt'      => '',
                    'numSpecies' => 0,
                    'species'    => [],
                ];
            }
        }
    }

    ebd_cache_put($cache_key, $checklists);
    return $checklists;
}

// ─── 3. ЗАКАЧЕНЕ ЧЕКЛИСТЕ ────────────────────────────────────
// Ручно уписати subId-ове које желиш да се увек приказују на врху

function ebd_pinned_checklists() {
    // Уписати нове subId-ове кад снимиш теренски запис на eBird:
    $pinned_ids = [
        'S330345122', // 1. мај 2026. — Горњи Бранетићи
        // 'S123456789',  // ← додај нове овде
    ];

    $cache_key = 'pinned-details';
    $cached = ebd_cache_get($cache_key, EBD_TTL_PINNED);
    if ($cached) return $cached;

    $result = [];
    foreach ($pinned_ids as $sub_id) {
        $cl = ebd_fetch_checklist_detail($sub_id);
        if ($cl) $result[] = $cl;
    }
    if (!empty($result)) ebd_cache_put($cache_key, $result);
    return $result;
}

// ─── 4. ДЕТАЉИ ЧЕКЛИСТЕ ─────────────────────────────────────

function ebd_fetch_checklist_detail($sub_id) {
    $sub_id = preg_replace('/[^A-Za-z0-9]/', '', $sub_id);
    if (!$sub_id) return null;

    $cache_key = 'cl-' . $sub_id;
    $cached = ebd_cache_get($cache_key, EBD_TTL_PINNED);
    if ($cached) return $cached;

    // eBird public checklist JSON endpoint
    $url  = 'https://ebird.org/checklist/' . $sub_id;
    $body = ebd_fetch($url, ['Accept: text/html,*/*']);

    if (!$body) return null;

    // Парсирај JSON из HTML (eBird га уграђује у <script type="application/ld+json">)
    $cl_data = null;
    if (preg_match('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $body, $m)) {
        $cl_data = json_decode(trim($m[1]), true);
    }

    // Алтернативно: __NEXT_DATA__ или window.__initialData__
    if (!$cl_data && preg_match('/__NEXT_DATA__\s*=\s*(\{.*?\})\s*;?\s*<\/script>/si', $body, $m)) {
        $nd = json_decode($m[1], true);
        $cl_data = $nd['props']['pageProps']['checklist'] ?? $nd['props']['pageProps'] ?? null;
    }

    // Извлачи врсте из HTML директно
    $species = [];
    if (preg_match_all('/data-species-code=["\']([a-z0-9]+)["\'][^>]*>.*?<span[^>]*class=["\'][^"\']*species-name[^"\']*["\'][^>]*>([^<]*)<\/span>/si', $body, $sm)) {
        for ($i = 0; $i < count($sm[1]); $i++) {
            $species[] = ['speciesCode' => $sm[1][$i], 'comName' => trim($sm[2][$i]), 'count' => 'X'];
        }
    }

    $result = [
        'group'           => 'pinned',
        'subId'           => $sub_id,
        'url'             => 'https://ebird.org/checklist/' . $sub_id,
        'locName'         => 'Рајац — eBird чеклиста ' . $sub_id,
        'userDisplayName' => 'Зоран Танасијевић',
        'obsDt'           => '',
        'numSpecies'      => count($species),
        'species'         => $species,
        'protocolName'    => 'Traveling',
        'allObsReported'  => true,
    ];

    if ($cl_data) {
        // eBird LD+JSON формат
        $result['locName']  = $cl_data['location']['name']  ?? $cl_data['name']         ?? $result['locName'];
        $result['obsDt']    = $cl_data['startTime']         ?? $cl_data['dateCreated']   ?? '';
        $result['userDisplayName'] = $cl_data['author']['name'] ?? $result['userDisplayName'];
    }

    ebd_cache_put($cache_key, $result);
    return $result;
}

// ─── 5. ГЛАВНИ OUTPUT ────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300, stale-while-revalidate=1800');
header('Access-Control-Allow-Origin: *');

// Принудно освежавање (admin параметар)
if (isset($_GET['refresh']) && $_GET['refresh'] === 'force') {
    // Обриши cache
    $cache_dir = __DIR__ . '/ebird-cache/ebd-data';
    if (is_dir($cache_dir)) {
        foreach (glob($cache_dir . '/*.json') as $f) @unlink($f);
    }
}

$species    = ebd_fetch_species_list();
$archive    = ebd_fetch_archive_checklists();
$pinned     = ebd_pinned_checklists();

// Додај структуру коју HTML очекује за speciesRegistry
$registry = array_map(function($sp) {
    return [
        'speciesCode' => $sp['speciesCode'] ?? '',
        'sciName'     => $sp['sciName']     ?? '',
        'comName'     => $sp['comName']     ?? '',
        'srName'      => $sp['srName']      ?? ($sp['comName'] ?? ''),
        'obsDt'       => $sp['obsDt']       ?? '',
    ];
}, $species);

echo json_encode([
    'ok'               => true,
    'generatedAt'      => gmdate('c'),
    'hotspot'          => EBD_HOTSPOT,
    'archiveChecklists' => $archive,
    'pinnedChecklists'  => $pinned,
    'speciesRegistry'   => $registry,
    'latestChecklists'  => array_slice($archive, 0, 3),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

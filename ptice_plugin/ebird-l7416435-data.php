<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=1800');

$HOTSPOT_ID = 'L7416435';
$HOTSPOT_NAME = 'Rajac';
$HOTSPOT_URL = 'https://ebird.org/hotspot/L7416435/recent-checklists';
$EBIRD_BASE = 'https://api.ebird.org/v2';
$CACHE_DIR = __DIR__ . '/ebird-cache';
$CACHE_FILE = $CACHE_DIR . '/l7416435-data.json';
$CACHE_TTL = 6 * 60 * 60;
$MAX_ARCHIVE = 50;
$MAX_LATEST = 3;

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle): bool
    {
        return $needle === '' || strpos((string) $haystack, (string) $needle) !== false;
    }
}

$configFile = __DIR__ . '/ebird-l7416435-config.php';
if (is_file($configFile)) {
    require_once $configFile;
}

$apiKey = '';
if (defined('EBIRD_API_KEY')) {
    $apiKey = trim((string) EBIRD_API_KEY);
}
if ($apiKey === '') {
    $apiKey = trim((string) getenv('EBIRD_API_KEY'));
}

$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
$maxRequest = isset($_GET['max']) ? (int) $_GET['max'] : $MAX_ARCHIVE;
if ($maxRequest < 3) {
    $maxRequest = 3;
}
if ($maxRequest > 200) {
    $maxRequest = 200;
}

if (!$forceRefresh && is_file($CACHE_FILE) && (time() - filemtime($CACHE_FILE) < $CACHE_TTL)) {
    readfile($CACHE_FILE);
    exit;
}

function finish_json(array $payload, string $cacheFile = '', bool $writeCache = true): void
{
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        $json = '{"ok":false,"errors":["JSON encoding error"]}';
    }
    if ($writeCache && $cacheFile !== '') {
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($cacheFile, $json, LOCK_EX);
    }
    echo $json;
    exit;
}

function add_query(string $url, array $params): string
{
    $params = array_filter($params, static fn($v) => $v !== null && $v !== '');
    if (!$params) {
        return $url;
    }
    return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
}

function http_json(string $url, string $apiKey = '', array $params = [], int $timeout = 20): array
{
    $url = add_query($url, $params);
    $headers = ['Accept: application/json'];
    if ($apiKey !== '') {
        $headers[] = 'x-ebirdapitoken: ' . $apiKey;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'PIO Rajac bird monitoring/1.1 (+https://piorajac.rs/)'
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($body === false || $body === '') {
        return ['ok' => false, 'status' => $status, 'error' => $err !== '' ? $err : 'Empty response', 'data' => null];
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['ok' => false, 'status' => $status, 'error' => 'Invalid JSON response', 'data' => null, 'raw' => mb_substr((string) $body, 0, 500)];
    }
    if ($status < 200 || $status >= 300) {
        $message = $data['errors'][0]['title'] ?? $data['message'] ?? $data['error'] ?? ('HTTP ' . $status);
        return ['ok' => false, 'status' => $status, 'error' => (string) $message, 'data' => $data];
    }
    return ['ok' => true, 'status' => $status, 'error' => '', 'data' => $data];
}

function http_text(string $url, int $timeout = 16): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml'],
        CURLOPT_USERAGENT => 'Mozilla/5.0 PIO-Rajac-Monitoring/1.1'
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($body === false || $body === '') {
        return ['ok' => false, 'status' => $status, 'error' => $err !== '' ? $err : 'Empty response', 'body' => ''];
    }
    return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'error' => '', 'body' => (string) $body];
}

function unique_subids(array $items): array
{
    $out = [];
    foreach ($items as $item) {
        if (is_array($item)) {
            $subId = $item['subId'] ?? $item['subID'] ?? $item['subid'] ?? '';
        } else {
            $subId = (string) $item;
        }
        $subId = strtoupper(trim((string) $subId));
        if (preg_match('/^S\d+$/', $subId) && !in_array($subId, $out, true)) {
            $out[] = $subId;
        }
    }
    return $out;
}

function read_manual_ids(): array
{
    $manualFile = __DIR__ . '/ebird-l7416435-manual.json';
    $fallback = ['pinned' => ['S330345122'], 'extra' => []];
    if (!is_file($manualFile)) {
        return $fallback;
    }
    $json = json_decode((string) file_get_contents($manualFile), true);
    if (!is_array($json)) {
        return $fallback;
    }
    $pinned = [];
    $extra = [];
    if (isset($json['pinnedChecklists']) && is_array($json['pinnedChecklists'])) {
        $pinned = unique_subids($json['pinnedChecklists']);
    }
    if (isset($json['extraArchiveChecklists']) && is_array($json['extraArchiveChecklists'])) {
        $extra = unique_subids($json['extraArchiveChecklists']);
    }
    if (!$pinned) {
        $pinned = ['S330345122'];
    }
    return ['pinned' => $pinned, 'extra' => $extra];
}

function scrape_recent_page_subids(string $url): array
{
    $html = http_text($url);
    if (!$html['ok']) {
        return [];
    }
    preg_match_all('/S\d{6,}/', $html['body'], $matches);
    return unique_subids($matches[0] ?? []);
}

function local_species_details(string $speciesCode): array
{
    static $details = [
        'eurwre' => ['srName' => 'Царић', 'comName' => 'Eurasian Wren', 'sciName' => 'Troglodytes troglodytes', 'imageSlug' => 'troglodytes-troglodytes'],
        'eurbla' => ['srName' => 'Обични кос', 'comName' => 'Common Blackbird', 'sciName' => 'Turdus merula', 'imageSlug' => 'turdus-merula'],
        'eurrob1' => ['srName' => 'Црвендаћ', 'comName' => 'European Robin', 'sciName' => 'Erithacus rubecula', 'imageSlug' => 'erithacus-rubecula'],
        'comcha' => ['srName' => 'Зеба', 'comName' => 'Common Chaffinch', 'sciName' => 'Fringilla coelebs', 'imageSlug' => 'fringilla-coelebs'],
        'gretit1' => ['srName' => 'Велика сеница', 'comName' => 'Great Tit', 'sciName' => 'Parus major', 'imageSlug' => 'velika-senica'],
        'blutit' => ['srName' => 'Плава сеница', 'comName' => 'Eurasian Blue Tit', 'sciName' => 'Cyanistes caeruleus', 'imageSlug' => 'plava-senica'],
        'eurnut2' => ['srName' => 'Бргљез', 'comName' => 'Eurasian Nuthatch', 'sciName' => 'Sitta europaea', 'imageSlug' => 'brgljez'],
        'eurjay1' => ['srName' => 'Сојка', 'comName' => 'Eurasian Jay', 'sciName' => 'Garrulus glandarius', 'imageSlug' => 'garrulus-glandarius'],
        'combuz1' => ['srName' => 'Мишар', 'comName' => 'Common Buzzard', 'sciName' => 'Buteo buteo', 'imageSlug' => 'buteo-buteo'],
        'grswoo' => ['srName' => 'Велики детлић', 'comName' => 'Great Spotted Woodpecker', 'sciName' => 'Dendrocopos major', 'imageSlug' => 'veliki-detlic'],
        'grtspo1' => ['srName' => 'Велики детлић', 'comName' => 'Great Spotted Woodpecker', 'sciName' => 'Dendrocopos major', 'imageSlug' => 'veliki-detlic'],
        'lessp1' => ['srName' => 'Мали детлић', 'comName' => 'Lesser Spotted Woodpecker', 'sciName' => 'Dryobates minor', 'imageSlug' => 'mali-detlic'],
        'eurmag1' => ['srName' => 'Сврака', 'comName' => 'Eurasian Magpie', 'sciName' => 'Pica pica', 'imageSlug' => 'pica-pica'],
        'carrio1' => ['srName' => 'Гавран', 'comName' => 'Common Raven', 'sciName' => 'Corvus corax', 'imageSlug' => 'gavran'],
        'comrav' => ['srName' => 'Гавран', 'comName' => 'Common Raven', 'sciName' => 'Corvus corax', 'imageSlug' => 'gavran'],
        'hoocro1' => ['srName' => 'Сива врана', 'comName' => 'Hooded Crow', 'sciName' => 'Corvus cornix', 'imageSlug' => 'siva-vrana'],
        'barswa' => ['srName' => 'Сеоска ласта', 'comName' => 'Barn Swallow', 'sciName' => 'Hirundo rustica', 'imageSlug' => 'hirundo-rustica'],
        'grywar1' => ['srName' => 'Црноглава грмуша', 'comName' => 'Eurasian Blackcap', 'sciName' => 'Sylvia atricapilla', 'imageSlug' => 'sylvia-atricapilla'],
        'eurbla2' => ['srName' => 'Црноглава грмуша', 'comName' => 'Eurasian Blackcap', 'sciName' => 'Sylvia atricapilla', 'imageSlug' => 'sylvia-atricapilla'],
        'songth1' => ['srName' => 'Дрозд певач', 'comName' => 'Song Thrush', 'sciName' => 'Turdus philomelos', 'imageSlug' => 'turdus-philomelos'],
        'misthr1' => ['srName' => 'Дрозд имелаш', 'comName' => 'Mistle Thrush', 'sciName' => 'Turdus viscivorus', 'imageSlug' => 'turdus-viscivorus'],
        'eurser1' => ['srName' => 'Жутарица', 'comName' => 'European Serin', 'sciName' => 'Serinus serinus', 'imageSlug' => 'serinus-serinus'],
        'eurgre1' => ['srName' => 'Зелениш', 'comName' => 'European Greenfinch', 'sciName' => 'Chloris chloris', 'imageSlug' => 'chloris-chloris'],
        'golori1' => ['srName' => 'Вуга', 'comName' => 'Eurasian Golden Oriole', 'sciName' => 'Oriolus oriolus', 'imageSlug' => 'oriolus-oriolus'],
        'houspa' => ['srName' => 'Врабац покућар', 'comName' => 'House Sparrow', 'sciName' => 'Passer domesticus', 'imageSlug' => 'vrabac'],
        'linspa1' => ['srName' => 'Врабац покућар', 'comName' => 'House Sparrow', 'sciName' => 'Passer domesticus', 'imageSlug' => 'vrabac'],
        'eursta' => ['srName' => 'Чворак', 'comName' => 'European Starling', 'sciName' => 'Sturnus vulgaris', 'imageSlug' => 'sturnus-vulgaris'],
        'whisto1' => ['srName' => 'Бела плиска', 'comName' => 'White Wagtail', 'sciName' => 'Motacilla alba', 'imageSlug' => 'motacilla-alba'],
        'whiwag' => ['srName' => 'Бела плиска', 'comName' => 'White Wagtail', 'sciName' => 'Motacilla alba', 'imageSlug' => 'motacilla-alba'],
        'eucdov' => ['srName' => 'Гугутка', 'comName' => 'Eurasian Collared-Dove', 'sciName' => 'Streptopelia decaocto', 'imageSlug' => 'streptopelia-decaocto'],
        'eurcoo' => ['srName' => 'Кукавица', 'comName' => 'Common Cuckoo', 'sciName' => 'Cuculus canorus', 'imageSlug' => 'kukavica'],
        'eurgol' => ['srName' => 'Штиглић', 'comName' => 'European Goldfinch', 'sciName' => 'Carduelis carduelis', 'imageSlug' => 'carduelis-carduelis'],
        'hawfin' => ['srName' => 'Батокљун', 'comName' => 'Hawfinch', 'sciName' => 'Coccothraustes coccothraustes', 'imageSlug' => 'coccothraustes-coccothraustes'],
        'comred' => ['srName' => 'Обична црвенрепка', 'comName' => 'Common Redstart', 'sciName' => 'Phoenicurus phoenicurus', 'imageSlug' => 'phoenicurus-phoenicurus'],
        'blared1' => ['srName' => 'Црна црвенрепка', 'comName' => 'Black Redstart', 'sciName' => 'Phoenicurus ochruros', 'imageSlug' => 'phoenicurus-ochruros'],
        'grwpwo1' => ['srName' => 'Зелена жуна', 'comName' => 'European Green Woodpecker', 'sciName' => 'Picus viridis', 'imageSlug' => 'picus-viridis'],
        'yelham1' => ['srName' => 'Стрнадица жутовољка', 'comName' => 'Yellowhammer', 'sciName' => 'Emberiza citrinella', 'imageSlug' => 'emberiza-citrinella'],
        'cornbu1' => ['srName' => 'Велика стрнадица', 'comName' => 'Corn Bunting', 'sciName' => 'Emberiza calandra', 'imageSlug' => 'emberiza-calandra'],
        'corbun1' => ['srName' => 'Велика стрнадица', 'comName' => 'Corn Bunting', 'sciName' => 'Emberiza calandra', 'imageSlug' => 'emberiza-calandra'],
        'ortbun1' => ['srName' => 'Вртна стрнадица', 'comName' => 'Ortolan Bunting', 'sciName' => 'Emberiza hortulana', 'imageSlug' => 'emberiza-hortulana'],
        'woolar1' => ['srName' => 'Шумска шева', 'comName' => 'Woodlark', 'sciName' => 'Lullula arborea', 'imageSlug' => 'lullula-arborea'],
        'skylar' => ['srName' => 'Пољска шева', 'comName' => 'Eurasian Skylark', 'sciName' => 'Alauda arvensis', 'imageSlug' => 'alauda-arvensis'],
        'rebshr1' => ['srName' => 'Руси сврачак', 'comName' => 'Red-backed Shrike', 'sciName' => 'Lanius collurio', 'imageSlug' => 'lanius-collurio'],

        'grecor' => ['srName' => 'Велики вранац', 'comName' => 'Great Cormorant', 'sciName' => 'Phalacrocorax carbo', 'imageSlug' => 'veliki-vranac'],
        'bkbsto' => ['srName' => 'Црна рода', 'comName' => 'Black Stork', 'sciName' => 'Ciconia nigra', 'imageSlug' => 'ciconia-nigra'],
        'blasto1' => ['srName' => 'Црна рода', 'comName' => 'Black Stork', 'sciName' => 'Ciconia nigra', 'imageSlug' => 'ciconia-nigra'],
        'europeanblackstork' => ['srName' => 'Црна рода', 'comName' => 'Black Stork', 'sciName' => 'Ciconia nigra', 'imageSlug' => 'ciconia-nigra'],
        'impeag1' => ['srName' => 'Орао крсташ', 'comName' => 'Eastern Imperial Eagle', 'sciName' => 'Aquila heliaca', 'imageSlug' => 'orao-krstas'],
        'imeeag1' => ['srName' => 'Орао крсташ', 'comName' => 'Eastern Imperial Eagle', 'sciName' => 'Aquila heliaca', 'imageSlug' => 'orao-krstas'],
        'goleag' => ['srName' => 'Сури орао', 'comName' => 'Golden Eagle', 'sciName' => 'Aquila chrysaetos', 'imageSlug' => 'suri-orao'],
        'comcuc' => ['srName' => 'Кукавица', 'comName' => 'Common Cuckoo', 'sciName' => 'Cuculus canorus', 'imageSlug' => 'kukavica'],
        'whinch2' => ['srName' => 'Обична траварка', 'comName' => 'Whinchat', 'sciName' => 'Saxicola rubetra', 'imageSlug' => 'obicna-travarka'],
        'martit2' => ['srName' => 'Сива сеница', 'comName' => 'Marsh Tit', 'sciName' => 'Poecile palustris', 'imageSlug' => 'siva-senica'],
        'tawowl1' => ['srName' => 'Шумска сова', 'comName' => 'Tawny Owl', 'sciName' => 'Strix aluco', 'imageSlug' => 'sumska-sova'],
        'eurjac' => ['srName' => 'Чавка', 'comName' => 'Eurasian Jackdaw', 'sciName' => 'Corvus monedula', 'imageSlug' => 'cavka'],
        'commyn' => ['srName' => 'Чавка', 'comName' => 'Eurasian Jackdaw', 'sciName' => 'Corvus monedula', 'imageSlug' => 'cavka'],
        'norwhe' => ['srName' => 'Обична белогуза', 'comName' => 'Northern Wheatear', 'sciName' => 'Oenanthe oenanthe', 'imageSlug' => 'oenanthe-oenanthe'],
        'eurspa1' => ['srName' => 'Кобац', 'comName' => 'Eurasian Sparrowhawk', 'sciName' => 'Accipiter nisus', 'imageSlug' => 'accipiter-nisus'],
        'eurhob' => ['srName' => 'Соко ластавичар', 'comName' => 'Eurasian Hobby', 'sciName' => 'Falco subbuteo', 'imageSlug' => 'falco-subbuteo'],
        'reffal1' => ['srName' => 'Вечерња ветрушка', 'comName' => 'Red-footed Falcon', 'sciName' => 'Falco vespertinus', 'imageSlug' => 'falco-vespertinus'],
        'eurhoo' => ['srName' => 'Пупавац', 'comName' => 'Eurasian Hoopoe', 'sciName' => 'Upupa epops', 'imageSlug' => 'upupa-epops'],
        'eubeat1' => ['srName' => 'Пчеларица', 'comName' => 'European Bee-eater', 'sciName' => 'Merops apiaster', 'imageSlug' => 'merops-apiaster'],
        'eutdov' => ['srName' => 'Грлица', 'comName' => 'European Turtle-Dove', 'sciName' => 'Streptopelia turtur', 'imageSlug' => 'streptopelia-turtur'],
        'cohmar1' => ['srName' => 'Градска ласта', 'comName' => 'Common House-Martin', 'sciName' => 'Delichon urbicum', 'imageSlug' => 'delichon-urbicum'],
        'nohmrt1' => ['srName' => 'Градска ласта', 'comName' => 'Northern House-Martin', 'sciName' => 'Delichon urbicum', 'imageSlug' => 'delichon-urbicum'],
        'lottit1' => ['srName' => 'Дугорепа сеница', 'comName' => 'Long-tailed Tit', 'sciName' => 'Aegithalos caudatus', 'imageSlug' => 'aegithalos-caudatus'],
        'wilwar' => ['srName' => 'Жути вољић', 'comName' => 'Willow Warbler', 'sciName' => 'Phylloscopus trochilus', 'imageSlug' => 'phylloscopus-trochilus'],
        'rebfly1' => ['srName' => 'Мала мухарица', 'comName' => 'Red-breasted Flycatcher', 'sciName' => 'Ficedula parva', 'imageSlug' => 'ficedula-parva'],
        'comwhi1' => ['srName' => 'Обична грмуша', 'comName' => 'Common Whitethroat', 'sciName' => 'Sylvia communis', 'imageSlug' => 'sylvia-communis'],
        'comqua' => ['srName' => 'Препелица', 'comName' => 'Common Quail', 'sciName' => 'Coturnix coturnix', 'imageSlug' => 'coturnix-coturnix'],
        'rinphe' => ['srName' => 'Фазан', 'comName' => 'Common Pheasant', 'sciName' => 'Phasianus colchicus', 'imageSlug' => 'phasianus-colchicus'],
        'comphe' => ['srName' => 'Фазан', 'comName' => 'Common Pheasant', 'sciName' => 'Phasianus colchicus', 'imageSlug' => 'phasianus-colchicus'],
        'eurrol1' => ['srName' => 'Модроврана', 'comName' => 'European Roller', 'sciName' => 'Coracias garrulus', 'imageSlug' => 'coracias-garrulus'],
        'blawoo1' => ['srName' => 'Црна жуна', 'comName' => 'Black Woodpecker', 'sciName' => 'Dryocopus martius', 'imageSlug' => 'dryocopus-martius'],
        'grhwoo1' => ['srName' => 'Сива жуна', 'comName' => 'Gray-headed Woodpecker', 'sciName' => 'Picus canus', 'imageSlug' => 'picus-canus'],
        'grywag' => ['srName' => 'Горска плиска', 'comName' => 'Gray Wagtail', 'sciName' => 'Motacilla cinerea', 'imageSlug' => 'motacilla-cinerea'],
        'treepi' => ['srName' => 'Шумска трептаљка', 'comName' => 'Tree Pipit', 'sciName' => 'Anthus trivialis', 'imageSlug' => 'anthus-trivialis'],
        'comchi1' => ['srName' => 'Обични звиждак', 'comName' => 'Common Chiffchaff', 'sciName' => 'Phylloscopus collybita', 'imageSlug' => 'phylloscopus-collybita'],
        'spofly1' => ['srName' => 'Сива мухарица', 'comName' => 'Spotted Flycatcher', 'sciName' => 'Muscicapa striata', 'imageSlug' => 'muscicapa-striata'],
        'eupfly1' => ['srName' => 'Црноглава мухарица', 'comName' => 'European Pied Flycatcher', 'sciName' => 'Ficedula hypoleuca', 'imageSlug' => 'ficedula-hypoleuca'],
        'euston1' => ['srName' => 'Црноглава траварка', 'comName' => 'European Stonechat', 'sciName' => 'Saxicola rubicola', 'imageSlug' => 'saxicola-rubicola'],
        'eutcha1' => ['srName' => 'Црноглава траварка', 'comName' => 'European Stonechat', 'sciName' => 'Saxicola rubicola', 'imageSlug' => 'saxicola-rubicola'],
        'dunnoc1' => ['srName' => 'Попић', 'comName' => 'Dunnock', 'sciName' => 'Prunella modularis', 'imageSlug' => 'prunella-modularis'],
        'goldcr1' => ['srName' => 'Жутоглави краљић', 'comName' => 'Goldcrest', 'sciName' => 'Regulus regulus', 'imageSlug' => 'regulus-regulus'],
        'shttrc1' => ['srName' => 'Краткопрсти пузић', 'comName' => 'Short-toed Treecreeper', 'sciName' => 'Certhia brachydactyla', 'imageSlug' => 'certhia-brachydactyla'],
        'eurnew1' => ['srName' => 'Вијоглава', 'comName' => 'Eurasian Wryneck', 'sciName' => 'Jynx torquilla', 'imageSlug' => 'jynx-torquilla'],
        'eurwry' => ['srName' => 'Вијоглава', 'comName' => 'Eurasian Wryneck', 'sciName' => 'Jynx torquilla', 'imageSlug' => 'jynx-torquilla'],
        'eurkes' => ['srName' => 'Ветрушка', 'comName' => 'Eurasian Kestrel', 'sciName' => 'Falco tinnunculus', 'imageSlug' => ''],
    ];
    $code = strtolower((string) preg_replace('/[^a-zA-Z0-9_-]/', '', $speciesCode));
    return $details[$code] ?? ['srName' => '', 'comName' => '', 'sciName' => '', 'imageSlug' => ''];
}

function local_species_name(string $speciesCode, string $englishName): string
{
    $details = local_species_details($speciesCode);
    if ($details['srName'] !== '') {
        return $details['srName'];
    }
    static $names = [
        'eurwre' => 'Царић',
        'eurbla' => 'Обични кос',
        'eurrob1' => 'Црвендаћ',
        'comcha' => 'Зеба',
        'gretit1' => 'Велика сеница',
        'blutit' => 'Плава сеница',
        'eurnut2' => 'Бргљез',
        'eurjay1' => 'Сојка',
        'combuz1' => 'Мишар',
        'grswoo' => 'Велики детлић',
        'grtspo1' => 'Велики детлић',
        'midspo1' => 'Средњи детлић',
        'lessp1' => 'Мали детлић',
        'eurmag1' => 'Сврака',
        'carrio1' => 'Гавран',
        'hoocro1' => 'Сива врана',
        'barswa' => 'Сеоска ласта',
        'comswi' => 'Чиопа',
        'grywar1' => 'Црноглава грмуша',
        'eurbla2' => 'Црноглава грмуша',
        'comnig1' => 'Славуј',
        'songth1' => 'Дрозд певач',
        'misthr1' => 'Дрозд имелаш',
        'eurser1' => 'Жутарица',
        'eurgre1' => 'Зелениш',
        'golori1' => 'Вуга',
        'woowar' => 'Шумски звиждак',
        'comchi1' => 'Чижак',
        'hou spa' => 'Врабац покућар',
        'houspa' => 'Врабац покућар',
        'linspa1' => 'Врабац покућар',
        'eursta' => 'Чворак',
        'whisto1' => 'Бела плиска',
        'yewagt1' => 'Жута плиска',
        'woodpi2' => 'Голуб дупљаш',
        'commoo3' => 'Голуб гривнаш',
        'eucdov' => 'Гугутка',
        'eurcoo' => 'Кукавица',
        'eurgol' => 'Штиглић',
        'eurbul' => 'Обични зелентар',
        'hawfin' => 'Батокљун',
        'commyn' => 'Обична чавка',
        'rocpet' => 'Горска стрнадица',
        'comred' => 'Обична црвенрепка',
        'blared1' => 'Црна црвенрепка',
        'eutspa' => 'Пољски врабац',
        'whtthr1' => 'Белогрли дрозд',
        'grwpwo1' => 'Зелена жуна',
        'comwoo1' => 'Шумска шљука',
        'eurlin1' => 'Конопљарка',
        'yelham1' => 'Стрнадица жутовољка',
        'cornbu1' => 'Стрнадица жутовољка',
        'ortbun1' => 'Вртна стрнадица'
    ];
    return $names[$speciesCode] ?? $englishName;
}

function image_slug_from_scientific_name(string $sciName): string
{
    $slug = strtolower(trim($sciName));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim((string) $slug, '-');
}

function image_for_species(string $speciesCode, string $sciName = ''): string
{
    static $codeToAssetSlug = [
        'eurwre' => 'troglodytes-troglodytes',
        'eurbla' => 'turdus-merula',
        'eurrob1' => 'erithacus-rubecula',
        'comcha' => 'fringilla-coelebs',
        'gretit1' => 'velika-senica',
        'blutit' => 'plava-senica',
        'eurnut2' => 'brgljez',
        'eurjay1' => 'garrulus-glandarius',
        'combuz1' => 'buteo-buteo',
        'grswoo' => 'veliki-detlic',
        'grtspo1' => 'veliki-detlic',
        'lessp1' => 'mali-detlic',
        'eurmag1' => 'pica-pica',
        'carrio1' => 'gavran',
        'hoocro1' => 'siva-vrana',
        'barswa' => 'hirundo-rustica',
        'grywar1' => 'sylvia-atricapilla',
        'eurbla2' => 'sylvia-atricapilla',
        'songth1' => 'turdus-philomelos',
        'misthr1' => 'turdus-viscivorus',
        'eurser1' => 'serinus-serinus',
        'eurgre1' => 'chloris-chloris',
        'golori1' => 'oriolus-oriolus',
        'houspa' => 'vrabac',
        'linspa1' => 'vrabac',
        'eursta' => 'sturnus-vulgaris',
        'whisto1' => 'motacilla-alba',
        'eucdov' => 'streptopelia-decaocto',
        'eurcoo' => 'kukavica',
        'eurgol' => 'carduelis-carduelis',
        'hawfin' => 'coccothraustes-coccothraustes',
        'blared1' => 'phoenicurus-ochruros',
        'comred' => 'phoenicurus-phoenicurus',
        'grwpwo1' => 'picus-viridis',
        'yelham1' => 'emberiza-citrinella',
        'cornbu1' => 'emberiza-calandra',
        'corbun1' => 'emberiza-calandra',
        'ortbun1' => 'emberiza-hortulana',
        'comrav' => 'gavran',
        'woolar1' => 'lullula-arborea',
        'skylar' => 'alauda-arvensis',
        'rebshr1' => 'lanius-collurio',
        'whiwag' => 'motacilla-alba',
    ];

    $code = strtolower((string) preg_replace('/[^a-zA-Z0-9_-]/', '', $speciesCode));
    $slugs = [];
    if ($code !== '') {
        $slugs[] = $code;
        $details = local_species_details($code);
        if ($details['imageSlug'] !== '') {
            $slugs[] = $details['imageSlug'];
        }
        if (isset($codeToAssetSlug[$code])) {
            $slugs[] = strtolower($codeToAssetSlug[$code]);
        }
    }
    $latinSlug = image_slug_from_scientific_name($sciName);
    if ($latinSlug !== '') {
        $slugs[] = $latinSlug;
    }
    $slugs = array_values(array_unique(array_filter($slugs)));
    if (!$slugs) {
        return '';
    }

    $candidates = [
    ];
    foreach ($slugs as $slug) {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            $candidates[] = 'assets/birds/' . $slug . '.' . $ext;
            $candidates[] = 'assets/ptice2/' . $slug . '.' . $ext;
            $candidates[] = 'assets/ptice2/' . $slug . '-1.' . $ext;
            $candidates[] = 'assets/ptice2/' . $slug . '-2.' . $ext;
            $candidates[] = 'assets/ptice2/' . $slug . '-3.' . $ext;
        }
    }
    foreach ($candidates as $rel) {
        if (is_file(__DIR__ . '/' . $rel)) {
            return $rel;
        }
    }
    return '';
}

function normalize_checklist(array $detail, string $group = 'archive'): array
{
    $obs = $detail['obs'] ?? [];
    if (!is_array($obs)) {
        $obs = [];
    }
    $subId = (string) ($detail['subId'] ?? $detail['subID'] ?? '');
    $checklistUrl = 'https://ebird.org/checklist/' . rawurlencode($subId);
    $species = [];
    foreach ($obs as $row) {
        if (!is_array($row)) {
            continue;
        }
        $speciesCode = (string) ($row['speciesCode'] ?? '');
        $comName = (string) ($row['comName'] ?? '');
        $sciName = (string) ($row['sciName'] ?? '');
        $localDetails = local_species_details($speciesCode);
        if ($comName === '') {
            $comName = $localDetails['comName'];
        }
        if ($sciName === '') {
            $sciName = $localDetails['sciName'];
        }
        $howManyStr = (string) ($row['howManyStr'] ?? ($row['howMany'] ?? 'X'));
        $species[] = [
            'speciesCode' => $speciesCode,
            'srName' => local_species_name($speciesCode, $comName),
            'comName' => $comName,
            'sciName' => $sciName,
            'count' => $howManyStr !== '' ? $howManyStr : 'X',
            'comments' => (string) ($row['obsComments'] ?? ''),
            'imageUrl' => image_for_species($speciesCode, $sciName),
            'sourceChecklistUrl' => $checklistUrl,
            'sourceChecklistId' => $subId,
            'mediaCounts' => $row['mediaCounts'] ?? null,
        ];
    }
    return [
        'group' => $group,
        'subId' => $subId,
        'url' => $checklistUrl,
        'locId' => (string) ($detail['locId'] ?? ''),
        'locName' => (string) ($detail['locName'] ?? ''),
        'userDisplayName' => (string) ($detail['userDisplayName'] ?? ''),
        'obsDt' => (string) ($detail['obsDt'] ?? ''),
        'obsTime' => (string) ($detail['obsTime'] ?? ''),
        'numSpecies' => isset($detail['numSpecies']) ? (int) $detail['numSpecies'] : count($species),
        'numObservers' => isset($detail['numObservers']) ? (int) $detail['numObservers'] : null,
        'durationMinutes' => isset($detail['durationHrs']) ? round(((float) $detail['durationHrs']) * 60) : null,
        'distanceKm' => isset($detail['effortDistanceKm']) ? round((float) $detail['effortDistanceKm'], 2) : null,
        'protocolName' => (string) ($detail['protocolName'] ?? $detail['protocolId'] ?? ''),
        'allObsReported' => isset($detail['allObsReported']) ? (bool) $detail['allObsReported'] : null,
        'comments' => (string) ($detail['subComments'] ?? ''),
        'species' => $species,
    ];
}

function fallback_checklist_s330(): array
{
    return [
        'group' => 'pinned',
        'subId' => 'S330345122',
        'url' => 'https://ebird.org/checklist/S330345122',
        'locId' => '',
        'locName' => 'Горњи Бранетићи, шира зона Рајца',
        'userDisplayName' => 'Зоран Танасијевић',
        'obsDt' => '2026-05-01',
        'obsTime' => '14:45',
        'numSpecies' => 4,
        'numObservers' => 1,
        'durationMinutes' => 30,
        'distanceKm' => 4.5,
        'protocolName' => 'Traveling',
        'allObsReported' => true,
        'comments' => '',
        'species' => [
            ['speciesCode' => 'eurwre', 'srName' => 'Царић', 'comName' => 'Eurasian Wren', 'sciName' => 'Troglodytes troglodytes', 'count' => 'X', 'comments' => '', 'imageUrl' => image_for_species('eurwre', 'Troglodytes troglodytes'), 'sourceChecklistUrl' => 'https://ebird.org/checklist/S330345122', 'sourceChecklistId' => 'S330345122'],
            ['speciesCode' => 'eurbla', 'srName' => 'Обични кос', 'comName' => 'Common Blackbird', 'sciName' => 'Turdus merula', 'count' => 'X', 'comments' => '', 'imageUrl' => image_for_species('eurbla', 'Turdus merula'), 'sourceChecklistUrl' => 'https://ebird.org/checklist/S330345122', 'sourceChecklistId' => 'S330345122'],
            ['speciesCode' => 'eurrob1', 'srName' => 'Црвендаћ', 'comName' => 'European Robin', 'sciName' => 'Erithacus rubecula', 'count' => 'X', 'comments' => '', 'imageUrl' => image_for_species('eurrob1', 'Erithacus rubecula'), 'sourceChecklistUrl' => 'https://ebird.org/checklist/S330345122', 'sourceChecklistId' => 'S330345122'],
            ['speciesCode' => 'comcha', 'srName' => 'Зеба', 'comName' => 'Common Chaffinch', 'sciName' => 'Fringilla coelebs', 'count' => '1', 'comments' => '', 'imageUrl' => image_for_species('comcha', 'Fringilla coelebs'), 'sourceChecklistUrl' => 'https://ebird.org/checklist/S330345122', 'sourceChecklistId' => 'S330345122']
        ]
    ];
}

function registry_from_checklists(array $checklists): array
{
    $registry = [];
    foreach ($checklists as $c) {
        foreach (($c['species'] ?? []) as $sp) {
            $key = (string) ($sp['speciesCode'] ?? $sp['sciName'] ?? $sp['comName'] ?? '');
            if ($key === '') {
                continue;
            }
            if (!isset($registry[$key])) {
                $registry[$key] = [
                    'speciesCode' => $sp['speciesCode'] ?? '',
                    'srName' => $sp['srName'] ?? $sp['comName'] ?? '',
                    'comName' => $sp['comName'] ?? '',
                    'sciName' => $sp['sciName'] ?? '',
                    'imageUrl' => $sp['imageUrl'] ?? '',
                    'checklists' => [],
                    'latestObsDt' => '',
                    'records' => 0,
                    'sourceChecklistUrl' => (string) ($c['url'] ?? ''),
                    'sourceChecklistId' => (string) ($c['subId'] ?? ''),
                ];
            }
            $registry[$key]['records']++;
            $sid = (string) ($c['subId'] ?? '');
            if ($sid !== '' && !in_array($sid, $registry[$key]['checklists'], true)) {
                $registry[$key]['checklists'][] = $sid;
            }
            $dt = (string) ($c['obsDt'] ?? '');
            if ($dt !== '' && ($registry[$key]['latestObsDt'] === '' || strcmp($dt, $registry[$key]['latestObsDt']) > 0)) {
                $registry[$key]['latestObsDt'] = $dt;
                $registry[$key]['sourceChecklistUrl'] = (string) ($c['url'] ?? '');
                $registry[$key]['sourceChecklistId'] = (string) ($c['subId'] ?? '');
            }
        }
    }
    usort($registry, static fn($a, $b) => strcmp($a['srName'], $b['srName']));
    return array_values($registry);
}

$errors = [];
$sourceMode = [];
$manual = read_manual_ids();
$pinnedIds = $manual['pinned'];
$extraIds = $manual['extra'];
$recentIds = [];

if ($apiKey !== '') {
    $recentUrl = $EBIRD_BASE . '/product/lists/' . rawurlencode($HOTSPOT_ID);
    $recent = http_json($recentUrl, $apiKey, ['maxResults' => $maxRequest]);
    if ($recent['ok'] && is_array($recent['data'])) {
        $recentIds = unique_subids($recent['data']);
        if ($recentIds) {
            $sourceMode[] = 'ebird-api-recent-checklists';
        }
    } else {
        $errors[] = 'eBird recent-checklists API: ' . ($recent['error'] ?? 'unknown error');
    }
}

if (!$recentIds) {
    $scraped = scrape_recent_page_subids($HOTSPOT_URL);
    if ($scraped) {
        $recentIds = $scraped;
        $sourceMode[] = 'ebird-public-page';
    }
}

if (!$recentIds && $apiKey !== '') {
    $obsUrl = $EBIRD_BASE . '/data/obs/' . rawurlencode($HOTSPOT_ID) . '/recent';
    $obs = http_json($obsUrl, $apiKey, ['back' => 30, 'includeProvisional' => 'true', 'sort' => 'date', 'detail' => 'full']);
    if ($obs['ok'] && is_array($obs['data'])) {
        $recentIds = unique_subids($obs['data']);
        if ($recentIds) {
            $sourceMode[] = 'ebird-api-recent-observations';
        }
    } else {
        $errors[] = 'eBird recent-observations API: ' . ($obs['error'] ?? 'unknown error');
    }
}

$recentIds = array_slice(unique_subids($recentIds), 0, $maxRequest);
$archiveIds = unique_subids(array_merge($recentIds, $extraIds));
$allIdsForDetail = unique_subids(array_merge($archiveIds, $pinnedIds));
$detailsById = [];

if ($apiKey !== '' && $allIdsForDetail) {
    foreach ($allIdsForDetail as $subId) {
        $detailUrl = $EBIRD_BASE . '/product/checklist/view/' . rawurlencode($subId);
        $detail = http_json($detailUrl, $apiKey);
        if ($detail['ok'] && is_array($detail['data'])) {
            $group = in_array($subId, $pinnedIds, true) && !in_array($subId, $archiveIds, true) ? 'pinned' : 'archive';
            $detailsById[$subId] = normalize_checklist($detail['data'], $group);
        } else {
            $errors[] = 'Checklist ' . $subId . ': ' . ($detail['error'] ?? 'unknown error');
        }
        usleep(120000);
    }
}

if (!isset($detailsById['S330345122']) && in_array('S330345122', $pinnedIds, true)) {
    $detailsById['S330345122'] = fallback_checklist_s330();
}

$archiveChecklists = [];
foreach ($archiveIds as $id) {
    if (isset($detailsById[$id])) {
        $archiveChecklists[] = $detailsById[$id];
    } else {
        $archiveChecklists[] = [
            'group' => 'archive',
            'subId' => $id,
            'url' => 'https://ebird.org/checklist/' . rawurlencode($id),
            'locId' => $HOTSPOT_ID,
            'locName' => $HOTSPOT_NAME,
            'userDisplayName' => '',
            'obsDt' => '',
            'obsTime' => '',
            'numSpecies' => null,
            'numObservers' => null,
            'durationMinutes' => null,
            'distanceKm' => null,
            'protocolName' => '',
            'allObsReported' => null,
            'comments' => '',
            'species' => [],
        ];
    }
}

$pinnedChecklists = [];
foreach ($pinnedIds as $id) {
    if (isset($detailsById[$id])) {
        $item = $detailsById[$id];
        $item['group'] = 'pinned';
        $pinnedChecklists[] = $item;
    }
}

$latestChecklists = array_slice($archiveChecklists, 0, $MAX_LATEST);
$allChecklistsForRegistry = [];
foreach (array_merge($archiveChecklists, $pinnedChecklists) as $item) {
    $sid = (string) ($item['subId'] ?? '');
    if ($sid !== '' && !isset($seen[$sid])) {
        $seen[$sid] = true;
        $allChecklistsForRegistry[] = $item;
    }
}
$speciesRegistry = registry_from_checklists($allChecklistsForRegistry);

if (!$archiveChecklists) {
    $errors[] = $apiKey === '' ? 'Nije postavljen eBird API ključ.' : 'Nisu pronađene javne eBird čekliste za hotspot L7416435.';
}

$payload = [
    'ok' => count($archiveChecklists) > 0 || count($pinnedChecklists) > 0,
    'generatedAt' => gmdate('c'),
    'hotspot' => [
        'locId' => $HOTSPOT_ID,
        'name' => $HOTSPOT_NAME,
        'recentChecklistsUrl' => $HOTSPOT_URL,
    ],
    'apiKeyConfigured' => $apiKey !== '',
    'sourceMode' => $sourceMode,
    'latestChecklists' => $latestChecklists,
    'archiveChecklists' => $archiveChecklists,
    'pinnedChecklists' => $pinnedChecklists,
    'speciesRegistry' => $speciesRegistry,
    'errors' => $errors,
];

finish_json($payload, $CACHE_FILE, $payload['ok']);

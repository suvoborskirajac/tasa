#!/usr/bin/env php
<?php
/**
 * download-audio.php — ПИО Рајац птице плагин
 *
 * Преузима 34 аудио снимка са Wikimedia Commons на сервер
 * и ажурира путање у свим HTML страницама.
 *
 * УПОТРЕБА:
 *   1. Отпакуј ptice-komplet-final.zip у wp-content/plugins/
 *   2. Копирај овај фајл у исту фасциклу (ptice/)
 *   3. Покрени: php download-audio.php
 *      или преко cPanel Terminal: php /путања/ptice/download-audio.php
 *
 * АЛТЕРНАТИВА (преко браузера):
 *   Постави на сервер и отвори:
 *   https://piorajac.rs/wp-content/plugins/ptice/download-audio.php
 *   (Обриши фајл после извршавања!)
 */

// Повећај лимит времена за преузимање
set_time_limit(300);
ini_set('max_execution_time', 300);

$PLUGIN_DIR = __DIR__;
$AUDIO_DIR  = $PLUGIN_DIR . '/assets/audio';
$VIEWS_DIR  = $PLUGIN_DIR . '/views';

// HTTP хедери за Wikimedia
$context = stream_context_create([
    'http' => [
        'method'     => 'GET',
        'user_agent' => 'Mozilla/5.0 (compatible; PIOrajac-AudioDownloader/1.0; +https://piorajac.rs)',
        'timeout'    => 30,
        'follow_location' => true,
        'max_redirects'   => 5,
    ],
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ],
]);

// ============================================================
// Листа аудио снимака: [slug => [url, ekstenzija]]
// ============================================================
$AUDIO_FILES = [
    'aegithalos-caudatus'         => ['https://upload.wikimedia.org/wikipedia/commons/6/6e/Aegithalos_caudatus_-_Long-tailed_Tit_XC512590.mp3', 'mp3'],
    'alauda-arvensis'             => ['https://upload.wikimedia.org/wikipedia/commons/8/8c/Mystery_mystery_-_Identity_unknown_XC571979.mp3', 'mp3'],
    'anthus-trivialis'            => ['https://upload.wikimedia.org/wikipedia/commons/0/04/Anthus_trivialis_-_Tree_Pipit_XC588228.mp3', 'mp3'],
    'carduelis-carduelis'         => ['https://upload.wikimedia.org/wikipedia/commons/f/f4/Carduelis_carduelis_-_European_Goldfinch_XC101463.mp3', 'mp3'],
    'chloris-chloris'             => ['https://upload.wikimedia.org/wikipedia/commons/2/26/Gr%C3%BCnfink_Chloris_chloris_0066a.mp3', 'mp3'],
    'coccothraustes-coccothraustes' => ['https://upload.wikimedia.org/wikipedia/commons/a/a1/Coccothraustes_coccothraustes_-_Hawfinch_XC541308.mp3', 'mp3'],
    'coracias-garrulus'           => ['https://upload.wikimedia.org/wikipedia/commons/1/1c/De-Racke.ogg', 'ogg'],
    'coturnix-coturnix'           => ['https://upload.wikimedia.org/wikipedia/commons/4/4c/Coturnix_coturnix_-_Common_Quail_XC549215.mp3', 'mp3'],
    'delichon-urbicum'            => ['https://upload.wikimedia.org/wikipedia/commons/5/53/Delichon_urbicum_contact_call.ogg', 'ogg'],
    'emberiza-calandra'           => ['https://upload.wikimedia.org/wikipedia/commons/b/b2/De-Grauammer.ogg', 'ogg'],
    'emberiza-hortulana'          => ['https://upload.wikimedia.org/wikipedia/commons/5/58/Emberiza_hortulana_-_Ortolan_Bunting_XC481324.mp3', 'mp3'],
    'erithacus-rubecula'          => ['https://upload.wikimedia.org/wikipedia/commons/f/f4/Erithacus_rubecula_-_European_Robin_XC124862.ogg', 'ogg'],
    'ficedula-hypoleuca'          => ['https://commons.wikimedia.org/wiki/Special:Redirect/file/Ficedula%20hypoleuca.ogg', 'ogg'],
    'ficedula-parva'              => ['https://upload.wikimedia.org/wikipedia/commons/9/96/Ficedula_parva_-_Red-breasted_Flycatcher_XC249313.mp3', 'mp3'],
    'hirundo-rustica'             => ['https://upload.wikimedia.org/wikipedia/commons/7/77/Hirundo_rustica_-_Barn_Swallow_XC83449.mp3', 'mp3'],
    'lullula-arborea'             => ['https://upload.wikimedia.org/wikipedia/commons/d/da/De-Heidelerche.ogg', 'ogg'],
    'merops-apiaster'             => ['https://upload.wikimedia.org/wikipedia/commons/3/37/Bijeneter_-_SoundCloud_-_Beeld_en_Geluid.ogg', 'ogg'],
    'motacilla-cinerea'           => ['https://upload.wikimedia.org/wikipedia/commons/d/dd/Motacilla_cinerea_-_Grey_Wagtail_XC596811.mp3', 'mp3'],
    'muscicapa-striata'           => ['https://upload.wikimedia.org/wikipedia/commons/7/72/Muscicapa_striata_-_Spotted_Flycatcher_XC342793.mp3', 'mp3'],
    'obicna-travarka'             => ['https://upload.wikimedia.org/wikipedia/commons/6/6e/Saxicola_rubetra_-_Whinchat_XC479662.mp3', 'mp3'],
    'oenanthe-oenanthe'           => ['https://upload.wikimedia.org/wikipedia/commons/4/4b/De-Steinschm%C3%A4tzer.ogg', 'ogg'],
    'phasianus-colchicus'         => ['https://upload.wikimedia.org/wikipedia/commons/0/0e/Phasianus_colchicus_-_Common_Pheasant_XC83152.mp3', 'mp3'],
    'phoenicurus-ochruros'        => ['https://commons.wikimedia.org/wiki/Special:Redirect/file/Black%20Redstart%20(Phoenicurus%20ochruros)%20(W1CDR0000253%20BD17).ogg', 'ogg'],
    'phoenicurus-phoenicurus'     => ['https://upload.wikimedia.org/wikipedia/commons/7/7c/Phoenicurus_phoenicurus_-_Common_Redstart_XC468918.mp3', 'mp3'],
    'phylloscopus-collybita'      => ['https://upload.wikimedia.org/wikipedia/commons/e/e2/Phylloscopus_collybita_-_Common_Chiffchaff_XC501590.mp3', 'mp3'],
    'phylloscopus-trochilus'      => ['https://upload.wikimedia.org/wikipedia/commons/7/7a/Phylloscopus_trochilus_-_Willow_Warbler_XC468919.mp3', 'mp3'],
    'saxicola-rubicola'           => ['https://commons.wikimedia.org/wiki/Special:Redirect/file/European%20Stonechat%20(Saxicola%20rubicola)%20(W1CDR0001536%20BD16).oga', 'ogg'],
    'serinus-serinus'             => ['https://upload.wikimedia.org/wikipedia/commons/6/68/Serinus_serinus_-_European_Serin_XC471334.mp3', 'mp3'],
    'sylvia-atricapilla'          => ['https://upload.wikimedia.org/wikipedia/commons/7/7a/Sylvia_atricapilla_-_Eurasian_Blackcap_XC543236.mp3', 'mp3'],
    'sylvia-communis'             => ['https://upload.wikimedia.org/wikipedia/commons/9/91/Sylvia_communis_-_Common_Whitethroat_XC503635.mp3', 'mp3'],
    'turdus-merula'               => ['https://upload.wikimedia.org/wikipedia/commons/2/22/Turdus_merula_-_Common_Blackbird_XC123548.ogg', 'ogg'],
    'turdus-viscivorus'           => ['https://upload.wikimedia.org/wikipedia/commons/d/d4/Turdus_viscivorus_-_Mistle_Thrush_XC108362.mp3', 'mp3'],
    'upupa-epops'                 => ['https://upload.wikimedia.org/wikipedia/commons/a/a1/De-Hupatz.ogg', 'ogg'],
    'vrabac'                      => ['https://upload.wikimedia.org/wikipedia/commons/d/dc/Passer_domesticus_-_House_Sparrow_-_XC86749.ogg', 'ogg'],
];

$ok   = 0;
$fail = 0;
$skip = 0;
$failed_slugs = [];

// Провери да ли assets/audio постоји
if (!is_dir($AUDIO_DIR)) {
    echo "ГРЕШКА: Фасцикла $AUDIO_DIR не постоји.\n";
    exit(1);
}

echo "============================================\n";
echo " ПИО Рајац — преузимање аудио снимака\n";
echo " Дестинација: $AUDIO_DIR\n";
echo " Фајлова за преузимање: " . count($AUDIO_FILES) . "\n";
echo "============================================\n\n";

// ============================================================
// 1. ПРЕУЗИМАЊЕ
// ============================================================
foreach ($AUDIO_FILES as $slug => [$url, $ext]) {
    $dest = "$AUDIO_DIR/$slug.$ext";

    // Прескочи ако већ постоји и није празан
    if (file_exists($dest) && filesize($dest) > 1000) {
        $size = round(filesize($dest) / 1024, 1);
        echo "  ПОСТОЈИ: $slug.$ext ({$size}KB) — прескачем\n";
        $skip++;
        continue;
    }

    echo "  Преузимам: $slug.$ext ...\n";

    $data = @file_get_contents($url, false, $context);

    if ($data !== false && strlen($data) > 1000) {
        file_put_contents($dest, $data);
        $size = round(strlen($data) / 1024, 1);
        echo "    ✓ OK ({$size}KB)\n";
        $ok++;
    } else {
        // Покушај са cURL ако је доступан
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; PIOrajac/1.0)',
            ]);
            $data = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($data !== false && strlen($data) > 1000 && $http_code === 200) {
                file_put_contents($dest, $data);
                $size = round(strlen($data) / 1024, 1);
                echo "    ✓ OK via cURL ({$size}KB)\n";
                $ok++;
                continue;
            }
        }

        echo "    ✗ ГРЕШКА — остаје Wikimedia URL\n";
        $fail++;
        $failed_slugs[] = $slug;
    }
}

echo "\n";
echo "============================================\n";
echo " Преузимање: $ok нових, $skip постојећих, $fail грешака\n";
echo "============================================\n\n";

if ($fail > 0) {
    echo "Неуспешни фајлови (остају на Wikimedia):\n";
    foreach ($failed_slugs as $s) echo "  - $s\n";
    echo "\n";
}

// ============================================================
// 2. АЖУРИРАЊЕ HTML ПУТАЊА
// ============================================================
echo "Ажурирање HTML путања...\n\n";

$skip_files = ['index.html','archive.html','ptice.html',
               'fotografisanje-ptica.html','kucice-za-ptice.html','merlin-ptice-rajac.html'];

$html_updated = 0;

foreach (glob("$VIEWS_DIR/*.html") as $html_file) {
    $fname = basename($html_file);
    if (in_array($fname, $skip_files)) continue;

    $slug = str_replace('.html', '', $fname);
    $content = file_get_contents($html_file);

    // Провери да ли постоји екстерни аудио
    if (strpos($content, 'wikimedia.org') === false) continue;

    // Одреди локалну путању
    if (file_exists("$AUDIO_DIR/$slug.mp3") && filesize("$AUDIO_DIR/$slug.mp3") > 1000) {
        $local_src  = "audio/$slug.mp3";
        $local_type = 'audio/mpeg';
    } elseif (file_exists("$AUDIO_DIR/$slug.ogg") && filesize("$AUDIO_DIR/$slug.ogg") > 1000) {
        $local_src  = "audio/$slug.ogg";
        $local_type = 'audio/ogg';
    } else {
        echo "  ПРЕСКАЧЕМ (аудио није преузет): $slug\n";
        continue;
    }

    // Замени URL у <source src="..."> тагу
    $new_content = preg_replace(
        '/<source\s+src="https?:\/\/[^"]*wikimedia[^"]*"\s+type="[^"]*">/',
        "<source src=\"$local_src\" type=\"$local_type\">",
        $content
    );

    if ($new_content !== $content) {
        file_put_contents($html_file, $new_content);
        echo "  ✓ $slug → $local_src\n";
        $html_updated++;
    }
}

// Укупан број аудио фајлова
$total_audio = count(glob("$AUDIO_DIR/*.mp3")) + count(glob("$AUDIO_DIR/*.ogg"));

echo "\n";
echo "============================================\n";
echo " HTML страница ажурирано: $html_updated\n";
echo " Аудио фајлова на серверу: $total_audio\n";
echo "============================================\n";
echo "\n ГОТОВО! Обриши овај фајл са сервера.\n\n";

<?php
/**
 * Plugin Name: Птице Рајац
 * Description: Комплетан plugin за секцију птица. Прва страна и нове странице врста служе се директно из plugin-а.
 * Version: 53.0.5
 * Author: ПИО Рајац
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PTICE45_DIR', plugin_dir_path(__FILE__));
define('PTICE45_URL', plugin_dir_url(__FILE__));
define('PTICE45_VERSION', '53.0.5');

add_action('init', 'ptice45_register_cpt');
function ptice45_register_cpt() {
    register_post_type('ptice', array(
        'labels' => array(
            'name' => 'Птице',
            'singular_name' => 'Птица',
            'menu_name' => 'Птице',
            'all_items' => 'Све птице',
            'add_new_item' => 'Додај птицу',
            'edit_item' => 'Уреди птицу',
            'view_item' => 'Погледај птицу',
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-palmtree',
        'query_var' => true,
        'rewrite' => array('slug' => 'ptice', 'with_front' => false),
        'has_archive' => 'ptice',
        'hierarchical' => false,
        'supports' => array('title', 'editor', 'thumbnail'),
        'show_in_rest' => true,
    ));
}

add_action('template_redirect', 'ptice45_template_redirect', 0);
function ptice45_template_redirect() {
    $path = ptice45_current_path();

    if ($path === 'ptice' || is_post_type_archive('ptice')) {
        ptice45_send_view('archive');
    }

    $guide_map = array(
        'ptice/kucice-za-ptice' => 'kucice-za-ptice',
        'kucice-za-ptice' => 'kucice-za-ptice',
        'ptice/fotografisanje-ptica' => 'fotografisanje-ptica',
        'fotografisanje-ptica' => 'fotografisanje-ptica',
        'ptice/uputstvo-fotografisanje-ptica-rajac-prvi-dizajn-dopune' => 'fotografisanje-ptica',
        'uputstvo-fotografisanje-ptica-rajac-prvi-dizajn-dopune' => 'fotografisanje-ptica',
        'ptice/ponasanje-prema-pticama' => 'ponasanje-prema-pticama',
        'ponasanje-prema-pticama' => 'ponasanje-prema-pticama',
        'ptice/uputstvo-ponasanje-prema-pticama-rajac-ptice-style-korigovano' => 'ponasanje-prema-pticama',
        'uputstvo-ponasanje-prema-pticama-rajac-ptice-style-korigovano' => 'ponasanje-prema-pticama',
        'ptice/kviz-prepoznavanje-ptica-rajac' => 'kviz-prepoznavanje-ptica-rajac',
        'kviz-prepoznavanje-ptica-rajac' => 'kviz-prepoznavanje-ptica-rajac',
        'ptice/monitoring-ptica-pio-rajac' => 'monitoring-ptica-pio-rajac',
        'monitoring-ptica-pio-rajac' => 'monitoring-ptica-pio-rajac',
        'ptice/ebird-l7416435-monitoring' => 'monitoring-ptica-pio-rajac',
        'ebird-l7416435-monitoring' => 'monitoring-ptica-pio-rajac',
        'ptice/merlin-ptice-rajac' => 'merlin-ptice-rajac',
        'merlin-ptice-rajac' => 'merlin-ptice-rajac',
    );
    if (isset($guide_map[$path])) {
        ptice45_send_view($guide_map[$path]);
    }

    $detail = ptice45_detail_slug_from_path($path);
    if ($detail !== '') {
        ptice45_send_view($detail);
    }
}

function ptice45_current_path() {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = (string) parse_url($request_uri, PHP_URL_PATH);
    $path = trim($path, '/');
    return trim(preg_replace('#/+#', '/', $path), '/');
}

function ptice45_detail_slug_from_path($path) {
    if (!preg_match('#^ptice/([^/]+)$#', $path, $match)) {
        return '';
    }

    $raw_slug = preg_replace('/\.html?$/i', '', (string) $match[1]);
    $slug = sanitize_title($raw_slug);
    $views = ptice45_view_files();
    if (isset($views[$slug])) {
        return $slug;
    }

    $aliases = array(
        'veliki-vranac1' => 'veliki-vranac',
        'veliki-detlic1' => 'veliki-detlic',
        'siva-vrana1' => 'siva-vrana',
        'orao-krstas1' => 'orao-krstas',
        'gavran1' => 'gavran',
        'kukavica1' => 'kukavica',
        'suri-orao1' => 'suri-orao',
        'obicna-travarka1' => 'obicna-travarka',
        'vrabac1' => 'vrabac',
        'velika-senica1' => 'velika-senica',
        'plava-senica1' => 'plava-senica',
        'brgljez1' => 'brgljez',
        'siva-senica1' => 'siva-senica',
        'mali-detlic1' => 'mali-detlic',
        'sumska-sova1' => 'sumska-sova',
        'cavka1' => 'cavka',
        'coccothraustes-coccothraustes1' => 'coccothraustes-coccothraustes',
        'motacilla-alba1' => 'motacilla-alba',
        'oenanthe-oenanthe1' => 'oenanthe-oenanthe',
        'emberiza-calandra1' => 'emberiza-calandra',
        'emberiza-hortulana1' => 'emberiza-hortulana',
        'accipiter-nisus1' => 'accipiter-nisus',
        'buteo-buteo1' => 'buteo-buteo',
        'ciconia-nigra1' => 'ciconia-nigra',
        'falco-subbuteo1' => 'falco-subbuteo',
        'falco-vespertinus1' => 'falco-vespertinus',
        'upupa-epops1' => 'upupa-epops',
        'merops-apiaster1' => 'merops-apiaster',
        'erithacus-rubecula1' => 'erithacus-rubecula',
        'turdus-merula1' => 'turdus-merula',
        'carduelis-carduelis1' => 'carduelis-carduelis',
        'pica-pica1' => 'pica-pica',
        'garrulus-glandarius1' => 'garrulus-glandarius',
        'streptopelia-decaocto1' => 'streptopelia-decaocto',
        'streptopelia-turtur1' => 'streptopelia-turtur',
        'hirundo-rustica1' => 'hirundo-rustica',
        'fringilla-coelebs1' => 'fringilla-coelebs',
        'sturnus-vulgaris1' => 'sturnus-vulgaris',
        'oriolus-oriolus1' => 'oriolus-oriolus',
        'emberiza-citrinella1' => 'emberiza-citrinella',
        'troglodytes-troglodytes1' => 'troglodytes-troglodytes',
        'turdus-philomelos1' => 'turdus-philomelos',
        'delichon-urbicum1' => 'delichon-urbicum',
        'turdus-viscivorus1' => 'turdus-viscivorus',
        'aegithalos-caudatus1' => 'aegithalos-caudatus',
        'serinus-serinus1' => 'serinus-serinus',
        'phylloscopus-trochilus1' => 'phylloscopus-trochilus',
        'picus-viridis1' => 'picus-viridis',
        'chloris-chloris1' => 'chloris-chloris',
        'ficedula-parva1' => 'ficedula-parva',
        'sylvia-communis1' => 'sylvia-communis',
        'phoenicurus-phoenicurus1' => 'phoenicurus-phoenicurus',
        'coturnix-coturnix1' => 'coturnix-coturnix',
        'phasianus-colchicus1' => 'phasianus-colchicus',
        'coracias-garrulus1' => 'coracias-garrulus',
        'dryocopus-martius1' => 'dryocopus-martius',
        'picus-canus1' => 'picus-canus',
        'prunella-modularis1' => 'prunella-modularis',
        'regulus-regulus1' => 'regulus-regulus',
        'certhia-brachydactyla1' => 'certhia-brachydactyla',
        'jynx-torquilla1' => 'jynx-torquilla',
        'lanius-collurio1' => 'lanius-collurio',
        'phalacrocorax-carbo' => 'veliki-vranac',
        'dendrocopos-major' => 'veliki-detlic',
        'corvus-cornix' => 'siva-vrana',
        'aquila-heliaca' => 'orao-krstas',
        'corvus-corax' => 'gavran',
        'cuculus-canorus' => 'kukavica',
        'aquila-chrysaetos' => 'suri-orao',
        'saxicola-rubetra' => 'obicna-travarka',
        'passer-domesticus' => 'vrabac',
        'parus-major' => 'velika-senica',
        'cyanistes-caeruleus' => 'plava-senica',
        'sitta-europaea' => 'brgljez',
        'poecile-palustris' => 'siva-senica',
        'dryobates-minor' => 'mali-detlic',
        'strix-aluco' => 'sumska-sova',
        'corvus-monedula' => 'cavka',
        'coccothraustes-coccothraustes' => 'coccothraustes-coccothraustes',
        'motacilla-alba' => 'motacilla-alba',
        'oenanthe-oenanthe' => 'oenanthe-oenanthe',
        'emberiza-calandra' => 'emberiza-calandra',
        'emberiza-hortulana' => 'emberiza-hortulana',
        'upupa-epops' => 'upupa-epops',
        'merops-apiaster' => 'merops-apiaster',
        'erithacus-rubecula' => 'erithacus-rubecula',
        'turdus-merula' => 'turdus-merula',
        'carduelis-carduelis' => 'carduelis-carduelis',
        'pica-pica' => 'pica-pica',
        'garrulus-glandarius' => 'garrulus-glandarius',
        'streptopelia-decaocto' => 'streptopelia-decaocto',
        'streptopelia-turtur' => 'streptopelia-turtur',
        'hirundo-rustica' => 'hirundo-rustica',
        'fringilla-coelebs' => 'fringilla-coelebs',
        'sturnus-vulgaris' => 'sturnus-vulgaris',
        'oriolus-oriolus' => 'oriolus-oriolus',
        'emberiza-citrinella' => 'emberiza-citrinella',
        'troglodytes-troglodytes' => 'troglodytes-troglodytes',
        'turdus-philomelos' => 'turdus-philomelos',
        'coturnix-coturnix' => 'coturnix-coturnix',
        'phasianus-colchicus' => 'phasianus-colchicus',
        'coracias-garrulus' => 'coracias-garrulus',
        'dryocopus-martius' => 'dryocopus-martius',
        'picus-canus' => 'picus-canus',
    );

    return isset($aliases[$slug]) ? $aliases[$slug] : '';
}

function ptice45_view_files() {
    return array(
        'archive' => 'index.html',
        'ptice' => 'index.html',
        'index' => 'index.html',
        'kucice-za-ptice' => 'kucice-za-ptice.html',
        'fotografisanje-ptica' => 'fotografisanje-ptica.html',
        'ponasanje-prema-pticama' => 'ponasanje-prema-pticama.html',
        'kviz-prepoznavanje-ptica-rajac' => 'kviz-prepoznavanje-ptica-rajac.html',
        'monitoring-ptica-pio-rajac' => 'monitoring-ptica-pio-rajac.html',
        'merlin-ptice-rajac' => 'merlin-ptice-rajac.html',
        'veliki-vranac' => 'veliki-vranac.html',
        'veliki-detlic' => 'veliki-detlic.html',
        'siva-vrana' => 'siva-vrana.html',
        'orao-krstas' => 'orao-krstas.html',
        'gavran' => 'gavran.html',
        'kukavica' => 'kukavica.html',
        'suri-orao' => 'suri-orao.html',
        'obicna-travarka' => 'obicna-travarka.html',
        'vrabac' => 'vrabac.html',
        'velika-senica' => 'velika-senica.html',
        'plava-senica' => 'plava-senica.html',
        'brgljez' => 'brgljez.html',
        'siva-senica' => 'siva-senica.html',
        'mali-detlic' => 'mali-detlic.html',
        'sumska-sova' => 'sumska-sova.html',
        'cavka' => 'cavka.html',
        'ciconia-nigra' => 'ciconia-nigra.html',
        'accipiter-nisus' => 'accipiter-nisus.html',
        'buteo-buteo' => 'buteo-buteo.html',
        'falco-vespertinus' => 'falco-vespertinus.html',
        'falco-subbuteo' => 'falco-subbuteo.html',
        'coccothraustes-coccothraustes' => 'coccothraustes-coccothraustes.html',
        'motacilla-alba' => 'motacilla-alba.html',
        'oenanthe-oenanthe' => 'oenanthe-oenanthe.html',
        'emberiza-calandra' => 'emberiza-calandra.html',
        'emberiza-hortulana' => 'emberiza-hortulana.html',
        'upupa-epops' => 'upupa-epops.html',
        'merops-apiaster' => 'merops-apiaster.html',
        'erithacus-rubecula' => 'erithacus-rubecula.html',
        'turdus-merula' => 'turdus-merula.html',
        'carduelis-carduelis' => 'carduelis-carduelis.html',
        'pica-pica' => 'pica-pica.html',
        'garrulus-glandarius' => 'garrulus-glandarius.html',
        'streptopelia-decaocto' => 'streptopelia-decaocto.html',
        'streptopelia-turtur' => 'streptopelia-turtur.html',
        'hirundo-rustica' => 'hirundo-rustica.html',
        'fringilla-coelebs' => 'fringilla-coelebs.html',
        'sturnus-vulgaris' => 'sturnus-vulgaris.html',
        'oriolus-oriolus' => 'oriolus-oriolus.html',
        'emberiza-citrinella' => 'emberiza-citrinella.html',
        'troglodytes-troglodytes' => 'troglodytes-troglodytes.html',
        'turdus-philomelos' => 'turdus-philomelos.html',
        'delichon-urbicum' => 'delichon-urbicum.html',
        'turdus-viscivorus' => 'turdus-viscivorus.html',
        'aegithalos-caudatus' => 'aegithalos-caudatus.html',
        'serinus-serinus' => 'serinus-serinus.html',
        'phylloscopus-trochilus' => 'phylloscopus-trochilus.html',
        'picus-viridis' => 'picus-viridis.html',
        'chloris-chloris' => 'chloris-chloris.html',
        'ficedula-parva' => 'ficedula-parva.html',
        'sylvia-communis' => 'sylvia-communis.html',
        'phoenicurus-phoenicurus' => 'phoenicurus-phoenicurus.html',
        'coturnix-coturnix' => 'coturnix-coturnix.html',
        'phasianus-colchicus' => 'phasianus-colchicus.html',
        'coracias-garrulus' => 'coracias-garrulus.html',
        'dryocopus-martius' => 'dryocopus-martius.html',
        'picus-canus' => 'picus-canus.html',
        'lullula-arborea' => 'lullula-arborea.html',
        'alauda-arvensis' => 'alauda-arvensis.html',
        'motacilla-cinerea' => 'motacilla-cinerea.html',
        'anthus-trivialis' => 'anthus-trivialis.html',
        'sylvia-atricapilla' => 'sylvia-atricapilla.html',
        'phylloscopus-collybita' => 'phylloscopus-collybita.html',
        'muscicapa-striata' => 'muscicapa-striata.html',
        'ficedula-hypoleuca' => 'ficedula-hypoleuca.html',
        'phoenicurus-ochruros' => 'phoenicurus-ochruros.html',
        'saxicola-rubicola' => 'saxicola-rubicola.html',
        'prunella-modularis' => 'prunella-modularis.html',
        'regulus-regulus' => 'regulus-regulus.html',
        'certhia-brachydactyla' => 'certhia-brachydactyla.html',
        'jynx-torquilla' => 'jynx-torquilla.html',
        'lanius-collurio' => 'lanius-collurio.html',
    );
}

function ptice45_send_view($slug) {
    $html = ptice45_get_view_html($slug);

    status_header(200);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: public, max-age=300, s-maxage=3600');
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit;
}

function ptice45_get_view_html($slug) {
    $views = ptice45_view_files();
    if (!isset($views[$slug])) {
        return ptice45_not_found_html();
    }

    $file = ptice45_find_view_file($views[$slug]);
    if (!is_readable($file)) {
        return ptice45_missing_file_html($views[$slug]);
    }

    $html = file_get_contents($file);
    if ($html === false) {
        return ptice45_missing_file_html($views[$slug]);
    }

    return ptice45_rewrite_html($html, $slug);
}

function ptice45_find_view_file($file_name) {
    $file_name = ltrim((string) $file_name, '/\\');
    $base_name = basename(untrailingslashit(PTICE45_DIR));
    $candidates = array(
        PTICE45_DIR . 'views/' . $file_name,
        PTICE45_DIR . $file_name,
        PTICE45_DIR . $base_name . '/views/' . $file_name,
        PTICE45_DIR . 'ptice/views/' . $file_name,
    );

    foreach ($candidates as $candidate) {
        if (is_readable($candidate)) {
            return $candidate;
        }
    }

    return $candidates[0];
}

function ptice45_not_found_html() {
    return '<!doctype html><html lang="sr-RS"><head><meta charset="UTF-8"><title>Птице</title></head><body>Страница није пронађена.</body></html>';
}

function ptice45_missing_file_html($file_name) {
    $safe_file = esc_html($file_name);
    $safe_dir = esc_html(PTICE45_DIR);
    return '<!doctype html><html lang="sr-RS"><head><meta charset="UTF-8"><title>Птице</title></head><body style="font-family:Arial,sans-serif;padding:24px"><h1>Птице</h1><p>Недостаје HTML фајл: <strong>' . $safe_file . '</strong></p><p>Plugin folder: <code>' . $safe_dir . '</code></p><p>Провери да ли постоји folder <code>views</code> поред <code>ptice.php</code>.</p></body></html>';
}

function ptice45_rewrite_html($html, $slug) {
    $asset_url = trailingslashit(PTICE45_URL . 'assets/ptice2');
    $audio_url = trailingslashit(PTICE45_URL . 'assets/audio');
    $replacements = array(
        'src="ptice2/' => 'src="' . esc_url($asset_url),
        "src='ptice2/" => "src='" . esc_url($asset_url),
        'href="ptice2/' => 'href="' . esc_url($asset_url),
        "href='ptice2/" => "href='" . esc_url($asset_url),
        'img: "ptice2/' => 'img: "' . esc_url($asset_url),
        "img: 'ptice2/" => "img: '" . esc_url($asset_url),
        'fallback: "ptice2/' => 'fallback: "' . esc_url($asset_url),
        "fallback: 'ptice2/" => "fallback: '" . esc_url($asset_url),
        'src="audio/' => 'src="' . esc_url($audio_url),
        "src='audio/" => "src='" . esc_url($audio_url),
        'data-img-base="ptice2/' => 'data-img-base="' . esc_url($asset_url),
        "data-img-base='ptice2/" => "data-img-base='" . esc_url($asset_url),
        'data-audio-base="audio/' => 'data-audio-base="' . esc_url($audio_url),
        "data-audio-base='audio/" => "data-audio-base='" . esc_url($audio_url),
        "url('ptice2/" => "url('" . esc_url($asset_url),
        'url("ptice2/' => 'url("' . esc_url($asset_url),
    );
    $html = strtr($html, $replacements);

    $absolute_asset_replacements = array(
        'https://piorajac.rs/wp-content/plugins/ptice/assets/ptice2/' => esc_url($asset_url),
        'https://piorajac.rs/wp-content/plugins/ptice/assets/audio/' => esc_url($audio_url),
        'https://piorajac.rs/wp-content/plugins/ptice0706-crvendac-vodici/assets/ptice2/' => esc_url($asset_url),
        'https://piorajac.rs/wp-content/plugins/ptice0706-crvendac-vodici/assets/audio/' => esc_url($audio_url),
    );
    $html = strtr($html, $absolute_asset_replacements);

    $detail_links = array(
        'ptice2.html' => home_url('/ptice/'),
        'uputstvo-fotografisanje-ptica-rajac-prvi-dizajn-dopune.html' => home_url('/ptice/fotografisanje-ptica/'),
        'uputstvo-ponasanje-prema-pticama-rajac-ptice-style-korigovano.html' => home_url('/ptice/ponasanje-prema-pticama/'),
        'kviz-prepoznavanje-ptica-rajac.html' => home_url('/ptice/kviz-prepoznavanje-ptica-rajac/'),
        'monitoring-ptica-pio-rajac.html' => home_url('/ptice/monitoring-ptica-pio-rajac/'),
        'monitoring-ptica-pio-rajac-velika-cista.html' => home_url('/ptice/monitoring-ptica-pio-rajac/'),
        'veliki-vranac1.html' => home_url('/ptice/veliki-vranac/'),
        'veliki-detlic1.html' => home_url('/ptice/veliki-detlic/'),
        'siva-vrana1.html' => home_url('/ptice/siva-vrana/'),
        'orao-krstas1.html' => home_url('/ptice/orao-krstas/'),
        'gavran1.html' => home_url('/ptice/gavran/'),
        'kukavica1.html' => home_url('/ptice/kukavica/'),
        'suri-orao1.html' => home_url('/ptice/suri-orao/'),
        'obicna-travarka1.html' => home_url('/ptice/obicna-travarka/'),
        'vrabac1.html' => home_url('/ptice/vrabac/'),
        'velika-senica1.html' => home_url('/ptice/velika-senica/'),
        'plava-senica1.html' => home_url('/ptice/plava-senica/'),
        'brgljez1.html' => home_url('/ptice/brgljez/'),
        'siva-senica1.html' => home_url('/ptice/siva-senica/'),
        'mali-detlic1.html' => home_url('/ptice/mali-detlic/'),
        'sumska-sova1.html' => home_url('/ptice/sumska-sova/'),
        'cavka1.html' => home_url('/ptice/cavka/'),
        'coccothraustes-coccothraustes1.html' => home_url('/ptice/coccothraustes-coccothraustes/'),
        'motacilla-alba1.html' => home_url('/ptice/motacilla-alba/'),
        'oenanthe-oenanthe1.html' => home_url('/ptice/oenanthe-oenanthe/'),
        'emberiza-calandra1.html' => home_url('/ptice/emberiza-calandra/'),
        'emberiza-hortulana1.html' => home_url('/ptice/emberiza-hortulana/'),
        'accipiter-nisus1.html' => home_url('/ptice/accipiter-nisus/'),
        'buteo-buteo1.html' => home_url('/ptice/buteo-buteo/'),
        'ciconia-nigra1.html' => home_url('/ptice/ciconia-nigra/'),
        'falco-subbuteo1.html' => home_url('/ptice/falco-subbuteo/'),
        'falco-vespertinus1.html' => home_url('/ptice/falco-vespertinus/'),
        'upupa-epops1.html' => home_url('/ptice/upupa-epops/'),
        'merops-apiaster1.html' => home_url('/ptice/merops-apiaster/'),
        'erithacus-rubecula1.html' => home_url('/ptice/erithacus-rubecula/'),
        'turdus-merula1.html' => home_url('/ptice/turdus-merula/'),
        'carduelis-carduelis1.html' => home_url('/ptice/carduelis-carduelis/'),
        'pica-pica1.html' => home_url('/ptice/pica-pica/'),
        'garrulus-glandarius1.html' => home_url('/ptice/garrulus-glandarius/'),
        'streptopelia-decaocto1.html' => home_url('/ptice/streptopelia-decaocto/'),
        'streptopelia-turtur1.html' => home_url('/ptice/streptopelia-turtur/'),
        'hirundo-rustica1.html' => home_url('/ptice/hirundo-rustica/'),
        'fringilla-coelebs1.html' => home_url('/ptice/fringilla-coelebs/'),
        'sturnus-vulgaris1.html' => home_url('/ptice/sturnus-vulgaris/'),
        'oriolus-oriolus1.html' => home_url('/ptice/oriolus-oriolus/'),
        'emberiza-citrinella1.html' => home_url('/ptice/emberiza-citrinella/'),
        'troglodytes-troglodytes1.html' => home_url('/ptice/troglodytes-troglodytes/'),
        'turdus-philomelos1.html' => home_url('/ptice/turdus-philomelos/'),
        'delichon-urbicum.html' => home_url('/ptice/delichon-urbicum/'),
        'delichon-urbicum1.html' => home_url('/ptice/delichon-urbicum/'),
        'turdus-viscivorus.html' => home_url('/ptice/turdus-viscivorus/'),
        'turdus-viscivorus1.html' => home_url('/ptice/turdus-viscivorus/'),
        'aegithalos-caudatus.html' => home_url('/ptice/aegithalos-caudatus/'),
        'aegithalos-caudatus1.html' => home_url('/ptice/aegithalos-caudatus/'),
        'serinus-serinus.html' => home_url('/ptice/serinus-serinus/'),
        'serinus-serinus1.html' => home_url('/ptice/serinus-serinus/'),
        'phylloscopus-trochilus.html' => home_url('/ptice/phylloscopus-trochilus/'),
        'phylloscopus-trochilus1.html' => home_url('/ptice/phylloscopus-trochilus/'),
        'picus-viridis.html' => home_url('/ptice/picus-viridis/'),
        'picus-viridis1.html' => home_url('/ptice/picus-viridis/'),
        'chloris-chloris.html' => home_url('/ptice/chloris-chloris/'),
        'chloris-chloris1.html' => home_url('/ptice/chloris-chloris/'),
        'ficedula-parva.html' => home_url('/ptice/ficedula-parva/'),
        'ficedula-parva1.html' => home_url('/ptice/ficedula-parva/'),
        'sylvia-communis.html' => home_url('/ptice/sylvia-communis/'),
        'sylvia-communis1.html' => home_url('/ptice/sylvia-communis/'),
        'phoenicurus-phoenicurus.html' => home_url('/ptice/phoenicurus-phoenicurus/'),
        'phoenicurus-phoenicurus1.html' => home_url('/ptice/phoenicurus-phoenicurus/'),
        'coturnix-coturnix.html' => home_url('/ptice/coturnix-coturnix/'),
        'phasianus-colchicus.html' => home_url('/ptice/phasianus-colchicus/'),
        'coracias-garrulus.html' => home_url('/ptice/coracias-garrulus/'),
        'dryocopus-martius.html' => home_url('/ptice/dryocopus-martius/'),
        'picus-canus.html' => home_url('/ptice/picus-canus/'),
        'lullula-arborea.html' => home_url('/ptice/lullula-arborea/'),
        'lullula-arborea1.html' => home_url('/ptice/lullula-arborea/'),
        'alauda-arvensis.html' => home_url('/ptice/alauda-arvensis/'),
        'alauda-arvensis1.html' => home_url('/ptice/alauda-arvensis/'),
        'motacilla-cinerea.html' => home_url('/ptice/motacilla-cinerea/'),
        'motacilla-cinerea1.html' => home_url('/ptice/motacilla-cinerea/'),
        'anthus-trivialis.html' => home_url('/ptice/anthus-trivialis/'),
        'anthus-trivialis1.html' => home_url('/ptice/anthus-trivialis/'),
        'sylvia-atricapilla.html' => home_url('/ptice/sylvia-atricapilla/'),
        'sylvia-atricapilla1.html' => home_url('/ptice/sylvia-atricapilla/'),
        'phylloscopus-collybita.html' => home_url('/ptice/phylloscopus-collybita/'),
        'phylloscopus-collybita1.html' => home_url('/ptice/phylloscopus-collybita/'),
        'muscicapa-striata.html' => home_url('/ptice/muscicapa-striata/'),
        'muscicapa-striata1.html' => home_url('/ptice/muscicapa-striata/'),
        'ficedula-hypoleuca.html' => home_url('/ptice/ficedula-hypoleuca/'),
        'ficedula-hypoleuca1.html' => home_url('/ptice/ficedula-hypoleuca/'),
        'phoenicurus-ochruros.html' => home_url('/ptice/phoenicurus-ochruros/'),
        'phoenicurus-ochruros1.html' => home_url('/ptice/phoenicurus-ochruros/'),
        'saxicola-rubicola.html' => home_url('/ptice/saxicola-rubicola/'),
        'saxicola-rubicola1.html' => home_url('/ptice/saxicola-rubicola/'),
    );
    foreach ($detail_links as $old => $new) {
        $safe_new = esc_url($new);
        $html = str_replace('href="' . $old . '"', 'href="' . $safe_new . '"', $html);
        $html = str_replace("href='" . $old . "'", "href='" . $safe_new . "'", $html);
        $html = str_replace('url: "' . $old . '"', 'url: "' . $safe_new . '"', $html);
        $html = str_replace("url: '" . $old . "'", "url: '" . $safe_new . "'", $html);
    }

    $public_links = array(
        'href="/ptice/"' => 'href="' . esc_url(home_url('/ptice/')) . '"',
        'href="/ptice/kucice-za-ptice/"' => 'href="' . esc_url(home_url('/ptice/kucice-za-ptice/')) . '"',
        'href="/ptice/fotografisanje-ptica/"' => 'href="' . esc_url(home_url('/ptice/fotografisanje-ptica/')) . '"',
        'href="/ptice/ponasanje-prema-pticama/"' => 'href="' . esc_url(home_url('/ptice/ponasanje-prema-pticama/')) . '"',
        'href="/ptice/kviz-prepoznavanje-ptica-rajac/"' => 'href="' . esc_url(home_url('/ptice/kviz-prepoznavanje-ptica-rajac/')) . '"',
        'href="/ptice/monitoring-ptica-pio-rajac/"' => 'href="' . esc_url(home_url('/ptice/monitoring-ptica-pio-rajac/')) . '"',
        'href="/ptice/merlin-ptice-rajac/"' => 'href="' . esc_url(home_url('/ptice/merlin-ptice-rajac/')) . '"',
    );
    $html = strtr($html, $public_links);

    if (strpos($html, 'ebird-l7416435-data.php') !== false) {
        $html = str_replace('data-endpoint="ebird-l7416435-data.php"', 'data-endpoint="' . esc_url(PTICE45_URL . 'ebird-l7416435-data.php') . '"', $html);
        $html = str_replace("data-endpoint='ebird-l7416435-data.php'", "data-endpoint='" . esc_url(PTICE45_URL . 'ebird-l7416435-data.php') . "'", $html);
    }

    $marker = '<!-- PTICE45 PUPAVAC ACTIVE ' . esc_html($slug) . ' -->';
    if (strpos($html, '</head>') !== false) {
        $html = str_replace('</head>', $marker . "\n</head>", $html);
    }

    return $html;
}

add_action('admin_menu', 'ptice45_admin_menu');
function ptice45_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=ptice',
        'Index прва страна',
        'Index прва страна',
        'manage_options',
        'ptice45-status',
        'ptice45_admin_page'
    );
}

function ptice45_admin_page() {
    $index_file = ptice45_find_view_file('index.html');
    $kucice_file = ptice45_find_view_file('kucice-za-ptice.html');
    $foto_file = ptice45_find_view_file('fotografisanje-ptica.html');
    $ponasanje_file = ptice45_find_view_file('ponasanje-prema-pticama.html');
    ?>
    <div class="wrap">
        <h1>Птице Рајац</h1>
        <p>Ова верзија директно служи прву страну <code>index.html</code> на <a href="<?php echo esc_url(home_url('/ptice/')); ?>" target="_blank" rel="noopener">/ptice/</a>.</p>
        <table class="widefat striped" style="max-width:900px">
            <thead><tr><th>Страница</th><th>Фајл у plugin-у</th><th>Статус фајла</th><th>Јавна адреса</th></tr></thead>
            <tbody>
                <tr><td>Птице</td><td><code>index.html</code></td><td><?php echo is_readable($index_file) ? 'OK: ' . esc_html($index_file) : 'NEDOSTAJE'; ?></td><td><a href="<?php echo esc_url(home_url('/ptice/')); ?>" target="_blank" rel="noopener"><?php echo esc_html(home_url('/ptice/')); ?></a></td></tr>
                <tr><td>Кућице за птице</td><td><code>views/kucice-za-ptice.html</code></td><td><?php echo is_readable($kucice_file) ? 'OK: ' . esc_html($kucice_file) : 'NEDOSTAJE'; ?></td><td><a href="<?php echo esc_url(home_url('/ptice/kucice-za-ptice/')); ?>" target="_blank" rel="noopener"><?php echo esc_html(home_url('/ptice/kucice-za-ptice/')); ?></a></td></tr>
                <tr><td>Фотографисање птица</td><td><code>views/fotografisanje-ptica.html</code></td><td><?php echo is_readable($foto_file) ? 'OK: ' . esc_html($foto_file) : 'NEDOSTAJE'; ?></td><td><a href="<?php echo esc_url(home_url('/ptice/fotografisanje-ptica/')); ?>" target="_blank" rel="noopener"><?php echo esc_html(home_url('/ptice/fotografisanje-ptica/')); ?></a></td></tr>
                <tr><td>Понашање према птицама</td><td><code>views/ponasanje-prema-pticama.html</code></td><td><?php echo is_readable($ponasanje_file) ? 'OK: ' . esc_html($ponasanje_file) : 'NEDOSTAJE'; ?></td><td><a href="<?php echo esc_url(home_url('/ptice/ponasanje-prema-pticama/')); ?>" target="_blank" rel="noopener"><?php echo esc_html(home_url('/ptice/ponasanje-prema-pticama/')); ?></a></td></tr>
            </tbody>
        </table>
    </div>
    <?php
}

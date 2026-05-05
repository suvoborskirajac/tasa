<?php
/**
 * Plugin Name: Сисари Рајца — ПИО Рајац v3
 * Description: Сисари са meta box пољима едитабилним у WP admin-у. Custom Post Type /sisari/vrsta-slug/. Исти систем као Птице.
 * Version:     3.0.0
 * Author:      ПИО Рајац
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════
// 1. CUSTOM POST TYPE
// ══════════════════════════════════════════
add_action( 'init', 'sisari1_register_cpt' );
function sisari1_register_cpt() {
    register_post_type( 'sisari', [
        'labels' => [
            'name'          => 'Сисари',
            'singular_name' => 'Сисар',
            'all_items'     => 'Сви сисари',
            'add_new'       => 'Додај сисара',
            'add_new_item'  => 'Додај новог сисара',
            'edit_item'     => 'Уреди сисара',
            'view_item'     => 'Погледај сисара',
            'menu_name'     => 'Сисари',
        ],
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-pets',
        'query_var'          => true,
        'rewrite'            => [ 'slug' => 'sisari', 'with_front' => false ],
        'has_archive'        => 'sisari',
        'hierarchical'       => false,
        'supports'           => [ 'title', 'thumbnail' ],
        'show_in_rest'       => false,
    ]);
}

// ══════════════════════════════════════════
// 2. META BOXES
// ══════════════════════════════════════════
add_action( 'add_meta_boxes', 'sisari1_meta_boxes' );
function sisari1_meta_boxes() {

    add_meta_box( 'sisari1_osnovni', '🐾 Основни подаци',
        'sisari1_mb_osnovni', 'sisari', 'normal', 'high' );

    $rich = [
        'opis'          => '🔍 Морфолошки опис',
        'staniste'      => '🌿 Станиште',
        'biologija'     => '🔬 Биологија',
        'ishrana'       => '🍃 Исхрана',
        'razmnozenost'  => '🌸 Размножавање',
        'zastita'       => '🛡 Заштита и статус',
        'tragovi'       => '🐾 Трагови и знаци присуства',
    ];
    foreach ( $rich as $key => $title ) {
        add_meta_box( 'sisari1_' . $key, $title,
            function($post) use ($key) { sisari1_mb_rich($post, '_sisari_' . $key); },
            'sisari', 'normal', 'default' );
    }

    add_meta_box( 'sisari1_aktivnost', '📅 Феносезонски календар на Рајцу',
        'sisari1_mb_aktivnost', 'sisari', 'side', 'default' );

    add_meta_box( 'sisari1_zvuci', '🔊 Гласање и звуци',
        'sisari1_mb_zvuci', 'sisari', 'side', 'default' );
}

function sisari1_mb_osnovni( $post ) {
    wp_nonce_field( 'sisari1_save', 'sisari1_nonce' );
    $f = function($key) use ($post) { return get_post_meta($post->ID, $key, true); };
    ?>
    <style>
    .s1grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:8px}
    .s1grid .full{grid-column:1/-1}
    .s1grid label{font-weight:600;font-size:12px;color:#555;display:block;margin-bottom:4px}
    .s1grid input,.s1grid select,.s1grid textarea{width:100%;padding:7px 10px;border:1px solid #ddd;border-radius:5px;font-size:13px}
    .s1grid textarea{min-height:70px;resize:vertical}
    </style>
    <div class="s1grid">
      <div>
        <label>🔬 Латински назив</label>
        <input type="text" name="sisari_latin" value="<?= esc_attr($f('_sisari_latin')) ?>" placeholder="нпр. Vulpes vulpes">
      </div>
      <div>
        <label>🌍 IUCN статус</label>
        <select name="sisari_iucn">
          <?php foreach(['LC'=>'LC — Минимална забринутост','NT'=>'NT — Близу угрожене','VU'=>'VU — Рањива','EN'=>'EN — Угрожена','CR'=>'CR — Критично угрожена'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= selected($f('_sisari_iucn'),$v,false) ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>📏 Дужина тела</label>
        <input type="text" name="sisari_length" value="<?= esc_attr($f('_sisari_length')) ?>" placeholder="нпр. 40–50 cm">
      </div>
      <div>
        <label>⚖️ Тежина</label>
        <input type="text" name="sisari_weight" value="<?= esc_attr($f('_sisari_weight')) ?>" placeholder="нпр. 3–8 kg">
      </div>
      <div>
        <label>🦴 Ред</label>
        <input type="text" name="sisari_red" value="<?= esc_attr($f('_sisari_red')) ?>" placeholder="нпр. Carnivora">
      </div>
      <div>
        <label>🐾 Породица</label>
        <input type="text" name="sisari_porodica" value="<?= esc_attr($f('_sisari_porodica')) ?>" placeholder="нпр. Canidae">
      </div>
      <div>
        <label>✅ Статус на Рајцу</label>
        <select name="sisari_status_rajac">
          <?php foreach(['potvrdjen'=>'✓ Потврђен и редован','potencijalan'=>'◎ Потенцијалан / спорадичан'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= selected($f('_sisari_status_rajac'),$v,false) ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>🏷 Категорија</label>
        <select name="sisari_kategorija">
          <?php foreach(['papkari'=>'Папкари','zecevi'=>'Зечеви','zveri'=>'Звери','glodari'=>'Глодари','bubojedи'=>'Бубоједи','slepi_misevi'=>'Слепи мишеви'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= selected($f('_sisari_kategorija'),$v,false) ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="full">
        <label>📋 Статус заштите (кратак текст)</label>
        <textarea name="sisari_status_zastite"><?= esc_textarea($f('_sisari_status_zastite')) ?></textarea>
      </div>
      <div class="full">
        <label>🖼 URL слике (Wikimedia или директан линк)</label>
        <input type="text" name="sisari_foto_url" value="<?= esc_attr($f('_sisari_foto_url')) ?>" placeholder="https://upload.wikimedia.org/...">
      </div>
    </div>
    <?php
}

function sisari1_mb_rich( $post, $meta_key ) {
    $content   = get_post_meta( $post->ID, $meta_key, true );
    $editor_id = 's1_' . preg_replace('/[^a-z0-9]/', '', $meta_key);
    echo '<p style="font-size:11px;color:#999;margin:4px 0 10px">Meta кључ: <code>' . esc_html($meta_key) . '</code></p>';
    wp_editor( $content, $editor_id, [
        'textarea_name' => $meta_key,
        'media_buttons' => false,
        'textarea_rows' => 8,
        'teeny'         => false,
        'quicktags'     => true,
        'tinymce'       => ['toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,undo,redo'],
    ]);
}

function sisari1_mb_aktivnost( $post ) {
    $ak_str  = get_post_meta($post->ID,'_sisari_aktivni',true);
    $pa_str  = get_post_meta($post->ID,'_sisari_parenje',true);
    $ml_str  = get_post_meta($post->ID,'_sisari_mladi',true);
    $hi_str  = get_post_meta($post->ID,'_sisari_hibernacija',true);
    $ak_arr  = $ak_str  ? array_map('intval',explode(',',$ak_str))  : [];
    $pa_arr  = $pa_str  ? array_map('intval',explode(',',$pa_str)) : [];
    $ml_arr  = $ml_str  ? array_map('intval',explode(',',$ml_str)) : [];
    $hi_arr  = $hi_str  ? array_map('intval',explode(',',$hi_str)) : [];
    $mes     = ['Јан','Феб','Мар','Апр','Мај','Јун','Јул','Авг','Сеп','Окт','Нов','Дец'];
    ?>
    <style>
    .s1mtg{display:grid;grid-template-columns:repeat(4,1fr);gap:5px;margin-bottom:10px}
    .s1mt input{display:none}
    .s1mt label{display:block;padding:5px 2px;border-radius:4px;font-size:10px;font-weight:700;text-align:center;cursor:pointer;border:1px solid #e0e0e0;color:#888;background:#f7f7f7}
    .s1ak input:checked+label{background:#2d5a27;color:#fff;border-color:#2d5a27}
    .s1pa input:checked+label{background:#c4500a;color:#fff;border-color:#c4500a}
    .s1ml input:checked+label{background:#e8a020;color:#3a2000;border-color:#e8a020}
    .s1hi input:checked+label{background:#7a9ab0;color:#fff;border-color:#7a9ab0}
    .s1leg{font-size:11px;color:#777;display:flex;gap:8px;margin-top:8px;flex-wrap:wrap}
    .s1leg span{display:flex;align-items:center;gap:4px}
    .s1dot{width:10px;height:10px;border-radius:2px;display:inline-block}
    </style>
    <p style="font-size:12px;font-weight:700;color:#2d5a27;margin:0 0 5px">🌿 Активна</p>
    <div class="s1mtg">
    <?php foreach($mes as $i=>$m): $n=$i+1; ?>
      <div class="s1mt s1ak">
        <input type="checkbox" id="s1ak<?=$n?>" name="sisari_aktivni[]" value="<?=$n?>" <?=in_array($n,$ak_arr)?'checked':''?>>
        <label for="s1ak<?=$n?>"><?=$m?></label>
      </div>
    <?php endforeach; ?>
    </div>
    <p style="font-size:12px;font-weight:700;color:#c4500a;margin:8px 0 5px">💕 Парење</p>
    <div class="s1mtg">
    <?php foreach($mes as $i=>$m): $n=$i+1; ?>
      <div class="s1mt s1pa">
        <input type="checkbox" id="s1pa<?=$n?>" name="sisari_parenje[]" value="<?=$n?>" <?=in_array($n,$pa_arr)?'checked':''?>>
        <label for="s1pa<?=$n?>"><?=$m?></label>
      </div>
    <?php endforeach; ?>
    </div>
    <p style="font-size:12px;font-weight:700;color:#b07a10;margin:8px 0 5px">🐣 Младунци</p>
    <div class="s1mtg">
    <?php foreach($mes as $i=>$m): $n=$i+1; ?>
      <div class="s1mt s1ml">
        <input type="checkbox" id="s1ml<?=$n?>" name="sisari_mladi[]" value="<?=$n?>" <?=in_array($n,$ml_arr)?'checked':''?>>
        <label for="s1ml<?=$n?>"><?=$m?></label>
      </div>
    <?php endforeach; ?>
    </div>
    <p style="font-size:12px;font-weight:700;color:#7a9ab0;margin:8px 0 5px">❄️ Хибернација / мировање</p>
    <div class="s1mtg">
    <?php foreach($mes as $i=>$m): $n=$i+1; ?>
      <div class="s1mt s1hi">
        <input type="checkbox" id="s1hi<?=$n?>" name="sisari_hibernacija[]" value="<?=$n?>" <?=in_array($n,$hi_arr)?'checked':''?>>
        <label for="s1hi<?=$n?>"><?=$m?></label>
      </div>
    <?php endforeach; ?>
    </div>
    <div class="s1leg">
      <span><span class="s1dot" style="background:#2d5a27"></span>Активна</span>
      <span><span class="s1dot" style="background:#c4500a"></span>Парење</span>
      <span><span class="s1dot" style="background:#e8a020"></span>Младунци</span>
      <span><span class="s1dot" style="background:#7a9ab0"></span>Хибернација</span>
    </div>
    <?php
}

function sisari1_mb_zvuci( $post ) {
    $z1 = get_post_meta($post->ID,'_sisari_zvuk1_naziv',true);
    $z1o = get_post_meta($post->ID,'_sisari_zvuk1_opis',true);
    $z2 = get_post_meta($post->ID,'_sisari_zvuk2_naziv',true);
    $z2o = get_post_meta($post->ID,'_sisari_zvuk2_opis',true);
    $xc  = get_post_meta($post->ID,'_sisari_xeno_canto',true);
    ?>
    <style>
    .s1zg label{font-weight:600;font-size:12px;color:#555;display:block;margin-bottom:4px;margin-top:10px}
    .s1zg input,.s1zg textarea{width:100%;padding:6px 9px;border:1px solid #ddd;border-radius:5px;font-size:13px}
    </style>
    <div class="s1zg">
      <label>Звук 1 — назив</label>
      <input type="text" name="sisari_zvuk1_naziv" value="<?= esc_attr($z1) ?>" placeholder="нпр. Узбуна — лавеж">
      <label>Звук 1 — опис</label>
      <textarea name="sisari_zvuk1_opis" rows="2"><?= esc_textarea($z1o) ?></textarea>
      <label>Звук 2 — назив</label>
      <input type="text" name="sisari_zvuk2_naziv" value="<?= esc_attr($z2) ?>" placeholder="нпр. Парење — цвиљење">
      <label>Звук 2 — опис</label>
      <textarea name="sisari_zvuk2_opis" rows="2"><?= esc_textarea($z2o) ?></textarea>
      <label>Xeno-canto / xeno URL (опционо)</label>
      <input type="text" name="sisari_xeno_canto" value="<?= esc_attr($xc) ?>" placeholder="https://xeno-canto.org/...">
    </div>
    <?php
}

// ══════════════════════════════════════════
// 3. СНИМАЊЕ
// ══════════════════════════════════════════
add_action( 'save_post_sisari', 'sisari1_save_meta' );
function sisari1_save_meta( $post_id ) {
    if ( ! isset($_POST['sisari1_nonce']) ) return;
    if ( ! wp_verify_nonce($_POST['sisari1_nonce'],'sisari1_save') ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;

    $simple = [
        'sisari_latin'          => '_sisari_latin',
        'sisari_iucn'           => '_sisari_iucn',
        'sisari_length'         => '_sisari_length',
        'sisari_weight'         => '_sisari_weight',
        'sisari_red'            => '_sisari_red',
        'sisari_porodica'       => '_sisari_porodica',
        'sisari_status_rajac'   => '_sisari_status_rajac',
        'sisari_kategorija'     => '_sisari_kategorija',
        'sisari_status_zastite' => '_sisari_status_zastite',
        'sisari_foto_url'       => '_sisari_foto_url',
        'sisari_zvuk1_naziv'    => '_sisari_zvuk1_naziv',
        'sisari_zvuk1_opis'     => '_sisari_zvuk1_opis',
        'sisari_zvuk2_naziv'    => '_sisari_zvuk2_naziv',
        'sisari_zvuk2_opis'     => '_sisari_zvuk2_opis',
        'sisari_xeno_canto'     => '_sisari_xeno_canto',
    ];
    foreach( $simple as $k=>$m ) {
        if ( isset($_POST[$k]) ) update_post_meta($post_id,$m,sanitize_text_field($_POST[$k]));
    }
    $rich = ['_sisari_opis','_sisari_staniste','_sisari_biologija','_sisari_ishrana',
             '_sisari_razmnozenost','_sisari_zastita','_sisari_tragovi'];
    foreach( $rich as $m ) {
        if ( isset($_POST[$m]) ) update_post_meta($post_id,$m,wp_kses_post($_POST[$m]));
    }
    $kal = ['sisari_aktivni'=>'_sisari_aktivni','sisari_parenje'=>'_sisari_parenje',
            'sisari_mladi'=>'_sisari_mladi','sisari_hibernacija'=>'_sisari_hibernacija'];
    foreach( $kal as $k=>$m ) {
        $v = isset($_POST[$k]) ? implode(',',array_map('intval',$_POST[$k])) : '';
        update_post_meta($post_id,$m,$v);
    }
}

// ══════════════════════════════════════════
// 4. ПРИКАЗ СТРАНИЦЕ
// ══════════════════════════════════════════
add_filter( 'the_content', 'sisari1_render' );
function sisari1_render( $content ) {
    if ( ! is_singular('sisari') ) return $content;

    $pid     = get_the_ID();
    $naziv   = get_the_title();
    $latin   = get_post_meta($pid,'_sisari_latin',true);
    $iucn    = get_post_meta($pid,'_sisari_iucn',true) ?: 'LC';
    $length  = get_post_meta($pid,'_sisari_length',true);
    $weight  = get_post_meta($pid,'_sisari_weight',true);
    $red_val = get_post_meta($pid,'_sisari_red',true);
    $porod   = get_post_meta($pid,'_sisari_porodica',true);
    $st_raj  = get_post_meta($pid,'_sisari_status_rajac',true) ?: 'potvrdjen';
    $st_zast = get_post_meta($pid,'_sisari_status_zastite',true);
    $foto    = get_post_meta($pid,'_sisari_foto_url',true);

    $ak_str  = get_post_meta($pid,'_sisari_aktivni',true);
    $pa_str  = get_post_meta($pid,'_sisari_parenje',true);
    $ml_str  = get_post_meta($pid,'_sisari_mladi',true);
    $hi_str  = get_post_meta($pid,'_sisari_hibernacija',true);

    $z1n = get_post_meta($pid,'_sisari_zvuk1_naziv',true);
    $z1o = get_post_meta($pid,'_sisari_zvuk1_opis',true);
    $z2n = get_post_meta($pid,'_sisari_zvuk2_naziv',true);
    $z2o = get_post_meta($pid,'_sisari_zvuk2_opis',true);
    $xc  = get_post_meta($pid,'_sisari_xeno_canto',true);

    $f = [];
    foreach(['opis','staniste','biologija','ishrana','razmnozenost','zastita','tragovi'] as $k)
        $f[$k] = get_post_meta($pid,'_sisari_'.$k,true);

    // Навигација
    $all = get_posts(['post_type'=>'sisari','numberposts'=>-1,'orderby'=>'title','order'=>'ASC','post_status'=>'publish']);
    $ids = wp_list_pluck($all,'ID');
    $pos = array_search($pid,$ids);
    $arch = get_post_type_archive_link('sisari');
    $prev = ($pos>0) ? '<a href="'.get_permalink($ids[$pos-1]).'" class="nav-sisari nav-prev">← '.get_the_title($ids[$pos-1]).'</a>' : '<span class="nav-sisari nav-prev nav-disabled"></span>';
    $next = ($pos!==false && $pos<count($ids)-1) ? '<a href="'.get_permalink($ids[$pos+1]).'" class="nav-sisari nav-next">'.get_the_title($ids[$pos+1]).' →</a>' : '<span class="nav-sisari nav-next nav-disabled"></span>';

    // Фотографија
    $fallback_emoji = '🐾';
    if ( $foto ) {
        $primary_img = $foto;
        $fallback_js = 'this.parentElement.innerHTML=\'<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:70px">' . $fallback_emoji . '</div>\'';
    } else {
        $primary_img = '';
        $fallback_js = '';
    }
    if ( $primary_img ) {
        $photo = '<img src="'.esc_url($primary_img).'" alt="'.esc_attr($naziv).'" onerror="'.$fallback_js.'">';
    } else {
        $photo = '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:80px">'.$fallback_emoji.'</div>';
    }

    // IUCN
    $iucn_map = ['LC'=>'Минимална забринутост','NT'=>'Близу угрожене','VU'=>'Рањива','EN'=>'Угрожена','CR'=>'Критично угрожена'];
    $iucn_lbl = $iucn_map[$iucn] ?? $iucn;
    $is_threat = in_array($iucn,['VU','EN','CR']);
    $iucn_cls  = $is_threat ? 'iucn-vu' : 'iucn-lc';
    $iucn_col  = $is_threat ? '#fdba74' : '#86efac';

    $status_rajac_lbl = ($st_raj === 'potvrdjen') ? '✓ Потврђен и редован на Рајцу' : '◎ Потенцијалан / спорадичан';

    // Карактеристике
    $chars = '';
    if($length) $chars .= '<div class="char-item"><div class="char-icon">📏</div><div class="char-label">Дужина тела</div><div class="char-val">'.esc_html($length).'</div></div>';
    if($weight) $chars .= '<div class="char-item"><div class="char-icon">⚖️</div><div class="char-label">Тежина</div><div class="char-val">'.esc_html($weight).'</div></div>';
    if($red_val) $chars .= '<div class="char-item"><div class="char-icon">🦴</div><div class="char-label">Ред</div><div class="char-val">'.esc_html($red_val).'</div></div>';
    if(!$chars) $chars = '<div class="char-item"><div class="char-icon">🌿</div><div class="char-label">Подручје</div><div class="char-val">ПИО Рајац</div></div><div class="char-item"><div class="char-icon">📊</div><div class="char-label">IUCN статус</div><div class="char-val">'.esc_html($iucn).'</div></div><div class="char-item"><div class="char-icon">🗺️</div><div class="char-label">Статус</div><div class="char-val">'.esc_html($status_rajac_lbl).'</div></div>';

    // Феносезонски календар
    $ak_arr  = $ak_str ? array_map('intval',explode(',',$ak_str)) : [];
    $pa_arr  = $pa_str ? array_map('intval',explode(',',$pa_str)) : [];
    $ml_arr  = $ml_str ? array_map('intval',explode(',',$ml_str)) : [];
    $hi_arr  = $hi_str ? array_map('intval',explode(',',$hi_str)) : [];
    $mes     = ['Јан','Феб','Мар','Апр','Мај','Јун','Јул','Авг','Сеп','Окт','Нов','Дец'];
    $bars    = '';
    foreach($mes as $i=>$m){
        $n=$i+1;
        if(in_array($n,$hi_arr))      $c='hib-bar';
        elseif(in_array($n,$pa_arr))  $c='par-bar';
        elseif(in_array($n,$ml_arr))  $c='ml-bar';
        elseif(in_array($n,$ak_arr))  $c='ak-bar';
        else                          $c='';
        $bars.='<div class="month"><div class="month-bar '.$c.'"></div><div class="month-lbl">'.$m.'</div></div>';
    }
    $calendar = '<div class="activity-card"><div class="card-title"><span>🗓</span> Феносезонски календар на Рајцу</div><div class="months">'.$bars.'</div><div class="months-legend"><span class="legend-item"><span class="legend-dot ak-dot"></span>Активна</span><span class="legend-item"><span class="legend-dot par-dot"></span>Парење</span><span class="legend-item"><span class="legend-dot ml-dot"></span>Младунци</span><span class="legend-item"><span class="legend-dot hib-dot"></span>Хибернација</span></div></div>';

    // Заштитни статус
    if($is_threat)
        $sb = '<div class="status-block"><div class="status-icon">⚠️</div><div class="status-text"><h4 style="color:#991b1b">'.esc_html($iucn).' (IUCN) — '.esc_html($iucn_lbl).'</h4><p style="color:#7f1d1d">'.wp_kses_post($st_zast).'</p></div></div>';
    else
        $sb = '<div class="status-block" style="background:linear-gradient(135deg,#f0fdf4,#f4fcf0);border-color:#bbf7d0"><div class="status-icon">✅</div><div class="status-text"><h4 style="color:#166534">LC (IUCN) — '.esc_html($iucn_lbl).'</h4><p style="color:#14532d">'.wp_kses_post($st_zast).'</p></div></div>';

    // Звуци блок
    $zvuci_html = '';
    if ( $z1n || $z2n ) {
        $zvuci_html .= '<div class="card"><div class="card-title"><span>🔊</span> Гласање и звуци</div><div class="sounds-grid">';
        if($z1n) $zvuci_html .= '<div class="sound-card"><h4>'.$z1n.'</h4><p>'.wp_kses_post($z1o).'</p></div>';
        if($z2n) $zvuci_html .= '<div class="sound-card"><h4>'.$z2n.'</h4><p>'.wp_kses_post($z2o).'</p></div>';
        $zvuci_html .= '</div>';
        if($xc) $zvuci_html .= '<a href="'.esc_url($xc).'" target="_blank" rel="noopener" class="app-link">🔗 Послушај гласање на интернету →</a>';
        $zvuci_html .= '</div>';
    }

    $ph = function($key,$msg='') use ($f) {
        return ($f[$key] ?? '') ?: '<p><em>'.($msg ?: 'Садржај за ову секцију биће ускоро додат.').'</em></p>';
    };

    // Табови
    $tabs = [
        ['key'=>'opis',         'icon'=>'🔍', 'lbl'=>'Опис'],
        ['key'=>'staniste',     'icon'=>'🌿', 'lbl'=>'Станиште'],
        ['key'=>'biologija',    'icon'=>'🔬', 'lbl'=>'Биологија'],
        ['key'=>'ishrana',      'icon'=>'🍃', 'lbl'=>'Исхрана'],
        ['key'=>'razmnozenost', 'icon'=>'🌸', 'lbl'=>'Размножавање'],
        ['key'=>'zastita',      'icon'=>'🛡', 'lbl'=>'Заштита'],
        ['key'=>'tragovi',      'icon'=>'🐾', 'lbl'=>'Трагови'],
    ];

    $tb = ''; $pn = '';
    foreach($tabs as $i=>$t) {
        $ac = $i===0?'active':'';
        $tb .= '<button class="tab '.$ac.'" data-tab="'.$t['key'].'">'.$t['icon'].' '.$t['lbl'].'</button>';

        if($t['key']==='opis') {
            $body  = '<div class="char-grid">'.$chars.'</div>';
            $body .= '<div class="card"><div class="card-title"><span>🔍</span> Морфолошки опис</div><div class="card-body">'.$ph('opis').'</div></div>';
            $body .= '<div class="card"><div class="card-title"><span>🛡</span> Заштитни статус</div><div class="conv-grid">';
            $body .= '<div class="conv-badge"><strong>🏛 Закон о заштити природе</strong><span>Заштићена дивља врста</span></div>';
            $body .= '<div class="conv-badge"><strong>🌍 IUCN</strong><span>'.esc_html($iucn).' — '.esc_html($iucn_lbl).'</span></div>';
            $body .= '<div class="conv-badge"><strong>📜 Бернска конвенција</strong><span>Анекс III</span></div>';
            $body .= '<div class="conv-badge"><strong>✅ Статус на Рајцу</strong><span>'.esc_html($status_rajac_lbl).'</span></div>';
            $body .= '</div></div>';
        } elseif($t['key']==='staniste') {
            $body  = '<div class="card"><div class="card-title"><span>🌿</span> Станиште и распрострањење</div><div class="card-body">'.$ph('staniste').'</div></div>';
            $body .= $calendar;
        } elseif($t['key']==='biologija') {
            $body  = '<div class="card"><div class="card-title"><span>🔬</span> Биологија</div><div class="card-body">'.$ph('biologija').'</div></div>';
            $body .= $zvuci_html;
        } elseif($t['key']==='tragovi') {
            $body  = '<div class="eco-card"><div class="card-title"><span>🐾</span> Трагови и знаци присуства</div><div class="card-body">'.$ph('tragovi','Опис трагова биће ускоро додат.').'</div></div>';
            $body .= '<div class="app-block"><div class="app-block-title">📡 Пријава налаза</div><div class="app-block-desc">Видели сте или чули ову врсту на Рајцу? Пријавите налаз:</div><div class="app-btns">';
            $body .= '<a href="https://www.inaturalist.org/taxa/'.urlencode($latin).'" target="_blank" class="app-btn app-btn-inaturalist"><span>🌿</span><div><strong>iNaturalist</strong><small>Пријави опажање</small></div></a>';
            $body .= '<a href="https://gbif.org/species/search?q='.urlencode($latin).'" target="_blank" class="app-btn app-btn-gbif"><span>🗺️</span><div><strong>GBIF</strong><small>Глобална дистрибуција</small></div></a>';
            $body .= '</div></div>';
        } elseif($t['key']==='zastita') {
            $body  = '<div class="card" style="border-color:#bbf7d0"><div class="card-title"><span>🛡</span> Заштита и статус</div><div class="card-body">'.$ph('zastita').'</div></div>';
        } else {
            $body  = '<div class="card"><div class="card-title"><span>'.$t['icon'].'</span> '.esc_html($t['lbl']).'</div><div class="card-body">'.$ph($t['key']).'</div></div>';
        }
        $pn .= '<div class="tab-panel '.$ac.'" id="tab-'.$t['key'].'">'.$body.'</div>';
    }

    ob_start(); ?>
<div class="sisari-hero">
  <div class="sisari-hero-inner">
    <div class="sisari-hero-text">
      <a href="<?=esc_url($arch)?>" class="back-link">← Сви сисари</a>
      <h1 class="sisari-title"><?=esc_html($naziv)?></h1>
      <div class="sisari-latin"><?=esc_html($latin)?></div>
      <span class="iucn-pill <?=$iucn_cls?>">IUCN <?=esc_html($iucn)?></span>
      <?php if($red_val): ?>
      <span class="red-pill"><?=esc_html($red_val)?></span>
      <?php endif; ?>
    </div>
    <div class="sisari-photo"><div class="sisari-photo-box"><?=$photo?></div></div>
  </div>
  <div class="sisari-infobar"><div class="sisari-infobar-inner">
    <div class="info-cell"><div class="info-label">Дужина тела</div><div class="info-val"><?=esc_html($length?:'—')?></div></div>
    <div class="info-cell"><div class="info-label">Тежина</div><div class="info-val"><?=esc_html($weight?:'—')?></div></div>
    <div class="info-cell"><div class="info-label">Породица</div><div class="info-val"><?=esc_html($porod?:'—')?></div></div>
    <div class="info-cell"><div class="info-label">IUCN статус</div><div class="info-val" style="color:<?=$iucn_col?>"><?=esc_html($iucn)?> — <?=esc_html($iucn_lbl)?></div></div>
  </div></div>
</div>
<div class="main">
  <?=$sb?>
  <div class="tabs" id="mainTabs"><?=$tb?></div>
  <?=$pn?>
</div>
<nav class="sisari-nav">
  <?=$prev?>
  <a href="<?=esc_url($arch)?>" class="nav-sisari nav-all">🐾 Сви сисари</a>
  <?=$next?>
</nav>
    <?php
    return ob_get_clean();
}

// ══════════════════════════════════════════
// 5. CSS + JS
// ══════════════════════════════════════════
add_action('wp_head','sisari1_head');
function sisari1_head(){
    if(!is_singular('sisari') && !is_post_type_archive('sisari')) return;
    echo '<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,300;1,400&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
    echo '<style id="sisari1-css">
:root{--forest:#2d5a27;--forest-dark:#1a3a15;--forest-mid:#3a6e30;--gold:#a07a2a;--gold-light:#c8a84b;--cream:#f7f4ef;--text:#1a1a1a;--muted:#6b7280;--border:#ddd8cc;--sage:#4a8c5c;--sage-bg:#edf7f0;--sage-border:#b8dfc5}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:\'Outfit\',sans-serif;background:var(--cream)!important;color:var(--text);font-size:15px;line-height:1.7}
a{text-decoration:none;color:inherit}
.sisari-hero{background:linear-gradient(160deg,var(--forest-dark) 0%,var(--forest-mid) 55%,#3a5a1e 100%);padding:36px 24px 0}
.sisari-hero-inner{max-width:860px;margin:0 auto;display:grid;grid-template-columns:1fr 300px;gap:32px;align-items:end}
.sisari-hero-text{padding-bottom:32px}
.back-link{display:inline-flex;align-items:center;gap:6px;color:rgba(255,255,255,.7);font-size:13px;font-weight:500;margin-bottom:18px;transition:color .2s}
.back-link:hover{color:var(--gold-light)}
.sisari-title{font-family:\'Outfit\',sans-serif;font-size:clamp(1.8rem,4.5vw,2.7rem);font-weight:700;color:#fff;line-height:1.1;margin-bottom:6px;letter-spacing:-.02em}
.sisari-latin{font-family:\'Cormorant Garamond\',serif;font-style:italic;font-size:17px;color:var(--gold-light);margin-bottom:18px;font-weight:400}
.iucn-pill{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.05em;padding:6px 14px;border-radius:7px;margin-right:6px}
.iucn-lc{background:#1e4a20;color:#86efac}
.iucn-vu{background:#7c3d12;color:#fdba74}
.red-pill{display:inline-block;font-size:11px;font-weight:600;padding:5px 12px;border-radius:7px;background:rgba(255,255,255,.12);color:rgba(255,255,255,.85);border:1px solid rgba(255,255,255,.2)}
.sisari-photo{align-self:end}
.sisari-photo-box{width:100%;height:260px;border-radius:12px 12px 0 0;overflow:hidden;background:linear-gradient(160deg,var(--forest-mid),#3a5a1e)}
.sisari-photo-box img{width:100%;height:100%;object-fit:cover;object-position:center 20%;display:block}
.sisari-infobar{background:var(--forest-dark);border-top:1px solid rgba(255,255,255,.08)}
.sisari-infobar-inner{max-width:860px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr)}
.info-cell{padding:14px 20px;border-right:1px solid rgba(255,255,255,.08)}
.info-cell:last-child{border-right:none}
.info-label{font-size:10px;text-transform:uppercase;letter-spacing:1.5px;color:rgba(255,255,255,.4);font-weight:600;margin-bottom:4px}
.info-val{font-size:13px;color:rgba(255,255,255,.85);font-weight:600}
.main{max-width:860px;margin:0 auto;padding:28px 16px 20px}
.status-block{background:linear-gradient(135deg,#fef2f2,#fff7ed);border:1px solid #fecaca;border-radius:12px;padding:18px 20px;margin-bottom:22px;display:flex;gap:14px;align-items:flex-start}
.status-icon{font-size:26px;flex-shrink:0}
.status-text h4{font-size:13px;font-weight:700;margin-bottom:6px}
.status-text p{font-size:13px;line-height:1.75}
.tabs{display:flex;border-bottom:2px solid var(--border);overflow-x:auto;scrollbar-width:none}
.tabs::-webkit-scrollbar{display:none}
.tab{padding:11px 17px;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;border:none;background:none;white-space:nowrap;border-bottom:2.5px solid transparent;margin-bottom:-2px;transition:all .2s;display:flex;align-items:center;gap:5px}
.tab:hover{color:var(--forest)}
.tab.active{color:var(--forest);border-bottom-color:var(--forest)}
.tab-panel{display:none;padding:24px 0}
.tab-panel.active{display:block}
.card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:14px}
.card-title{font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:7px}
.card-body{font-size:14px;color:#374151;line-height:1.85}
.card-body p{margin-bottom:12px}
.card-body p:last-child{margin-bottom:0}
.card-body ul,.card-body ol{margin:8px 0 12px 20px}
.card-body li{margin-bottom:5px}
.card-body strong{color:var(--text)}
.eco-card{background:linear-gradient(135deg,var(--sage-bg),#f5fbf7);border:1px solid var(--sage-border);border-left:4px solid var(--sage);border-radius:12px;padding:20px;margin-bottom:14px}
.eco-card .card-title{color:#166534}
.eco-card .card-body{color:#155a30;font-size:14px;line-height:1.85}
.eco-card .card-body p{margin-bottom:12px}
.char-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:22px}
.char-item{background:#fff;border:1px solid var(--border);border-radius:10px;padding:16px 14px;text-align:center;transition:all .2s}
.char-item:hover{border-color:var(--gold);transform:translateY(-2px);box-shadow:0 4px 14px rgba(160,122,42,.12)}
.char-icon{font-size:22px;margin-bottom:7px}
.char-label{font-size:9px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-bottom:5px}
.char-val{font-size:14px;font-weight:700;color:var(--text)}
.conv-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.conv-badge{background:#fff;border:1px solid var(--border);border-radius:8px;padding:12px 14px}
.conv-badge strong{display:block;color:var(--forest);font-size:12px;margin-bottom:2px;font-weight:700}
.conv-badge span{color:var(--muted);font-size:12px}
.activity-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:14px}
.months{display:grid;grid-template-columns:repeat(12,1fr);gap:4px;margin-bottom:8px}
.month{text-align:center}
.month-bar{height:36px;border-radius:5px;margin-bottom:4px;background:#e5e7eb}
.month-bar.ak-bar{background:var(--forest)}
.month-bar.par-bar{background:#c4500a}
.month-bar.ml-bar{background:#e8a020}
.month-bar.hib-bar{background:#7a9ab0}
.month-lbl{font-size:9px;color:var(--muted);font-weight:600}
.months-legend{display:flex;gap:16px;font-size:12px;color:var(--muted);margin-top:6px;flex-wrap:wrap}
.legend-item{display:flex;align-items:center;gap:6px}
.legend-dot{width:12px;height:12px;border-radius:3px;display:inline-block}
.ak-dot{background:var(--forest)}
.par-dot{background:#c4500a}
.ml-dot{background:#e8a020}
.hib-dot{background:#7a9ab0}
.sounds-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px}
.sound-card{background:var(--sage-bg);border:1px solid var(--sage-border);border-radius:8px;padding:14px 16px}
.sound-card h4{font-size:13px;font-weight:700;color:var(--forest-dark);margin-bottom:6px}
.sound-card p{font-size:13px;line-height:1.65;color:#374151}
.app-link{display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:600;color:var(--forest);padding:8px 14px;border:1.5px solid var(--forest);border-radius:6px;transition:all .2s;margin-top:6px}
.app-link:hover{background:var(--forest);color:#fff}
.app-block{background:linear-gradient(135deg,var(--forest-dark),var(--forest-mid));border-radius:12px;padding:20px;margin-bottom:14px}
.app-block-title{color:var(--gold-light);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;margin-bottom:5px}
.app-block-desc{color:rgba(255,255,255,.55);font-size:13px;margin-bottom:16px}
.app-btns{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.app-btn{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;font-size:13px;transition:all .2s}
.app-btn:hover{transform:translateY(-2px)}
.app-btn-inaturalist{background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.15)}
.app-btn-gbif{background:rgba(200,168,75,.12);color:var(--gold-light);border:1px solid rgba(200,168,75,.25)}
.app-btn span:first-child{font-size:22px}
.app-btn div strong{display:block}
.app-btn div small{font-size:11px;opacity:.65}
.sisari-nav{display:flex;align-items:center;justify-content:space-between;max-width:860px;margin:32px auto 0;padding:0 16px 32px;gap:12px}
.nav-sisari{font-size:13px;font-weight:600;padding:10px 18px;border-radius:8px;transition:all .2s}
.nav-prev,.nav-next{color:var(--forest);background:#f0f4f0;border:1px solid #c8d8c4}
.nav-prev:hover,.nav-next:hover{background:var(--forest);color:#fff}
.nav-all{color:#fff;background:var(--forest);border:1px solid var(--forest);text-align:center}
.nav-all:hover{background:var(--forest-dark)}
.nav-disabled{visibility:hidden;pointer-events:none}
.entry-title,.page-title,h1.entry-title,.post-title,.entry-header,.post-header,.article-header{display:none!important}
.site-header,.ct-header,header.site-header,#site-header,#masthead,.main-navigation,#main-navigation,.header-wrap,.ct-header-builder,.site-branding,.header-image,.navbar,.nav-menu,.ct-menu,.primary-navigation,nav[role=navigation]{display:none!important}
.site-content,#content,.content-area,main.site-main,#primary{padding-top:0!important;margin-top:0!important}
body{padding-top:0!important;margin-top:0!important}
html.admin-bar body{margin-top:32px!important}
html.admin-bar .sisari-hero{padding-top:36px!important}
.entry-content,.ct-post-content,.post-content{padding:0!important;margin:0!important;max-width:100%!important}
.site-info,.footer-info,.footer-credit,.powered-by,.footer-branding,.site-footer__bottom,.footer-bottom,footer.site-footer,footer#colophon,#colophon,.footer-widgets,[class*="footer-copyright"],[class*="footer-credit"],[class*="powered-by"]{display:none!important}
footer.sisari1-footer{display:block!important}
@media(max-width:640px){.sisari-hero-inner{grid-template-columns:1fr}.sisari-photo{display:none}.sisari-infobar-inner{grid-template-columns:1fr 1fr}.char-grid{grid-template-columns:repeat(2,1fr)}.app-btns,.conv-grid,.sounds-grid{grid-template-columns:1fr}.sisari-nav{flex-direction:column;align-items:stretch;text-align:center}}
</style>';
}

add_action('wp_footer','sisari1_footer_js');
function sisari1_footer_js(){
    if(!is_singular('sisari')) return;
    ?>
    <script>
    (function(){
        function initTabs(){
            var tabs = document.querySelectorAll('#mainTabs .tab');
            var panels = document.querySelectorAll('.tab-panel');
            if(!tabs.length) return;
            panels.forEach(function(p){ p.style.display = p.classList.contains('active') ? 'block' : 'none'; });
            tabs.forEach(function(t){
                t.addEventListener('click', function(){
                    tabs.forEach(function(x){ x.classList.remove('active'); });
                    panels.forEach(function(x){ x.classList.remove('active'); x.style.display='none'; });
                    this.classList.add('active');
                    var p = document.getElementById('tab-'+this.dataset.tab);
                    if(p){ p.classList.add('active'); p.style.display='block'; }
                });
            });
        }
        if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded',initTabs); }
        else { initTabs(); }
    })();
    </script>
    <footer class="sisari1-footer" style="background:var(--forest-dark,#1a3a15);padding:20px 16px;margin-top:0;display:block!important">
      <div style="max-width:860px;margin:0 auto;text-align:center;color:rgba(255,255,255,.6);font-size:13px">
        Copyright &copy; <?=date('Y')?> &mdash; Предео изузетних одлика „Рајац" · Туристичка организација општине Љиг
      </div>
    </footer>
    <?php
}

// ══════════════════════════════════════════
// 6. АКТИВАЦИЈА — аутоматско креирање постова
// ══════════════════════════════════════════
register_activation_hook(__FILE__,'sisari1_aktivacija');
function sisari1_aktivacija(){
    sisari1_register_cpt();
    sisari1_kreiraj_postove();
    flush_rewrite_rules();
}

function sisari1_kreiraj_postove(){
    foreach(sisari1_lista() as $p){
        $ex = get_posts(['post_type'=>'sisari','name'=>$p['slug'],'post_status'=>'any','numberposts'=>1]);
        if($ex) continue;
        wp_insert_post([
            'post_title'   => $p['naziv'],
            'post_name'    => $p['slug'],
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'sisari',
            'meta_input'   => [
                '_sisari_latin'        => $p['latin'],
                '_sisari_iucn'         => $p['iucn'],
                '_sisari_red'          => $p['red'],
                '_sisari_porodica'     => $p['porodica'],
                '_sisari_kategorija'   => $p['kategorija'],
                '_sisari_status_rajac' => $p['status'],
                '_sisari_foto_url'     => $p['foto'],
            ]
        ]);
    }
}

function sisari1_lista(){
    // Koristimo PROVERENE URL-ove sa Wikimedia — isti kao u originalnom HTML katalogu
    return [
        // ── ПАПКАРИ ──────────────────────────────────────────────
        ['naziv'=>'Срна',         'latin'=>'Capreolus capreolus','slug'=>'capreolus-capreolus','iucn'=>'LC','red'=>'Artiodactyla','porodica'=>'Cervidae',  'kategorija'=>'papkari',   'status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Capreolus_capreolus_2.jpg'],
        ['naziv'=>'Дивља свиња', 'latin'=>'Sus scrofa',         'slug'=>'sus-scrofa',         'iucn'=>'LC','red'=>'Artiodactyla','porodica'=>'Suidae',    'kategorija'=>'papkari',   'status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Sus_scrofa_scrofa.jpg'],
        // ── ЗЕЧЕВИ ───────────────────────────────────────────────
        ['naziv'=>'Дивљи зец',   'latin'=>'Lepus europaeus',    'slug'=>'lepus-europaeus',    'iucn'=>'LC','red'=>'Lagomorpha',  'porodica'=>'Leporidae', 'kategorija'=>'zecevi',    'status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Feldhase%2C_Lepus_europaeus_3a.JPG'],
        // ── ЗВЕРИ ────────────────────────────────────────────────
        ['naziv'=>'Лисица',       'latin'=>'Vulpes vulpes',      'slug'=>'vulpes-vulpes',      'iucn'=>'LC','red'=>'Carnivora',  'porodica'=>'Canidae',    'kategorija'=>'zveri',     'status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Red_Fox_(Vulpes_vulpes)_(4).jpg'],
        ['naziv'=>'Шакал',        'latin'=>'Canis aureus',       'slug'=>'canis-aureus',       'iucn'=>'LC','red'=>'Carnivora',  'porodica'=>'Canidae',    'kategorija'=>'zveri',     'status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Canis_aureus_-_golden_jackal.jpg'],
        ['naziv'=>'Јазавац',      'latin'=>'Meles meles',        'slug'=>'meles-meles',        'iucn'=>'LC','red'=>'Carnivora',  'porodica'=>'Mustelidae', 'kategorija'=>'zveri',     'status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Meles_meles_Усть-Каменогорск.jpg'],
        ['naziv'=>'Дивља мачка', 'latin'=>'Felis silvestris',   'slug'=>'felis-silvestris',   'iucn'=>'NT','red'=>'Carnivora',  'porodica'=>'Felidae',    'kategorija'=>'zveri',     'status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Felis_silvestris_silvestris.jpg'],
        ['naziv'=>'Куна белица', 'latin'=>'Martes foina',        'slug'=>'martes-foina',       'iucn'=>'LC','red'=>'Carnivora',  'porodica'=>'Mustelidae', 'kategorija'=>'zveri',     'status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Martes_foina_ct.jpg'],
        ['naziv'=>'Куна златица','latin'=>'Martes martes',       'slug'=>'martes-martes',      'iucn'=>'LC','red'=>'Carnivora',  'porodica'=>'Mustelidae', 'kategorija'=>'zveri',     'status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Martes_martes-08-WA-Jochen.jpg'],
        ['naziv'=>'Мрки твор',   'latin'=>'Mustela putorius',   'slug'=>'mustela-putorius',   'iucn'=>'VU','red'=>'Carnivora',  'porodica'=>'Mustelidae', 'kategorija'=>'zveri',     'status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Mustela_putorius.jpg'],
        ['naziv'=>'Риђа ласица', 'latin'=>'Mustela nivalis',    'slug'=>'mustela-nivalis',    'iucn'=>'LC','red'=>'Carnivora',  'porodica'=>'Mustelidae', 'kategorija'=>'zveri',     'status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Mustela_nivalis_-British_Wildlife_Centre-_edit.jpg'],
        // ── ГЛОДАРИ ──────────────────────────────────────────────
        ['naziv'=>'Веверица',           'latin'=>'Sciurus vulgaris',        'slug'=>'sciurus-vulgaris',        'iucn'=>'LC','red'=>'Rodentia','porodica'=>'Sciuridae',  'kategorija'=>'glodari', 'status'=>'potvrdjen', 'foto'=>'SISARI_IMG_URL/Sciurus_vulgaris_Narew.jpg'],
        ['naziv'=>'Сиви пух',           'latin'=>'Glis glis',               'slug'=>'glis-glis',               'iucn'=>'LC','red'=>'Rodentia','porodica'=>'Gliridae',   'kategorija'=>'glodari', 'status'=>'potvrdjen', 'foto'=>'SISARI_IMG_URL/Glis_glis_03.jpg'],
        ['naziv'=>'Пух лешникар',       'latin'=>'Muscardinus avellanarius','slug'=>'muscardinus-avellanarius','iucn'=>'LC','red'=>'Rodentia','porodica'=>'Gliridae',   'kategorija'=>'glodari', 'status'=>'potvrdjen', 'foto'=>'SISARI_IMG_URL/Muscardinus_avellanarius.jpg'],
        ['naziv'=>'Слепо куче',         'latin'=>'Nannospalax leucodon',    'slug'=>'nannospalax-leucodon',    'iucn'=>'VU','red'=>'Rodentia','porodica'=>'Spalacidae', 'kategorija'=>'glodari', 'status'=>'potvrdjen', 'foto'=>'SISARI_IMG_URL/Nannospalax_leucodon.jpg'],
        ['naziv'=>'Жутогрли миш',       'latin'=>'Apodemus flavicollis',    'slug'=>'apodemus-flavicollis',    'iucn'=>'LC','red'=>'Rodentia','porodica'=>'Muridae',    'kategorija'=>'glodari', 'status'=>'potvrdjen', 'foto'=>'SISARI_IMG_URL/Apodemus_flavicollis.jpg'],
        ['naziv'=>'Шумски миш',         'latin'=>'Apodemus sylvaticus',     'slug'=>'apodemus-sylvaticus',     'iucn'=>'LC','red'=>'Rodentia','porodica'=>'Muridae',    'kategorija'=>'glodari', 'status'=>'potvrdjen', 'foto'=>'SISARI_IMG_URL/Apodemus_sylvaticus.jpg'],
        ['naziv'=>'Пругасти миш',       'latin'=>'Apodemus agrarius',       'slug'=>'apodemus-agrarius',       'iucn'=>'LC','red'=>'Rodentia','porodica'=>'Muridae',    'kategorija'=>'glodari', 'status'=>'potvrdjen', 'foto'=>'SISARI_IMG_URL/Apodemus_agrarius.jpg'],
        ['naziv'=>'Риђа волухарица',    'latin'=>'Myodes glareolus',        'slug'=>'myodes-glareolus',        'iucn'=>'LC','red'=>'Rodentia','porodica'=>'Cricetidae', 'kategorija'=>'glodari', 'status'=>'potvrdjen', 'foto'=>'SISARI_IMG_URL/Myodes_glareolus.jpg'],
        ['naziv'=>'Подземна волухарица','latin'=>'Microtus subterraneus',   'slug'=>'microtus-subterraneus',   'iucn'=>'LC','red'=>'Rodentia','porodica'=>'Cricetidae', 'kategorija'=>'glodari', 'status'=>'potvrdjen', 'foto'=>'SISARI_IMG_URL/Microtus_subterraneus.jpg'],
        ['naziv'=>'Пољска волухарица',  'latin'=>'Microtus arvalis',        'slug'=>'microtus-arvalis',        'iucn'=>'LC','red'=>'Rodentia','porodica'=>'Cricetidae', 'kategorija'=>'glodari', 'status'=>'potvrdjen', 'foto'=>'SISARI_IMG_URL/Microtus_arvalis.jpg'],
        // ── БУБОЈЕДИ ─────────────────────────────────────────────
        ['naziv'=>'Јеж',              'latin'=>'Erinaceus roumanicus','slug'=>'erinaceus-roumanicus','iucn'=>'LC','red'=>'Eulipotyphla','porodica'=>'Erinaceidae','kategorija'=>'bubojedи','status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Erinaceus_roumanicus.jpg'],
        ['naziv'=>'Кртица',           'latin'=>'Talpa europaea',      'slug'=>'talpa-europaea',      'iucn'=>'LC','red'=>'Eulipotyphla','porodica'=>'Talpidae',    'kategorija'=>'bubojedи','status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Talpa_europaea.jpg'],
        ['naziv'=>'Шумска ровчица',   'latin'=>'Sorex araneus',       'slug'=>'sorex-araneus',       'iucn'=>'LC','red'=>'Eulipotyphla','porodica'=>'Soricidae',   'kategorija'=>'bubojedи','status'=>'potvrdjen',    'foto'=>'SISARI_IMG_URL/Sorex_araneus.jpg'],
        // ── ПОТЕНЦИЈАЛНЕ / СПОРАДИЧНЕ ЗВЕРИ ─────────────────────────
        ['naziv'=>'Вук',         'latin'=>'Canis lupus',   'slug'=>'canis-lupus',   'iucn'=>'LC','red'=>'Carnivora','porodica'=>'Canidae',    'kategorija'=>'zveri',   'status'=>'sporadican','foto'=>'SISARI_IMG_URL/Canis_lupus_laying.jpg'],
        ['naziv'=>'Мрки медвед','latin'=>'Ursus arctos',   'slug'=>'ursus-arctos',  'iucn'=>'LC','red'=>'Carnivora','porodica'=>'Ursidae',    'kategorija'=>'zveri',   'status'=>'sporadican','foto'=>'SISARI_IMG_URL/Brown_bear.jpg'],
        ['naziv'=>'Видра',       'latin'=>'Lutra lutra',   'slug'=>'lutra-lutra',   'iucn'=>'NT','red'=>'Carnivora','porodica'=>'Mustelidae', 'kategorija'=>'zveri',   'status'=>'sporadican','foto'=>'SISARI_IMG_URL/Lutra_lutra.jpg'],
        // ── ПОТЕНЦИЈАЛНЕ РОВЧИЦЕ (Бубоједи) ─────────────────────────
        ['naziv'=>'Мала ровчица',      'latin'=>'Sorex minutus',       'slug'=>'sorex-minutus',       'iucn'=>'LC','red'=>'Eulipotyphla','porodica'=>'Soricidae','kategorija'=>'bubojedи','status'=>'sporadican','foto'=>'SISARI_IMG_URL/Sorex_minutus.jpg'],
        ['naziv'=>'Водена ровчица',    'latin'=>'Neomys fodiens',      'slug'=>'neomys-fodiens',      'iucn'=>'LC','red'=>'Eulipotyphla','porodica'=>'Soricidae','kategorija'=>'bubojedи','status'=>'sporadican','foto'=>'SISARI_IMG_URL/Neomys_fodiens.jpg'],
        ['naziv'=>'Баштенска ровчица', 'latin'=>'Crocidura suaveolens','slug'=>'crocidura-suaveolens','iucn'=>'LC','red'=>'Eulipotyphla','porodica'=>'Soricidae','kategorija'=>'bubojedи','status'=>'sporadican','foto'=>'SISARI_IMG_URL/Crocidura_suaveolens.jpg'],
        ['naziv'=>'Пољска ровчица',    'latin'=>'Crocidura leucodon',  'slug'=>'crocidura-leucodon',  'iucn'=>'LC','red'=>'Eulipotyphla','porodica'=>'Soricidae','kategorija'=>'bubojedи','status'=>'sporadican','foto'=>'SISARI_IMG_URL/Crocidura_leucodon.jpg'],
        // ── СЛЕПИ МИШЕВИ ─────────────────────────────────────────
        ['naziv'=>'Велики потковичар',        'latin'=>'Rhinolophus ferrumequinum','slug'=>'rhinolophus-ferrumequinum','iucn'=>'LC','red'=>'Chiroptera','porodica'=>'Rhinolophidae',    'kategorija'=>'slepi_misevi','status'=>'potvrdjen','foto'=>'SISARI_IMG_URL/Rhinolophus_ferrumequinum.jpg'],
        ['naziv'=>'Мали потковичар',          'latin'=>'Rhinolophus hipposideros','slug'=>'rhinolophus-hipposideros', 'iucn'=>'LC','red'=>'Chiroptera','porodica'=>'Rhinolophidae',    'kategorija'=>'slepi_misevi','status'=>'potvrdjen','foto'=>'SISARI_IMG_URL/Rhinolophus_hipposideros_-_Gzenyme.jpg'],
        ['naziv'=>'Велики мишоуши вечерњак', 'latin'=>'Myotis myotis',            'slug'=>'myotis-myotis',            'iucn'=>'LC','red'=>'Chiroptera','porodica'=>'Vespertilionidae', 'kategorija'=>'slepi_misevi','status'=>'potvrdjen','foto'=>'SISARI_IMG_URL/Myotis_myotis_head.jpg'],
        ['naziv'=>'Средњи ноћник',            'latin'=>'Nyctalus noctula',         'slug'=>'nyctalus-noctula',         'iucn'=>'LC','red'=>'Chiroptera','porodica'=>'Vespertilionidae', 'kategorija'=>'slepi_misevi','status'=>'potvrdjen','foto'=>'SISARI_IMG_URL/Nyctalus_noctula_01.jpg'],
    ];
}

// ══════════════════════════════════════════
// 7. СТАТИЧКЕ HTML СТРАНИЦЕ (Јеж, Лисица, Зец)
//    Ако у species-pages/ постоји HTML фајл за
//    тај slug — служи га директно уместо динамичке
//    генерације из мета поља.
// ══════════════════════════════════════════
add_action('template_redirect','sisari1_static_html_redirect');
function sisari1_static_html_redirect(){
    if( ! is_singular('sisari') ) return;
    $slug = get_post_field('post_name', get_the_ID());
    $file = plugin_dir_path(__FILE__) . 'species-pages/' . sanitize_file_name($slug) . '.html';
    if( ! file_exists($file) ) return;

    // Serve the static HTML file with local image URL substitution
    status_header(200);
    header('Content-Type: text/html; charset=UTF-8');
    $html = file_get_contents($file);
    $img_base = plugins_url('images/', __FILE__);
    $html = str_replace('SISARI_IMG_URL/', $img_base, $html);
    echo $html;
    exit;
}

// ══════════════════════════════════════════
// 8. ARCHIVE СТРАНИЦА — потпуни каталог
// ══════════════════════════════════════════

// Intercept archive template and output full custom page
add_action('template_redirect','sisari1_archive_redirect');
function sisari1_archive_redirect(){
    if(!is_post_type_archive('sisari')) return;
    sisari1_render_archive();
    exit;
}



function sisari1_commons_original_filename($url){
    $url = trim((string)$url);
    if($url === '') return '';
    if(preg_match('~commons\.wikimedia\.org/wiki/Special:FilePath/([^?]+)~u', $url, $m)){
        return rawurldecode($m[1]);
    }
    if(preg_match('~upload\.wikimedia\.org/wikipedia/commons/thumb/[^/]+/[^/]+/([^/]+)/[^/?]+$~u', $url, $m)){
        return rawurldecode($m[1]);
    }
    if(preg_match('~upload\.wikimedia\.org/wikipedia/commons/[^/]+/[^/]+/([^/?]+)$~u', $url, $m)){
        return rawurldecode($m[1]);
    }
    return '';
}

function sisari1_normalize_commons_image_url($url, $width = 1200){
    $url = trim((string)$url);
    if($url === '') return '';
    // Local image — resolve SISARI_IMG_URL/ to plugin images/ folder
    if(strpos($url, 'SISARI_IMG_URL/') === 0){
        return plugins_url('images/' . substr($url, 15), __FILE__);
    }
    $filename = sisari1_commons_original_filename($url);
    if($filename !== ''){
        return plugins_url('images/' . $filename, __FILE__);
    }
    return $url;
}

function sisari1_archive_card_image_url($url){
    return sisari1_normalize_commons_image_url($url, 900);
}

function sisari1_get_page_hero_map(){
    static $cache = null;
    if($cache !== null) return $cache;
    $cache = [];
    $dir = plugin_dir_path(__FILE__) . 'species-pages/';
    if(!is_dir($dir)) return $cache;
    foreach(glob($dir . '*.html') as $file){
        $slug = basename($file, '.html');
        $html = @file_get_contents($file);
        if($html === false) continue;

        $hero = '';
        $fallback = '';
        $wrap = '';

        if(preg_match('~<div class="hero-image-wrap">(.*?)</div>~su', $html, $mwrap)){
            $wrap = $mwrap[1];
        }
        if($wrap !== ''){
            if(preg_match('~<img[^>]+src="([^"]+)"~su', $wrap, $m)){
                $hero = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            if(preg_match('~onerror="[^"]*this\.src=\'([^\']+)\'~su', $wrap, $m2)){
                $fallback = html_entity_decode(trim($m2[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        if($hero || $fallback){
            $cache[$slug] = [
                'src' => $hero,
                'fallback' => ($fallback && $fallback !== $hero) ? $fallback : '',
            ];
        }
    }
    return $cache;
}

function sisari1_render_archive(){
    // Dohvati sve postove i provjeri koji imaju sadržaj (published)
    $all_posts = get_posts([
        'post_type'      => 'sisari',
        'numberposts'    => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    // Indeks: slug => permalink (za linkove)
    $has_page = [];
    foreach($all_posts as $p){
        $has_page[$p->post_name] = get_permalink($p->ID);
    }
    $page_hero_map = sisari1_get_page_hero_map();

    // Statički podaci za katalog — isti red/slike kao u HTML fajlu
    $species_data = sisari1_lista();
    $by_slug = [];
    foreach($species_data as $s) $by_slug[$s['slug']] = $s;

    // Grupe
    $grupe = [
        'papkari' => [
            'icon'=>'🦌','naziv'=>'Папкари и зецолики','latin'=>'Artiodactyla · Lagomorpha',
            'count'=>'3 потврђене врсте',
            'opis'=>'Крупни биљождери — кључне врсте за екосистем Рајца. Срна и дивља свиња ловне су дивљачи под регулисаним режимом; зец је ситна ловна дивљач са опадајућим трендом популације у Европи.',
            'slugs'=>['capreolus-capreolus','sus-scrofa','lepus-europaeus']
        ],
        'zveri' => [
            'icon'=>'🦊','naziv'=>'Звери','latin'=>'Carnivora',
            'count'=>'8 потврђених врста',
            'opis'=>'Предатори и свеједери — регулатори популација ситних сисара, птица и папкара. Дивља мачка је строго заштићена реликтна врста; шакал је новодосељена врста у ширењу ка северу.',
            'slugs'=>['vulpes-vulpes','canis-aureus','meles-meles','felis-silvestris','martes-foina','martes-martes','mustela-putorius','mustela-nivalis']
        ],
        'glodari' => [
            'icon'=>'🐿','naziv'=>'Глодари','latin'=>'Rodentia',
            'count'=>'10 потврђених врста',
            'opis'=>'Најбројнија група по врстама — од веверице и пуха у крошњама, преко подземног слепог кучета, до ситних мишева и волухарица у трави. Пухови и веверица кључне су врсте шумских екосистема.',
            'slugs'=>['sciurus-vulgaris','glis-glis','muscardinus-avellanarius','nannospalax-leucodon','apodemus-flavicollis','apodemus-sylvaticus','apodemus-agrarius','myodes-glareolus','microtus-subterraneus','microtus-arvalis']
        ],
        'bubojedи' => [
            'icon'=>'🦔','naziv'=>'Бубоједи','latin'=>'Eulipotyphla',
            'count'=>'3 потврђене врсте',
            'opis'=>'Инсективорни сисари — јеж је строго заштићена и добро позната врста; кртица живи потпуно под земљом; шумска ровчица је ситни ноћни ловац инсеката.',
            'slugs'=>['erinaceus-roumanicus','talpa-europaea','sorex-araneus']
        ],
        'slepi_misevi' => [
            'icon'=>'🦇','naziv'=>'Слепи мишеви','latin'=>'Chiroptera',
            'count'=>'19 врста · све строго заштићене',
            'opis'=>'Рајац је изузетно богат хироптерофауном. Све врсте строго су заштићене Законом о заштити природе и Бернском конвенцијом.',
            'slugs'=>['rhinolophus-ferrumequinum','rhinolophus-hipposideros','myotis-myotis','nyctalus-noctula']
        ],
    ];

    // Tag mapiranje
    $iucn_tags = [
        'LC'=>'<span class="card-tag tag-iucn">IUCN LC</span>',
        'NT'=>'<span class="card-tag tag-iucn-nt">IUCN NT</span>',
        'VU'=>'<span class="card-tag tag-iucn-nt">IUCN VU</span>',
        'EN'=>'<span class="card-tag tag-iucn-nt">IUCN EN</span>',
    ];
    // Posebne oznake po vrsti
    $extra_tags = [
        'capreolus-capreolus'  => '<span class="card-tag tag-game">Ловна дивљач</span>',
        'sus-scrofa'           => '<span class="card-tag tag-game">Ловна дивљач</span>',
        'lepus-europaeus'      => '<span class="card-tag tag-game">Ловна дивљач</span>',
        'vulpes-vulpes'        => '<span class="card-tag tag-game">Ловна дивљач</span>',
        'canis-aureus'         => '<span class="card-tag tag-game">Ловна дивљач</span>',
        'meles-meles'          => '<span class="card-tag tag-protected">Заштићена</span>',
        'felis-silvestris'     => '<span class="card-tag tag-protected">Строго заштићена</span>',
        'martes-foina'         => '<span class="card-tag tag-game">Ловна дивљач</span>',
        'martes-martes'        => '<span class="card-tag tag-protected">Заштићена</span>',
        'mustela-putorius'     => '<span class="card-tag tag-game">Ловна дивљач</span>',
        'mustela-nivalis'      => '<span class="card-tag tag-protected">Заштићена</span>',
        'sciurus-vulgaris'     => '<span class="card-tag tag-protected">Заштићена</span>',
        'glis-glis'            => '<span class="card-tag tag-protected">Строго заштићена</span>',
        'muscardinus-avellanarius'=>'<span class="card-tag tag-protected">Строго заштићена</span>',
        'nannospalax-leucodon' => '<span class="card-tag tag-protected">Заштићена</span>',
        'erinaceus-roumanicus' => '<span class="card-tag tag-protected">Строго заштићена</span>',
        'talpa-europaea'       => '<span class="card-tag tag-protected">Заштићена</span>',
        'sorex-araneus'        => '<span class="card-tag tag-protected">Строго заштићена</span>',
        'rhinolophus-ferrumequinum'=>'<span class="card-tag tag-protected">Строго заштићена</span>',
        'rhinolophus-hipposideros' =>'<span class="card-tag tag-protected">Строго заштићена</span>',
        'myotis-myotis'        => '<span class="card-tag tag-protected">Строго заштићена</span>',
        'nyctalus-noctula'     => '<span class="card-tag tag-protected">Строго заштићена</span>',
    ];
    // Emoji fallback za vrste bez slike
    $emoji_fallback = [
        'nannospalax-leucodon'=>'🦫','apodemus-flavicollis'=>'🐭','apodemus-sylvaticus'=>'🐭',
        'apodemus-agrarius'=>'🐭','myodes-glareolus'=>'🐀','microtus-subterraneus'=>'🐀',
        'microtus-arvalis'=>'🐀','talpa-europaea'=>'🦔','sorex-araneus'=>'🐭',
    ];

    ?><!DOCTYPE html>
<html lang="sr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Сисари Рајца | ПИО „Рајац"</title>
<meta name="description" content="Комплетан преглед врста сисара (Mammalia) Предела изузетних одлика Рајац — папкари, звери, глодари, бубоједи, слепи мишеви.">
<link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,400;0,700;1,400&family=Source+Sans+3:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
:root{--forest:#2d5a27;--forest-dark:#1a3a15;--forest-light:#4a7c43;--cream:#f7f4ef;--warm-white:#fdfcfa;--text:#2a2a2a;--text-muted:#5a5a5a;--border:#d8d0c4;--accent:#8b6914;--section-bg:#f2f0eb;--tag-bg:#e8f0e6;--tag-border:#a8c4a4;--shadow:0 2px 12px rgba(0,0,0,.08);--shadow-hover:0 6px 24px rgba(0,0,0,.14);--radius:10px;--font-serif:'Merriweather',Georgia,serif;--font-sans:'Source Sans 3',system-ui,sans-serif}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font-sans);font-size:16px;line-height:1.75;color:var(--text);background:var(--warm-white)}
a{text-decoration:none;color:inherit}
/* HEADER */
.site-header{background:var(--forest-dark);color:#fff;padding:0 32px;display:flex;align-items:center;justify-content:space-between;height:58px;border-bottom:3px solid var(--forest-light)}
.site-header .logo{font-weight:700;font-size:15px;color:#fff;letter-spacing:.05em;text-transform:uppercase}
.site-header nav a{color:rgba(255,255,255,.85);margin-left:20px;font-size:12.5px}
.site-header nav a:hover{color:#fff}
/* HERO */
.page-hero{background:linear-gradient(135deg,var(--forest-dark) 0%,#2a4d1e 50%,#1e3a14 100%);color:#fff;padding:56px 24px 48px;text-align:center;position:relative;overflow:hidden}
.page-hero-inner{position:relative;max-width:720px;margin:0 auto}
.section-super{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.15em;color:rgba(255,255,255,.6);margin-bottom:10px}
.page-hero h1{font-family:var(--font-serif);font-size:2.6rem;font-weight:700;line-height:1.2;margin-bottom:14px;color:#fff}
.page-hero h1 em{font-style:italic;color:rgba(255,255,255,.75);font-size:1.4rem;display:block;margin-top:4px}
.page-hero p{font-size:16px;line-height:1.75;color:rgba(255,255,255,.82);max-width:580px;margin:0 auto 22px}
.hero-stats{display:flex;gap:32px;justify-content:center;flex-wrap:wrap;margin-top:24px}
.hero-stat{text-align:center}
.hero-stat .num{font-family:var(--font-serif);font-size:2rem;font-weight:700;color:#fff;line-height:1}
.hero-stat .lbl{font-size:12px;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.08em;margin-top:4px}
/* BREADCRUMB */
.breadcrumb-bar{background:var(--cream);border-bottom:1px solid var(--border);padding:10px 0}
.breadcrumb-bar .inner{max-width:980px;margin:0 auto;padding:0 24px;font-size:13px;color:var(--text-muted)}
.breadcrumb-bar a{color:var(--forest);font-weight:600}
.breadcrumb-bar .sep{margin:0 6px;opacity:.5}
/* WRAP */
.page-wrap{max-width:980px;margin:0 auto;padding:40px 24px 60px}
/* INTRO */
.intro-text{font-size:15.5px;line-height:1.85;color:var(--text);max-width:780px;margin-bottom:14px}
.confirmed-note{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;padding:6px 14px;border-radius:20px;margin-right:8px;margin-bottom:8px}
.note-confirmed{background:var(--tag-bg);color:var(--forest);border:1px solid var(--tag-border)}
.note-potential{background:#fff8e6;color:#7a5a00;border:1px solid #e8cc88}
.notes-row{margin-bottom:32px}
/* FILTER */
.filter-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:36px;padding-bottom:20px;border-bottom:2px solid var(--border)}
.filter-btn{font-family:var(--font-sans);font-size:13px;font-weight:600;padding:7px 16px;border-radius:20px;border:1.5px solid var(--border);background:#fff;color:var(--text-muted);cursor:pointer;transition:all .18s}
.filter-btn:hover,.filter-btn.active{background:var(--forest);color:#fff;border-color:var(--forest)}
/* GROUP */
.species-group{margin-bottom:48px}
.group-header{display:flex;align-items:center;gap:14px;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid var(--border)}
.group-icon{font-size:2rem;width:52px;height:52px;background:var(--forest);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.group-header-text h2{font-family:var(--font-serif);font-size:1.25rem;font-weight:700;color:var(--forest-dark);line-height:1.2}
.group-header-text .group-latin{font-style:italic;color:var(--forest-light);font-size:.95rem}
.group-header-text .group-count{font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-top:2px}
.group-desc{font-size:14px;line-height:1.7;color:var(--text-muted);max-width:780px;margin-bottom:18px}
/* CARDS */
.species-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.species-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow);transition:box-shadow .2s,transform .2s;display:flex;flex-direction:column}
.species-card:hover{box-shadow:var(--shadow-hover);transform:translateY(-2px)}
.species-card.has-page:hover .card-img-wrap img{transform:scale(1.07)}
.card-img-wrap{aspect-ratio:4/3;overflow:hidden;background:#c8bfaa;position:relative;display:flex;align-items:center;justify-content:center}
.card-img-wrap img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s}
.card-status-dot{position:absolute;top:8px;right:8px;width:10px;height:10px;border-radius:50%;border:2px solid #fff}
.dot-confirmed{background:#4caf50}
.dot-potential{background:#f0a020}
.card-body{padding:11px 13px 13px;flex:1;display:flex;flex-direction:column;gap:3px}
.card-common{font-weight:700;font-size:14px;color:var(--forest-dark);line-height:1.3}
.card-latin{font-style:italic;font-size:12px;color:var(--forest-light);line-height:1.3}
.card-tags{display:flex;gap:4px;flex-wrap:wrap;margin-top:6px}
.card-tag{font-size:10.5px;font-weight:600;padding:2px 7px;border-radius:4px}
.tag-protected{background:var(--tag-bg);color:var(--forest);border:1px solid var(--tag-border)}
.tag-game{background:#fff3e0;color:#c05000;border:1px solid #f0c080}
.tag-iucn{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7}
.tag-iucn-nt{background:#fff8e6;color:#7a5a00;border:1px solid #ffe082}
.card-arrow{margin-top:auto;padding-top:8px;font-size:12px;color:var(--forest);font-weight:600}
.card-arrow.no-page{color:var(--text-muted);font-style:italic}
/* BATS */
.bats-block{background:var(--section-bg);border:1px solid var(--border);border-radius:var(--radius);padding:20px 22px}
.bats-block h3{font-family:var(--font-serif);font-size:1rem;font-weight:700;color:var(--forest-dark);margin-bottom:8px}
.bats-block p{font-size:14px;line-height:1.75;color:var(--text);margin-bottom:12px}
.bats-species-list{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}
.bat-chip{font-size:12.5px;background:#fff;border:1px solid var(--border);border-radius:6px;padding:4px 10px;color:var(--text)}
.bat-chip em{color:var(--forest-light);font-style:italic}
.bats-img-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px}
.bats-img-row figure{border-radius:8px;overflow:hidden;background:#b8b0a0;box-shadow:var(--shadow);aspect-ratio:3/2}
.bats-img-row figure img{width:100%;height:100%;object-fit:cover;display:block}
.bats-img-row figcaption{font-size:10.5px;color:var(--text-muted);font-style:italic;padding:5px 8px;background:var(--cream);border:1px solid var(--border);border-top:none}
/* POTENTIAL */
.potential-section{margin-bottom:48px}
.potential-section .group-header{border-bottom-style:dashed}
.potential-list{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.potential-item{background:#fff;border:1.5px dashed var(--border);border-radius:var(--radius);padding:13px 15px;font-size:13.5px}
.potential-item strong{display:block;font-weight:700;color:var(--text);margin-bottom:2px}
.potential-item em{color:var(--forest-light);font-style:italic;font-size:12.5px}
.potential-item .p-note{font-size:12px;color:var(--text-muted);margin-top:5px;line-height:1.5}
/* BOTTOM NAV */
.section-nav{background:var(--forest-dark);border-radius:var(--radius);padding:24px 28px;display:flex;gap:16px;align-items:center;justify-content:space-between;margin-top:20px;flex-wrap:wrap}
.section-nav p{color:rgba(255,255,255,.75);font-size:14px}
.section-nav a{color:#fff;font-weight:700;font-size:14px;background:rgba(255,255,255,.15);padding:9px 18px;border-radius:6px;transition:background .2s}
.section-nav a:hover{background:rgba(255,255,255,.28)}
.site-footer{background:var(--forest-dark);color:rgba(255,255,255,.7);text-align:center;padding:22px;font-size:13px;margin-top:60px}
@media(max-width:860px){.species-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:620px){.species-grid{grid-template-columns:repeat(2,1fr)}.potential-list{grid-template-columns:1fr 1fr}.bats-img-row{grid-template-columns:1fr}.page-hero h1{font-size:1.9rem}.site-header nav{display:none}.hero-stats{gap:20px}}
</style>
</head>
<body>

<header class="site-header">
  <a href="<?=esc_url(home_url('/'))?>" class="logo">Предео изузетних одлика „Рајац"</a>
  <nav>
    <a href="<?=esc_url(home_url('/'))?>">ПИО „Рајац"</a>
    <a href="<?=esc_url(home_url('/flora'))?>">Флора Рајца</a>
    <a href="<?=esc_url(home_url('/ptice'))?>">Птице Рајца</a>
    <a href="<?=esc_url(get_post_type_archive_link('sisari'))?>" style="color:#fff;font-weight:700">Сисари Рајца</a>
    <a href="<?=esc_url(home_url('/karte/'))?>">Карте</a>
  </nav>
</header>

<div class="page-hero">
  <div class="page-hero-inner">
    <div class="section-super">ПИО „Рајац" · Фауна</div>
    <h1>Сисари Рајца<em>Mammalia</em></h1>
    <p>Предео изузетних одлика „Рајац" станиште је богате заједнице сисара — од крупних папкара и звери до ситних глодара, инсективора и 19 врста слепих мишева. Откријте сваку врсту, њене трагове, биологију и начин да јој помогнете.</p>
    <div class="hero-stats">
      <div class="hero-stat"><div class="num">23+</div><div class="lbl">Потврђене врсте</div></div>
      <div class="hero-stat"><div class="num">19</div><div class="lbl">Врста слепих мишева</div></div>
      <div class="hero-stat"><div class="num">6</div><div class="lbl">Редова сисара</div></div>
      <div class="hero-stat"><div class="num">~10</div><div class="lbl">Потенцијалне врсте</div></div>
    </div>
  </div>
</div>

<div class="breadcrumb-bar">
  <div class="inner">
    <a href="<?=esc_url(home_url('/'))?>">ПИО „Рајац"</a>
    <span class="sep">›</span>
    Сисари Рајца
  </div>
</div>

<main class="page-wrap">

  <p class="intro-text">На подручју ПИО „Рајац" потврђено је присуство представника шест редова сисара. Листа обухвата врсте са директним теренским налазима, али и оне са великом вероватноћом присуства на основу погодних станишта.</p>
  <div class="notes-row">
    <span class="confirmed-note note-confirmed">🟢 Потврђене / редовне врсте</span>
    <span class="confirmed-note note-potential">🟡 Потенцијалне / спорадичне врсте</span>
  </div>

  <div class="filter-bar">
    <button class="filter-btn active" onclick="filterGroup('sve',this)">Све врсте</button>
    <button class="filter-btn" onclick="filterGroup('papkari',this)">🦌 Папкари</button>
    <button class="filter-btn" onclick="filterGroup('zveri',this)">🦊 Звери</button>
    <button class="filter-btn" onclick="filterGroup('glodari',this)">🐿 Глодари</button>
    <button class="filter-btn" onclick="filterGroup('bubojedи',this)">🦔 Бубоједи</button>
    <button class="filter-btn" onclick="filterGroup('slepi_misevi',this)">🦇 Слепи мишеви</button>
  </div>

<?php
    foreach($grupe as $grupa_key => $grupa):
        if($grupa_key === 'slepi_misevi') continue; // special render below
?>
  <div class="species-group" id="<?=esc_attr($grupa_key)?>" data-group="<?=esc_attr($grupa_key)?>">
    <div class="group-header">
      <div class="group-icon"><?=$grupa['icon']?></div>
      <div class="group-header-text">
        <h2><?=esc_html($grupa['naziv'])?></h2>
        <div class="group-latin"><?=esc_html($grupa['latin'])?></div>
        <div class="group-count"><?=esc_html($grupa['count'])?></div>
      </div>
    </div>
    <p class="group-desc"><?=esc_html($grupa['opis'])?></p>
    <div class="species-grid">
<?php
        foreach($grupa['slugs'] as $slug):
            $s = $by_slug[$slug] ?? null;
            if(!$s) continue;
            $url      = $has_page[$slug] ?? '#';
            $has_cls  = isset($has_page[$slug]) ? ' has-page' : '';
            $dot_cls  = ($s['status']==='potvrdjen') ? 'dot-confirmed' : 'dot-potential';
            $itag     = $iucn_tags[$s['iucn']] ?? '';
            $etag     = $extra_tags[$slug] ?? '';
            $arrow    = isset($has_page[$slug])
                        ? '<div class="card-arrow">Прочитај više →</div>'
                        : '<div class="card-arrow no-page">Страница у изради</div>';
            $emoji    = $emoji_fallback[$slug] ?? '🐾';
            $page_img = $page_hero_map[$slug]['src'] ?? '';
            $page_fallback = $page_hero_map[$slug]['fallback'] ?? '';

            /*
             * За архиву /sisari/ прво користимо исту главну фотографију као на
             * појединачној страници врсте, да би листинг био визуелно уједначен
             * као страница лисице. Каталошка слика остаје само као резерва.
             */
            $card_img = $page_img ? $page_img : sisari1_archive_card_image_url($s['foto']);

            $card_onerror = '';
            if($page_fallback){
                $card_onerror = "this.onerror=null;this.src='" . esc_js($page_fallback) . "';";
            } elseif(!$page_img){
                $fallback_catalog = sisari1_archive_card_image_url($s['foto']);
                if($fallback_catalog && $fallback_catalog !== $card_img){
                    $card_onerror = "this.onerror=null;this.src='" . esc_js($fallback_catalog) . "';";
                }
            }
?>
      <a href="<?=esc_url($url)?>" class="species-card<?=$has_cls?>">
        <div class="card-img-wrap"><?php
            if($card_img): ?>
          <img src="<?=esc_url($card_img)?>"
               alt="<?=esc_attr($s['naziv'].' — '.$s['latin'])?>"
               loading="lazy"
               <?php if($card_onerror): ?>onerror="<?=esc_attr($card_onerror)?>"<?php endif; ?>>
          <div class="card-status-dot <?=$dot_cls?>"></div>
<?php       else: ?>
          <span style="font-size:2.5rem"><?=$emoji?></span>
          <div class="card-status-dot <?=$dot_cls?>" style="position:absolute;top:8px;right:8px"></div>
<?php       endif; ?>
        </div>
        <div class="card-body">
          <div class="card-common"><?=esc_html($s['naziv'])?></div>
          <div class="card-latin"><?=esc_html($s['latin'])?></div>
          <div class="card-tags"><?=$etag?><?=$itag?></div>
          <?=$arrow?>
        </div>
      </a>
<?php       endforeach; ?>
    </div>
  </div>
<?php   endforeach; ?>

  <!-- СЛЕПИ МИШЕВИ -->
  <div class="species-group" id="slepi_misevi" data-group="slepi_misevi">
    <div class="group-header">
      <div class="group-icon">🦇</div>
      <div class="group-header-text">
        <h2>Слепи мишеви</h2>
        <div class="group-latin">Chiroptera</div>
        <div class="group-count">19 врста · све строго заштићене</div>
      </div>
    </div>
    <p class="group-desc">Рајац је изузетно богат хироптерофауном — 19 потврђених врста слепих мишева, укључујући важне шпиљарске и шумске врсте. Све врсте слепих мишева у Србији строго су заштићене Законом о заштити природе и Бернском конвенцијом.</p>
    <div class="bats-block">
      <h3>🔬 Потврђене врсте на Рајцу</h3>
      <p>Истраживања хироптерофауне евидентирала су следеће врсте у шумама, шпиљама и традиционалним грађевинама Рајца:</p>
      <div class="bats-species-list">
        <span class="bat-chip">Велики потковичар <em>Rhinolophus ferrumequinum</em></span>
        <span class="bat-chip">Мали потковичар <em>Rhinolophus hipposideros</em></span>
        <span class="bat-chip">Велики мишоуши вечерњак <em>Myotis myotis</em></span>
        <span class="bat-chip">Мали мишоуши вечерњак <em>Myotis blythii</em></span>
        <span class="bat-chip">Средњи ноћник <em>Nyctalus noctula</em></span>
        <span class="bat-chip">Мали ноћник <em>Nyctalus leisleri</em></span>
        <span class="bat-chip">Обична вечерња шишмиша <em>Pipistrellus pipistrellus</em></span>
        <span class="bat-chip">Натерерова вечерња шишмиша <em>Myotis nattereri</em></span>
        <span class="bat-chip">Брцата вечерња шишмиша <em>Myotis mystacinus</em></span>
        <span class="bat-chip">+ 10 додатних врста</span>
      </div>
      <div class="bats-img-row">
        <figure>
          <img src="SISARI_IMG_URL/Rhinolophus_ferrumequinum.jpg"
               alt="Велики потковичар (Rhinolophus ferrumequinum)" loading="lazy" onerror="this.src='SISARI_IMG_URL/Rhinolophus_ferrumequinum.jpg';this.onerror=null;">
          <figcaption>Велики потковичар · <em>Rhinolophus ferrumequinum</em> · Строго заштићена · CC BY-SA</figcaption>
        </figure>
        <figure>
          <img src="SISARI_IMG_URL/Myotis_myotis_head.jpg"
               alt="Велики мишоуши вечерњак (Myotis myotis)" loading="lazy" onerror="this.src='SISARI_IMG_URL/Myotis_myotis_head.jpg';this.onerror=null;">
          <figcaption>Велики мишоуши вечерњак · <em>Myotis myotis</em> · Анекс II Хабитатне директиве · CC BY-SA</figcaption>
        </figure>
      </div>
    </div>
  </div>

  <!-- ПОТЕНЦИЈАЛНЕ ВРСТЕ -->
  <div class="potential-section" id="potencijalne">
    <div class="group-header">
      <div class="group-icon" style="background:#d4a020">❓</div>
      <div class="group-header-text">
        <h2>Потенцијалне и спорадичне врсте</h2>
        <div class="group-latin">Врсте без новијих директних доказа о сталном присуству</div>
        <div class="group-count">~10 врста</div>
      </div>
    </div>
    <p class="group-desc">За ове врсте постоје погодна станишта на Рајцу или су забележене у непосредном окружењу — али недостају конзистентни теренски докази о сталном присуству.</p>
    <style>
    .potential-list{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
    .potential-item{background:#fff;border:1.5px dashed var(--border);border-radius:var(--radius);overflow:hidden;font-size:13.5px;transition:box-shadow .2s,transform .2s;display:flex;flex-direction:column}
    .potential-item:hover{box-shadow:var(--shadow-hover);transform:translateY(-2px);border-style:solid;border-color:var(--forest)}
    .potential-item a{display:flex;flex-direction:column;height:100%;color:inherit;text-decoration:none}
    .pi-img{aspect-ratio:4/3;overflow:hidden;background:#c8bfaa;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .pi-img img{width:100%;height:100%;object-fit:cover;transition:transform .35s}
    .potential-item:hover .pi-img img{transform:scale(1.06)}
    .pi-body{padding:11px 13px 13px;flex:1}
    .pi-body strong{display:block;font-weight:700;color:var(--forest-dark);margin-bottom:2px}
    .pi-body em{color:var(--forest-light);font-style:italic;font-size:12px}
    .p-note{font-size:12px;color:var(--text-muted);margin-top:5px;line-height:1.5}
    .pi-arrow{font-size:12px;color:var(--forest);font-weight:600;margin-top:6px}
    .pi-arrow.no-link{color:var(--text-muted);font-style:italic}
    @media(max-width:620px){.potential-list{grid-template-columns:1fr 1fr}}
    </style>
    <div class="potential-list">
<?php
    $pot_species = [
      ['slug'=>'canis-lupus',         'naziv'=>'Вук',                  'latin'=>'Canis lupus',        'note'=>'Рајац користи само као „саобраћајницу" при сезонском кретању.',               'foto'=>'SISARI_IMG_URL/Canis_lupus_laying.jpg'],
      ['slug'=>'ursus-arctos',         'naziv'=>'Мрки медвед',          'latin'=>'Ursus arctos',       'note'=>'Крајње спорадична опажања. Не сматра се сталним фаунистичким саставом.',         'foto'=>'SISARI_IMG_URL/Brown_bear.jpg'],
      ['slug'=>'lutra-lutra',          'naziv'=>'Видра',                'latin'=>'Lutra lutra',        'note'=>'Могућа повремена појава уз водотокове. IUCN NT — индикатор чистих вода.',          'foto'=>'SISARI_IMG_URL/Lutra_lutra.jpg'],
      ['slug'=>'sorex-minutus',        'naziv'=>'Мала ровчица',         'latin'=>'Sorex minutus',      'note'=>'Погодна станишта присутна. Недостају директни теренски налази.',                   'foto'=>'SISARI_IMG_URL/Sorex_minutus.jpg'],
      ['slug'=>'neomys-fodiens',       'naziv'=>'Водена ровчица',       'latin'=>'Neomys fodiens',     'note'=>'Везана за влажне обале потока и ивице мочвара.',                                   'foto'=>'SISARI_IMG_URL/Neomys_fodiens.jpg'],
      ['slug'=>'crocidura-suaveolens', 'naziv'=>'Баштенска ровчица',    'latin'=>'Crocidura suaveolens','note'=>'Погодна станишта у рубним зонама насеља и баштама.',                            'foto'=>'SISARI_IMG_URL/Crocidura_suaveolens.jpg'],
      ['slug'=>'crocidura-leucodon',   'naziv'=>'Пољска ровчица',       'latin'=>'Crocidura leucodon', 'note'=>'Очекивана на полуотвореним стаништима у периферним зонама.',                       'foto'=>'SISARI_IMG_URL/Crocidura_leucodon.jpg'],
    ];
    foreach($pot_species as $ps):
        $url = isset($has_page[$ps['slug']]) ? get_permalink(get_page_by_path($ps['slug'],'OBJECT','sisari')->ID ?? 0) : '';
        $url = $url ?: (isset($has_page[$ps['slug']]) ? $has_page[$ps['slug']] : '');
        // Check directly in has_page array
        $url = $has_page[$ps['slug']] ?? '';
        $has_link = !empty($url);
        $wrap_open  = $has_link ? '<a href="'.esc_url($url).'">' : '<div>';
        $wrap_close = $has_link ? '</a>' : '</div>';
?>
      <div class="potential-item">
        <?=$wrap_open?>
        <div class="pi-img">
          <img src="<?=esc_url($ps['foto'])?>" alt="<?=esc_attr($ps['naziv'])?>" loading="lazy" onerror="this.style.display='none'">
        </div>
        <div class="pi-body">
          <strong><?=esc_html($ps['naziv'])?></strong>
          <em><?=esc_html($ps['latin'])?></em>
          <div class="p-note"><?=esc_html($ps['note'])?></div>
          <div class="pi-arrow <?=$has_link?'':'no-link'?>"><?=$has_link?'Прочитај više →':'Страница у изради'?></div>
        </div>
        <?=$wrap_close?>
      </div>
<?php   endforeach; ?>
    </div>
  </div>

  <div class="section-nav">
    <p>Откријте и остале природне вредности ПИО „Рајац"</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a href="<?=esc_url(home_url('/flora'))?>">🌿 Флора Рајца</a>
      <a href="<?=esc_url(home_url('/ptice'))?>">🐦 Птице Рајца</a>
      <a href="<?=esc_url(home_url('/karte/'))?>">🗺 Карте и стазе</a>
    </div>
  </div>

</main>

<footer class="site-footer">
  Copyright &copy; <?=date('Y')?> — Предео изузетних одлика „Рајац" · Туристичка организација општине Љиг
</footer>

<script>
function filterGroup(group, btn){
  document.querySelectorAll('.filter-btn').forEach(function(b){b.classList.remove('active')});
  btn.classList.add('active');
  document.querySelectorAll('.species-group,.potential-section').forEach(function(el){
    if(group==='sve'){ el.style.display=''; }
    else { el.style.display = (el.dataset.group===group) ? '' : 'none'; }
  });
}
</script>
</body>
</html>
<?php
}

// ══════════════════════════════════════════
// 9. ADMIN АКЦИЈА — синхронизација нових врста
// ══════════════════════════════════════════
add_action('admin_menu','sisari1_sync_menu');
function sisari1_sync_menu(){
    add_submenu_page('edit.php?post_type=sisari','Синхронизуј нове врсте','🔄 Синхронизуј','manage_options','sisari1-sync','sisari1_sync_page');
}
function sisari1_sync_page(){
    echo '<div class="wrap"><h1>Синхронизација нових врста сисара</h1>';
    if(isset($_POST['sisari1_do_sync']) && check_admin_referer('sisari1_sync')){
        $count = 0;
        foreach(sisari1_lista() as $p){
            $ex = get_posts(['post_type'=>'sisari','name'=>$p['slug'],'post_status'=>'any','numberposts'=>1]);
            if($ex) continue;
            wp_insert_post(['post_title'=>$p['naziv'],'post_name'=>$p['slug'],'post_content'=>'','post_status'=>'publish','post_type'=>'sisari',
                'meta_input'=>['_sisari_latin'=>$p['latin'],'_sisari_iucn'=>$p['iucn'],'_sisari_red'=>$p['red'],'_sisari_porodica'=>$p['porodica'],'_sisari_kategorija'=>$p['kategorija'],'_sisari_status_rajac'=>$p['status'],'_sisari_foto_url'=>$p['foto']]]);
            $count++;
        }
        echo '<div class="notice notice-success"><p>Додато <strong>'.$count.'</strong> нових врста.</p></div>';
    }
    wp_nonce_field('sisari1_sync');
    echo '<form method="post"><p>Ова акција креира WordPress постове за све врсте из листе које још не постоје.</p>';
    echo '<p><input type="hidden" name="sisari1_do_sync" value="1"><button type="submit" class="button button-primary">▶ Покрени синхронизацију</button></p></form></div>';
}

// ══════════════════════════════════════════
// 10. ДЕАКТИВАЦИЈА
// ══════════════════════════════════════════
register_deactivation_hook(__FILE__,'sisari1_deaktivacija');
function sisari1_deaktivacija(){
    flush_rewrite_rules();
}

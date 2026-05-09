<?php
/**
 * PIO Rajac – eBird Illustrated Checklist / Macaulay media endpoint
 *
 * Put this file in the same folder as ebird-l7416435-data.php.
 * HTML usage:
 *   <img src="rajac-illustrated-media.php?mode=image&hotspot=L7416435&taxonCode=eurrob1&size=320">
 * Species list usage:
 *   rajac-illustrated-media.php?mode=species&hotspot=L7416435
 * Debug:
 *   rajac-illustrated-media.php?mode=debug&taxonCode=corbun1
 */

$DEFAULT_HOTSPOT = 'L7416435';

function rim_clean_code($v){
    $v = strtolower(trim((string)$v));
    return preg_replace('/[^a-z0-9_-]/', '', $v);
}
function rim_clean_text($v, $limit=180){
    $v = trim((string)$v);
    $v = preg_replace('/[\x00-\x1F\x7F]/u', '', $v);
    if(function_exists('mb_substr')) return mb_substr($v, 0, $limit, 'UTF-8');
    return substr($v, 0, $limit);
}
function rim_json($data, $code=200){
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=1800');
    echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    exit;
}
function rim_cache_dir($sub='media'){
    $dir = __DIR__ . '/ebird-cache/rajac-illustrated-' . preg_replace('/[^a-z0-9_-]/i','',$sub);
    if(!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}
function rim_cache_get($file, $ttl){
    if(is_file($file) && (time() - filemtime($file) < $ttl)){
        $raw = @file_get_contents($file);
        $json = json_decode((string)$raw, true);
        if(is_array($json)) return $json;
    }
    return null;
}
function rim_cache_put($file, $data){
    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
function rim_fetch($url, &$status=null){
    $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome Safari PIO-Rajac/1.0';
    $headers = [
        'Accept: text/html,application/json;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9,sr;q=0.8',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'Referer: https://ebird.org/'
    ];
    $status = 0;
    if(function_exists('curl_init')){
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_USERAGENT => $ua,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if($body !== false && $status >= 200 && $status < 400) return (string)$body;
    }
    $ctx = stream_context_create(['http'=>[
        'method'=>'GET',
        'timeout'=>25,
        'ignore_errors'=>true,
        'header'=>"User-Agent: $ua\r\nAccept: text/html,application/json;q=0.9,*/*;q=0.8\r\nAccept-Language: en-US,en;q=0.9,sr;q=0.8\r\nReferer: https://ebird.org/\r\n"
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if(isset($http_response_header) && is_array($http_response_header)){
        foreach($http_response_header as $h){ if(preg_match('/^HTTP\/\S+\s+(\d+)/', $h, $m)){ $status=(int)$m[1]; break; } }
    }
    return $body !== false ? (string)$body : '';
}
function rim_decode_html_json($html){
    return html_entity_decode((string)$html, ENT_QUOTES|ENT_HTML5, 'UTF-8');
}
function rim_recursive_find_first_asset($x){
    $queue = [$x];
    $seen = 0;
    while($queue && $seen++ < 15000){
        $v = array_shift($queue);
        if(is_array($v)){
            foreach(['assetId','assetID','asset_id','catalogId','catalogID','catalog_id','catId','catID','mediaId','mediaID','mlCatalogNumber','MLCatalogNumber','ml_catalog_number','id'] as $k){
                if(isset($v[$k]) && preg_match('/(?:ML)?(\d{5,})/i', (string)$v[$k], $m)) return $m[1];
            }
            foreach(['previewUrl','preview_url','thumbnailUrl','thumbnail_url','imageUrl','image_url','mediaUrl','media_url','url','src'] as $k){
                if(isset($v[$k]) && is_string($v[$k])){
                    $s = $v[$k];
                    if(preg_match('/asset\/(\d{5,})/i', $s, $m)) return $m[1];
                    if(preg_match('/macaulaylibrary\.org\/asset\/(\d{5,})/i', $s, $m)) return $m[1];
                }
            }
            foreach($v as $vv){ if(is_array($vv) || is_string($vv)) $queue[]=$vv; }
        } elseif(is_string($v)) {
            if(preg_match('/cdn\.download\.ams\.birds\.cornell\.edu\/api\/v1\/asset\/(\d{5,})/i', $v, $m)) return $m[1];
            if(preg_match('/macaulaylibrary\.org\/asset\/(\d{5,})/i', $v, $m)) return $m[1];
            if(preg_match('/ML(\d{5,})/i', $v, $m)) return $m[1];
        }
    }
    return '';
}
function rim_recursive_find_image($x){
    $queue = [$x];
    $seen = 0;
    while($queue && $seen++ < 15000){
        $v = array_shift($queue);
        if(is_array($v)){
            foreach(['previewUrl','preview_url','thumbnailUrl','thumbnail_url','thumbUrl','thumb_url','imageUrl','image_url','smallUrl','mediumUrl','mediaUrl','media_url','url','src'] as $k){
                if(!empty($v[$k]) && is_string($v[$k])){
                    $u = $v[$k];
                    if(strpos($u, '//') === 0) $u = 'https:' . $u;
                    if(preg_match('~^https?://~i', $u) && (stripos($u, 'birds.cornell.edu') !== false || stripos($u, 'macaulaylibrary.org') !== false)) return $u;
                }
            }
            foreach($v as $vv){ if(is_array($vv) || is_string($vv)) $queue[]=$vv; }
        } elseif(is_string($v)){
            if(preg_match('~https://cdn\.download\.ams\.birds\.cornell\.edu/api/v1/asset/\d+/(?:\d+|preview)~i', $v, $m)) return $m[0];
            if(preg_match('~https://[^\s"\']*macaulaylibrary\.org[^\s"\']*~i', $v, $m)) return $m[0];
        }
    }
    return '';
}
function rim_json_extract_by_code($html, $taxonCode){
    $html = rim_decode_html_json($html);
    $taxonCode = strtolower($taxonCode);
    $pos = 0;
    $assets = [];
    while(($p = stripos($html, $taxonCode, $pos)) !== false && count($assets) < 5){
        $start = max(0, $p - 35000);
        $chunk = substr($html, $start, 85000);
        if(preg_match_all('/cdn\.download\.ams\.birds\.cornell\.edu\/api\/v1\/asset\/(\d{5,})(?:\/\d+)?/i', $chunk, $m)){
            foreach($m[1] as $id){ $assets[]=$id; }
        }
        if(preg_match_all('/macaulaylibrary\.org\/asset\/(\d{5,})/i', $chunk, $m)){
            foreach($m[1] as $id){ $assets[]=$id; }
        }
        if(preg_match_all('/["\'](?:assetId|assetID|catalogId|mediaId|mlCatalogNumber|MLCatalogNumber)["\']\s*:\s*["\']?(\d{5,})["\']?/i', $chunk, $m)){
            foreach($m[1] as $id){ $assets[]=$id; }
        }
        $pos = $p + strlen($taxonCode);
    }
    $assets = array_values(array_unique($assets));
    return $assets ? $assets[0] : '';
}
function rim_extract_json_scripts($html){
    $out=[];
    if(preg_match_all('/<script[^>]+type=["\']application\/json["\'][^>]*>(.*?)<\/script>/is', $html, $m)){
        foreach($m[1] as $raw){ $j=json_decode(html_entity_decode($raw,ENT_QUOTES|ENT_HTML5,'UTF-8'), true); if(is_array($j)) $out[]=$j; }
    }
    if(preg_match('/<script[^>]+id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/is', $html, $m)){
        $j=json_decode(html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8'), true); if(is_array($j)) $out[]=$j;
    }
    return $out;
}
function rim_find_asset_in_json_near_code($json, $taxonCode){
    $taxonCode = strtolower($taxonCode);
    $best = '';
    $walk = function($x) use (&$walk, $taxonCode, &$best){
        if($best) return;
        if(!is_array($x)) return;
        $hasCode = false;
        foreach(['taxonCode','speciesCode','species_code','taxon_code','code'] as $k){
            if(isset($x[$k]) && strtolower((string)$x[$k]) === $taxonCode){ $hasCode = true; break; }
        }
        if($hasCode){
            $best = rim_recursive_find_first_asset($x);
            if($best) return;
        }
        foreach($x as $v){ if(is_array($v)) $walk($v); if($best) return; }
    };
    $walk($json);
    return $best;
}
function rim_image_url_for_asset($assetId, $size){
    $assetId = preg_replace('/[^0-9]/','',(string)$assetId);
    if(!$assetId) return '';
    $size = (int)$size;
    if($size < 120) $size = 320;
    if($size > 1600) $size = 640;
    return 'https://cdn.download.ams.birds.cornell.edu/api/v1/asset/' . $assetId . '/' . $size;
}
function rim_try_illustrated($hotspot, $taxonCode, $size, &$trace=[]){
    if(!$hotspot || !$taxonCode) return null;
    $cache = rim_cache_dir('illustrated-html') . '/' . preg_replace('/[^a-z0-9_-]/i','_', $hotspot) . '.html';
    $html = '';
    if(is_file($cache) && (time() - filemtime($cache) < 7*86400)){
        $html = (string)@file_get_contents($cache);
        $trace[] = 'illustrated cache html';
    } else {
        $status = 0;
        $url = 'https://ebird.org/hotspot/' . rawurlencode($hotspot) . '/illustrated-checklist';
        $html = rim_fetch($url, $status);
        $trace[] = 'illustrated fetch status=' . $status;
        if($html) @file_put_contents($cache, $html);
    }
    if(!$html) return null;
    foreach(rim_extract_json_scripts($html) as $json){
        $asset = rim_find_asset_in_json_near_code($json, $taxonCode);
        if($asset) return ['source'=>'ebird-illustrated-json','assetId'=>$asset,'image'=>rim_image_url_for_asset($asset,$size),'assetPage'=>'https://macaulaylibrary.org/asset/'.$asset];
    }
    $asset = rim_json_extract_by_code($html, $taxonCode);
    if($asset) return ['source'=>'ebird-illustrated-html','assetId'=>$asset,'image'=>rim_image_url_for_asset($asset,$size),'assetPage'=>'https://macaulaylibrary.org/asset/'.$asset];
    return null;
}
function rim_first_item($json){
    if(is_array($json)){
        if(array_keys($json) === range(0,count($json)-1) && isset($json[0])) return $json[0];
        foreach(['results','content','items','data','media','assets','docs','rows','searchResults','resultsList'] as $k){
            if(isset($json[$k]) && is_array($json[$k]) && isset($json[$k][0])) return $json[$k][0];
        }
    }
    return $json;
}
function rim_try_macaulay($taxonCode, $latin, $size, &$trace=[]){
    $q = [];
    if($taxonCode){
        $q[]='https://search.macaulaylibrary.org/catalog.json?mediaType=photo&sort=rating_rank_desc&taxonCode=' . rawurlencode($taxonCode) . '&count=1';
        $q[]='https://search.macaulaylibrary.org/catalog.json?mediaType=p&sort=rating_rank_desc&taxonCode=' . rawurlencode($taxonCode) . '&count=1';
        $q[]='https://search.macaulaylibrary.org/catalog?mediaType=photo&sort=rating_rank_desc&taxonCode=' . rawurlencode($taxonCode);
    }
    if($latin){
        $q[]='https://search.macaulaylibrary.org/catalog.json?mediaType=photo&sort=rating_rank_desc&searchField=species&q=' . rawurlencode($latin) . '&count=1';
        $q[]='https://search.macaulaylibrary.org/catalog?mediaType=photo&sort=rating_rank_desc&searchField=species&q=' . rawurlencode($latin);
    }
    foreach($q as $url){
        $status=0; $body = rim_fetch($url, $status); $trace[] = 'macaulay ' . $status . ' ' . preg_replace('/\?.*/','',$url);
        if(!$body) continue;
        $json = json_decode($body,true);
        $image=''; $asset='';
        if(is_array($json)){
            $first = rim_first_item($json);
            $image = rim_recursive_find_image($first) ?: rim_recursive_find_image($json);
            $asset = rim_recursive_find_first_asset($first) ?: rim_recursive_find_first_asset($json);
        } else {
            if(preg_match('/cdn\.download\.ams\.birds\.cornell\.edu\/api\/v1\/asset\/(\d{5,})/i', $body, $m)) $asset=$m[1];
            if(!$asset && preg_match('/macaulaylibrary\.org\/asset\/(\d{5,})/i', $body, $m)) $asset=$m[1];
            if(preg_match('~https://cdn\.download\.ams\.birds\.cornell\.edu/api/v1/asset/\d+/(?:\d+|preview)~i', $body, $m)) $image=$m[0];
        }
        if(!$image && $asset) $image = rim_image_url_for_asset($asset, $size);
        if($image) return ['source'=>'macaulay-catalog','assetId'=>$asset,'image'=>$image,'assetPage'=>$asset?'https://macaulaylibrary.org/asset/'.$asset:''];
    }
    return null;
}
function rim_taxon_from_latin($latin, &$trace=[]){
    $latin = rim_clean_text($latin);
    if(!$latin) return '';
    $cache = rim_cache_dir('taxon') . '/' . md5(strtolower($latin)) . '.json';
    $cached = rim_cache_get($cache, 60*86400);
    if($cached && !empty($cached['taxonCode'])) return $cached['taxonCode'];
    $urls = [
        'https://search.macaulaylibrary.org/api/v1/find/taxon?q=' . rawurlencode($latin),
        'https://ebird.org/species/search?q=' . rawurlencode($latin)
    ];
    foreach($urls as $url){
        $status=0; $body=rim_fetch($url,$status); $trace[]='taxon '.$status;
        if(!$body) continue;
        $json=json_decode($body,true);
        if(is_array($json)){
            $code = '';
            $walk = function($x) use (&$walk,&$code){
                if($code || !is_array($x)) return;
                foreach(['taxonCode','speciesCode','species_code','taxon_code','code'] as $k){
                    if(!empty($x[$k]) && preg_match('/^[a-z0-9_-]+$/i',(string)$x[$k])){ $code=strtolower((string)$x[$k]); return; }
                }
                foreach($x as $v){ if(is_array($v)) $walk($v); if($code) return; }
            };
            $walk($json);
            if($code){ rim_cache_put($cache, ['taxonCode'=>$code,'latin'=>$latin]); return $code; }
        }
        if(preg_match('/\/species\/([a-z0-9_-]+)/i',$body,$m)){ rim_cache_put($cache,['taxonCode'=>strtolower($m[1]),'latin'=>$latin]); return strtolower($m[1]); }
    }
    rim_cache_put($cache, ['taxonCode'=>'','latin'=>$latin]);
    return '';
}
function rim_media_for_taxon($taxonCode, $latin, $hotspot, $size, &$trace=[]){
    if(!$taxonCode && $latin) $taxonCode = rim_taxon_from_latin($latin, $trace);
    $taxonCode = rim_clean_code($taxonCode);
    $latin = rim_clean_text($latin);
    if(!$taxonCode && !$latin) return null;
    $key = preg_replace('/[^a-z0-9_-]/i','_', $hotspot . '_' . ($taxonCode ?: md5(strtolower($latin))) . '_' . (int)$size);
    $cache = rim_cache_dir('media') . '/' . $key . '.json';
    $cached = rim_cache_get($cache, 14*86400);
    if($cached && !empty($cached['image'])){ $cached['cache']='hit'; return $cached; }
    $media = null;
    if($taxonCode) $media = rim_try_illustrated($hotspot, $taxonCode, $size, $trace);
    if(!$media) $media = rim_try_macaulay($taxonCode, $latin, $size, $trace);
    if($media){
        $media['taxonCode']=$taxonCode; $media['latin']=$latin; $media['hotspot']=$hotspot; $media['cachedAt']=gmdate('c');
        rim_cache_put($cache, $media);
        return $media;
    }
    rim_cache_put($cache, ['taxonCode'=>$taxonCode,'latin'=>$latin,'image'=>'','cachedAt'=>gmdate('c')]);
    return null;
}
function rim_svg_placeholder($label){
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    $label = htmlspecialchars($label ?: 'Macaulay', ENT_QUOTES, 'UTF-8');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="360" height="240" viewBox="0 0 360 240"><rect width="360" height="240" rx="28" fill="#ded5c5"/><text x="180" y="108" text-anchor="middle" font-family="Georgia,serif" font-size="18" fill="#4a544d">Macaulay</text><text x="180" y="136" text-anchor="middle" font-family="Arial,sans-serif" font-size="13" fill="#687269">слика се није учитала</text><text x="180" y="162" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#687269">'.$label.'</text></svg>';
    exit;
}
function rim_add_species(&$map, $code, $common='', $latin='', $sr=''){
    $code = rim_clean_code($code);
    if(!$code) return;
    if(!isset($map[$code])) $map[$code]=['speciesCode'=>$code];
    if($common && empty($map[$code]['comName'])) $map[$code]['comName']=rim_clean_text($common);
    if($latin && empty($map[$code]['sciName'])) $map[$code]['sciName']=rim_clean_text($latin);
    if($sr && empty($map[$code]['srName'])) $map[$code]['srName']=rim_clean_text($sr);
}
function rim_extract_species_from_json($json, &$map){
    $walk = function($x) use (&$walk,&$map){
        if(!is_array($x)) return;
        $code = '';
        foreach(['taxonCode','speciesCode','species_code','taxon_code','code'] as $k){ if(!empty($x[$k]) && preg_match('/^[a-z0-9_-]+$/i',(string)$x[$k])){ $code=strtolower((string)$x[$k]); break; } }
        if($code){
            $common = $x['commonName'] ?? $x['comName'] ?? $x['name'] ?? $x['speciesName'] ?? '';
            $latin = $x['scientificName'] ?? $x['sciName'] ?? $x['latinName'] ?? $x['latin'] ?? '';
            rim_add_species($map,$code,$common,$latin);
        }
        foreach($x as $v){ if(is_array($v)) $walk($v); }
    };
    $walk($json);
}
function rim_species_list($hotspot){
    $cache = rim_cache_dir('species') . '/' . preg_replace('/[^a-z0-9_-]/i','_', $hotspot) . '.json';
    $cached = rim_cache_get($cache, 3*86400);
    if($cached && !empty($cached['species'])) return $cached;
    $urls = [
        'https://ebird.org/hotspot/' . rawurlencode($hotspot) . '/illustrated-checklist',
        'https://ebird.org/hotspot/' . rawurlencode($hotspot) . '/bird-list'
    ];
    $map=[]; $trace=[];
    foreach($urls as $url){
        $status=0; $html=rim_fetch($url,$status); $trace[]=$url.' status='.$status.' bytes='.strlen($html);
        if(!$html) continue;
        foreach(rim_extract_json_scripts($html) as $json) rim_extract_species_from_json($json,$map);
        $decoded = rim_decode_html_json($html);
        if(preg_match_all('~/species/([a-z0-9_-]+)~i',$decoded,$m)){
            foreach($m[1] as $code) rim_add_species($map,$code);
        }
        if(preg_match_all('/["\'](?:taxonCode|speciesCode)["\']\s*:\s*["\']([a-z0-9_-]+)["\']/i',$decoded,$m)){
            foreach($m[1] as $code) rim_add_species($map,$code);
        }
    }
    $species = array_values($map);
    usort($species, function($a,$b){ return strcmp($a['speciesCode'],$b['speciesCode']); });
    $out=['ok'=>true,'hotspot'=>$hotspot,'source'=>'ebird illustrated-checklist / bird-list','species'=>$species,'count'=>count($species),'trace'=>$trace,'cachedAt'=>gmdate('c')];
    rim_cache_put($cache,$out);
    return $out;
}

$mode = strtolower($_GET['mode'] ?? 'image');
$hotspot = rim_clean_code($_GET['hotspot'] ?? $_GET['locId'] ?? $DEFAULT_HOTSPOT) ?: $DEFAULT_HOTSPOT;
$taxonCode = rim_clean_code($_GET['taxonCode'] ?? $_GET['speciesCode'] ?? $_GET['code'] ?? '');
$latin = rim_clean_text($_GET['latin'] ?? $_GET['sciName'] ?? '');
$size = isset($_GET['size']) ? (int)$_GET['size'] : 360;
if($size < 120) $size=320;
if($size > 1600) $size=640;

if($mode === 'species'){
    rim_json(rim_species_list($hotspot));
}
if($mode === 'gallery'){
    $code = $taxonCode ?: rim_taxon_from_latin($latin);
    $url = $code ? 'https://search.macaulaylibrary.org/catalog?mediaType=photo&sort=rating_rank_desc&taxonCode=' . rawurlencode($code) : 'https://search.macaulaylibrary.org/catalog?mediaType=photo&sort=rating_rank_desc&searchField=species&q=' . rawurlencode($latin);
    header('Location: ' . $url, true, 302); exit;
}
$trace=[];
$media = rim_media_for_taxon($taxonCode, $latin, $hotspot, $size, $trace);
if($mode === 'json' || $mode === 'debug'){
    rim_json(['ok'=>(bool)$media,'request'=>['hotspot'=>$hotspot,'taxonCode'=>$taxonCode,'latin'=>$latin,'size'=>$size],'media'=>$media,'trace'=>$trace]);
}
if($media && !empty($media['image'])){
    header('Cache-Control: public, max-age=86400');
    header('Location: ' . $media['image'], true, 302);
    exit;
}
rim_svg_placeholder($taxonCode ?: $latin);

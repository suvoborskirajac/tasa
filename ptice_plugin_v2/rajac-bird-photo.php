<?php
/**
 * PIO Rajac bird photo endpoint v5
 * Priority:
 * 1) Macaulay photos from eBird hotspot L7416435 (Rajac) by taxonCode.
 * 2) Global Macaulay photos for the same taxonCode.
 * No Wikipedia/Commons fallback.
 *
 * Usage:
 *   rajac-bird-photo.php?taxonCode=corbun1&size=720
 *   rajac-bird-photo.php?mode=json&taxonCode=corbun1
 *   rajac-bird-photo.php?mode=debug&taxonCode=corbun1
 */
const RBP_HOTSPOT = 'L7416435';
const RBP_TTL_META = 604800;      // 7 days

function rbp_clean_code($v){
    $v = strtolower(trim((string)$v));
    return preg_replace('/[^a-z0-9_-]/','',$v);
}
function rbp_clean_text($v,$limit=180){
    $v = trim((string)$v);
    $v = preg_replace('/[\x00-\x1F\x7F]/u','',$v);
    return function_exists('mb_substr') ? mb_substr($v,0,$limit,'UTF-8') : substr($v,0,$limit);
}
function rbp_alias($code){
    $a = [
        'blackcu'=>'eurbla2','blackci'=>'eurbla2','blackcap'=>'eurbla2',
        'comquar'=>'comqua','comquai'=>'comqua',
        'cowpig'=>'cowpig1','woodpigeon'=>'cowpig1','cowpig1'=>'cowpig1',
        'rocpig'=>'rocpig1','compig'=>'rocpig1','rocpig1'=>'rocpig1',
        'rocbun'=>'rocbun1','rocbun1'=>'rocbun1',
        'rook'=>'rook1','rook1'=>'rook1',
        'watpip'=>'watpip1','watpipi'=>'watpip1','watpipr'=>'watpip1','watpip1'=>'watpip1',
        'wiltit'=>'wiltit1','wilitt'=>'wiltit1','wilitti'=>'wiltit1','wiltit1'=>'wiltit1',
        'miswoo'=>'miswoo1','miswoo1'=>'miswoo1',
        'coaltit2'=>'coatit2','coatit2'=>'coatit2',
        'monhar'=>'monhar1','monhar1'=>'monhar1',
        'tawowl'=>'tawowl1','tawowl1'=>'tawowl1',
        'loeowl'=>'loeowl1','loeowl1'=>'loeowl1',
        'grypar'=>'grypar1','grypar1'=>'grypar1',
        'kestrel1'=>'eurkes1','comkes'=>'eurkes1','eurkes1'=>'eurkes1',
        'gybwoo1'=>'spofly1','spofly'=>'spofly1',
        'phooch'=>'blared1','blackr1'=>'blared1',
        'yellow2'=>'yelham1','yelham2'=>'yelham1',
        'trepip'=>'treepi',
        'eugori2'=>'golori1',
        'goldfi'=>'eurgol','serin1'=>'eurser1','chaffi'=>'comcha',
        'nuthat'=>'eurnut2','woodla'=>'woolar1','redbac'=>'rebshr1','whiwag1'=>'whiwag','grewag'=>'grywag',
        'nightr1'=>'eurnig1','nighti'=>'eurnig1',
        'hoopoe'=>'eurhoo'
    ];
    return $a[$code] ?? $code;
}
function rbp_cache_dir($sub='meta'){
    $d = __DIR__ . '/ebird-cache/rajac-bird-photo-' . preg_replace('/[^a-z0-9_-]/i','',$sub);
    if(!is_dir($d)) @mkdir($d,0755,true);
    return $d;
}
function rbp_cache_get($file,$ttl){
    if(is_file($file) && time()-filemtime($file) < $ttl){
        $j = json_decode((string)@file_get_contents($file), true);
        if(is_array($j)) return $j;
    }
    return null;
}
function rbp_cache_put($file,$data){
    @file_put_contents($file, json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}
function rbp_fetch($url,&$status=null,$binary=false){
    $status = 0;
    $ua = 'Mozilla/5.0 (compatible; PIO-Rajac-BirdPhoto/5.0; +https://piorajac.rs/)';
    $accept = $binary ? 'image/avif,image/webp,image/apng,image/jpeg,image/png,image/*,*/*;q=0.8' : 'application/json,text/html,*/*;q=0.8';
    if(function_exists('curl_init')){
        $ch = curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_FOLLOWLOCATION=>true,
            CURLOPT_MAXREDIRS=>8,
            CURLOPT_CONNECTTIMEOUT=>10,
            CURLOPT_TIMEOUT=>35,
            CURLOPT_USERAGENT=>$ua,
            CURLOPT_ENCODING=>'',
            CURLOPT_HTTPHEADER=>[
                'Accept: '.$accept,
                'Accept-Language: en-US,en;q=0.9,sr;q=0.8',
                'Referer: https://ebird.org/hotspot/'.RBP_HOTSPOT.'/illustrated-checklist'
            ],
            CURLOPT_SSL_VERIFYPEER=>true,
            CURLOPT_SSL_VERIFYHOST=>2,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if($body !== false && $status >= 200 && $status < 400) return (string)$body;
    }
    $ctx = stream_context_create(['http'=>[
        'method'=>'GET',
        'timeout'=>35,
        'ignore_errors'=>true,
        'header'=>"User-Agent: $ua\r\nAccept: $accept\r\nAccept-Language: en-US,en;q=0.9,sr;q=0.8\r\nReferer: https://ebird.org/hotspot/".RBP_HOTSPOT."/illustrated-checklist\r\n"
    ]]);
    $body = @file_get_contents($url,false,$ctx);
    if(isset($http_response_header) && is_array($http_response_header)){
        foreach($http_response_header as $h){ if(preg_match('/^HTTP\/\S+\s+(\d+)/',$h,$m)){ $status=(int)$m[1]; break; } }
    }
    return $body !== false ? (string)$body : '';
}
function rbp_find_asset($x){
    $q = [$x]; $n = 0;
    while($q && $n++ < 30000){
        $v = array_shift($q);
        if(is_array($v)){
            foreach(['assetId','assetID','asset_id','catalogId','catalogID','catalog_id','catId','catID','mediaId','mediaID','mlCatalogNumber','MLCatalogNumber','asset_id_str','id'] as $k){
                if(isset($v[$k]) && preg_match('/(?:ML)?(\d{5,})/i',(string)$v[$k],$m)) return $m[1];
            }
            foreach(['previewUrl','thumbnailUrl','imageUrl','mediaUrl','url','src','largeUrl','large_url','downloadUrl','originalUrl'] as $k){
                if(isset($v[$k]) && is_string($v[$k])){
                    if(preg_match('/cdn\.download\.ams\.birds\.cornell\.edu\/api\/v1\/asset\/(\d{5,})/i',$v[$k],$m)) return $m[1];
                    if(preg_match('/macaulaylibrary\.org\/asset\/(\d{5,})/i',$v[$k],$m)) return $m[1];
                    if(preg_match('/\bML(\d{5,})\b/i',$v[$k],$m)) return $m[1];
                }
            }
            foreach($v as $vv){ if(is_array($vv) || is_string($vv) || is_numeric($vv)) $q[] = $vv; }
        } elseif(is_string($v) || is_numeric($v)) {
            $v=(string)$v;
            if(preg_match('/cdn\.download\.ams\.birds\.cornell\.edu\/api\/v1\/asset\/(\d{5,})/i',$v,$m)) return $m[1];
            if(preg_match('/macaulaylibrary\.org\/asset\/(\d{5,})/i',$v,$m)) return $m[1];
            if(preg_match('/\bML(\d{5,})\b/i',$v,$m)) return $m[1];
        }
    }
    return '';
}
function rbp_asset_image_url($asset,$size){
    $asset = preg_replace('/[^0-9]/','',(string)$asset);
    $size = max(320,min(1200,(int)$size));
    return $asset ? 'https://cdn.download.ams.birds.cornell.edu/api/v1/asset/'.$asset.'/'.$size : '';
}
function rbp_hotspot_gallery($code){
    return 'https://search.macaulaylibrary.org/catalog?mediaType=photo&sort=rating_rank_desc&regionCode='.rawurlencode(RBP_HOTSPOT).'&searchField=hotspot&taxonCode='.rawurlencode($code);
}
function rbp_global_gallery($code){
    return 'https://search.macaulaylibrary.org/catalog?mediaType=photo&sort=rating_rank_desc&taxonCode='.rawurlencode($code);
}
function rbp_find_macaulay_asset_for_url($url,&$trace){
    $s = 0;
    $body = rbp_fetch($url,$s,false);
    $trace[] = ['status'=>$s,'url'=>$url,'bytes'=>strlen($body)];
    if(!$body) return '';
    $json = json_decode($body,true);
    $asset = is_array($json) ? rbp_find_asset($json) : '';
    if(!$asset && preg_match('/cdn\.download\.ams\.birds\.cornell\.edu\/api\/v1\/asset\/(\d{5,})/i',$body,$m)) $asset=$m[1];
    if(!$asset && preg_match('/macaulaylibrary\.org\/asset\/(\d{5,})/i',$body,$m)) $asset=$m[1];
    if(!$asset && preg_match('/\bML(\d{5,})\b/i',$body,$m)) $asset=$m[1];
    return $asset;
}
function rbp_urls_for($code,$scope){
    $code=rawurlencode($code);
    if($scope==='rajac'){
        $base = 'https://search.macaulaylibrary.org/catalog';
        return [
            $base.'.json?searchField=hotspot&regionCode='.rawurlencode(RBP_HOTSPOT).'&taxonCode='.$code.'&count=3&mediaType=p&sort=rating_rank_desc',
            $base.'.json?searchField=hotspot&regionCode='.rawurlencode(RBP_HOTSPOT).'&taxonCode='.$code.'&count=3&mediaType=photo&sort=rating_rank_desc',
            $base.'?mediaType=photo&sort=rating_rank_desc&regionCode='.rawurlencode(RBP_HOTSPOT).'&searchField=hotspot&taxonCode='.$code.'&view=grid',
            $base.'?regionCode='.rawurlencode(RBP_HOTSPOT).'&searchField=hotspot&taxonCode='.$code.'&view=list'
        ];
    }
    $base = 'https://search.macaulaylibrary.org/catalog';
    return [
        $base.'.json?searchField=species&taxonCode='.$code.'&count=3&mediaType=p&sort=rating_rank_desc',
        $base.'.json?searchField=species&taxonCode='.$code.'&count=3&mediaType=photo&sort=rating_rank_desc',
        $base.'?mediaType=photo&sort=rating_rank_desc&taxonCode='.$code.'&view=grid',
        $base.'?searchField=species&taxonCode='.$code.'&view=list'
    ];
}
function rbp_try_macaulay_by_code($code,$size,&$trace){
    if(!$code) return null;
    foreach(rbp_urls_for($code,'rajac') as $u){
        $asset = rbp_find_macaulay_asset_for_url($u,$trace);
        if($asset) return ['source'=>'rajac-macaulay','assetId'=>$asset,'image'=>rbp_asset_image_url($asset,$size),'gallery'=>rbp_hotspot_gallery($code),'page'=>'https://macaulaylibrary.org/asset/'.$asset];
    }
    foreach(rbp_urls_for($code,'global') as $u){
        $asset = rbp_find_macaulay_asset_for_url($u,$trace);
        if($asset) return ['source'=>'global-macaulay','assetId'=>$asset,'image'=>rbp_asset_image_url($asset,$size),'gallery'=>rbp_global_gallery($code),'page'=>'https://macaulaylibrary.org/asset/'.$asset];
    }
    return null;
}
function rbp_resolve_media($code,$latin,$title,$size,&$trace){
    $code = rbp_alias($code);
    $key = md5(strtolower($code.'|'.$latin.'|'.$title.'|'.$size.'|v5-p-first'));
    $cache = rbp_cache_dir('meta').'/'.$key.'.json';
    $cached = rbp_cache_get($cache,RBP_TTL_META);
    if($cached) return $cached;
    $media = rbp_try_macaulay_by_code($code,$size,$trace);
    if(!$media) $media = ['source'=>'none','image'=>'','gallery'=>$code?rbp_hotspot_gallery($code):'https://ebird.org/hotspot/'.RBP_HOTSPOT.'/illustrated-checklist'];
    $media['taxonCode']=$code;
    $media['trace']=$trace;
    $media['cachedAt']=gmdate('c');
    rbp_cache_put($cache,$media);
    return $media;
}
function rbp_stream_image($media,$code,$size){
    $url = $media['image'] ?? '';
    if(!$url) rbp_blank(404);
    $asset = $media['assetId'] ?? '';
    $safe = preg_replace('/[^a-z0-9_-]/i','', $code ?: 'bird');
    $tag = preg_replace('/[^a-z0-9_-]/i','', strtolower(($media['source']??'img').'-'.($asset?:md5($url))));
    $file = rbp_cache_dir('images').'/'.$safe.'-'.$tag.'-'.$size.'.jpg';
    if(is_file($file) && filesize($file) > 500){
        header('Content-Type: image/jpeg');
        header('Cache-Control: public, max-age=604800');
        readfile($file);
        exit;
    }
    $s=0; $body = rbp_fetch($url,$s,true);
    if($body && strlen($body) > 500 && $s >= 200 && $s < 400){
        @file_put_contents($file,$body);
        header('Content-Type: image/jpeg');
        header('Cache-Control: public, max-age=604800');
        echo $body;
        exit;
    }
    header('Cache-Control: public, max-age=86400');
    header('Location: '.$url, true, 302);
    exit;
}
function rbp_blank($code=404){
    http_response_code($code);
    header('Content-Type:image/svg+xml; charset=utf-8');
    header('Cache-Control: public, max-age=1800');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="600"><rect width="900" height="600" fill="#eee5d5"/><text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" fill="#7c7464" font-family="serif" font-size="34">фото</text></svg>';
    exit;
}

$mode = rbp_clean_code($_GET['mode'] ?? 'image');
$code = rbp_alias(rbp_clean_code($_GET['taxonCode'] ?? $_GET['speciesCode'] ?? $_GET['code'] ?? ''));
$latin = rbp_clean_text($_GET['latin'] ?? '');
$title = rbp_clean_text($_GET['title'] ?? '');
$size = max(320,min(1200,(int)($_GET['size'] ?? 720)));
$trace = [];
$media = rbp_resolve_media($code,$latin,$title,$size,$trace);

if($mode === 'debug'){
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache');
    echo json_encode(['ok'=>!empty($media['image']),'priority'=>'1 Rajac hotspot L7416435 Macaulay photo; 2 global Macaulay photo; no Wikipedia/Commons','request'=>['taxonCode'=>$code,'latin'=>$latin,'title'=>$title,'size'=>$size],'media'=>$media],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
    exit;
}
if($mode === 'json'){
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    echo json_encode(['ok'=>!empty($media['image']),'taxonCode'=>$code,'source'=>$media['source']??'','image'=>$media['image']??'','gallery'=>$media['gallery']??'','assetId'=>$media['assetId']??'','page'=>$media['page']??''],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
if($mode === 'go'){
    $target = $media['gallery'] ?? '';
    if(!$target && $code) $target = rbp_hotspot_gallery($code);
    if(!$target) $target = 'https://ebird.org/hotspot/'.RBP_HOTSPOT.'/illustrated-checklist';
    header('Location: '.$target, true, 302);
    exit;
}
rbp_stream_image($media,$code,$size);

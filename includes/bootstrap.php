<?php
declare(strict_types=1);

if (basename((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === 'bootstrap.php') {
    http_response_code(403); exit;
}
define('PLASMART_APP_CONTEXT', true);

function is_https_request(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}
function send_security_headers(): void {
    if (headers_sent()) return;
    if (function_exists('header_remove')) header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: blob: https:; connect-src 'self'; media-src 'self'; worker-src 'none'");
    if (is_https_request()) header('Strict-Transport-Security: max-age=31536000');
}
send_security_headers();

function data_file(string $name): string { return __DIR__ . '/../data/' . $name . '.json.php'; }
function load_json(string $name, array $fallback=[]): array {
    $f=data_file($name);
    if(!is_file($f) || !is_readable($f)) return $fallback;
    $raw=(string)file_get_contents($f); $d=json_decode(substr($raw,strpos($raw,"\n")+1),true);
    return is_array($d)?$d:$fallback;
}
function h(?string $v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function xml_h(string $v): string { return htmlspecialchars($v,ENT_XML1|ENT_QUOTES,'UTF-8'); }
function money($n): string { return 'Rp ' . number_format((float)$n,0,',','.'); }
function products(): array { static $d; return $d ??= load_json('products'); }
function site(): array { static $d; return $d ??= load_json('site'); }
function page_defaults(array $data): array {
    if (isset($data['tentang']) && is_array($data['tentang'])) {
        $data['tentang']=array_merge([
            'google_url'=>'https://share.google/VWPGb266Djz004cAp',
            'google_label'=>'Lihat CV PLASMART di Google',
        ],$data['tentang']);
    }
    return $data;
}
function pages(): array { static $d; return $d ??= page_defaults(load_json('pages')); }
function categories(): array { static $d; return $d ??= load_json('categories'); }
function flash_sale(): array { static $d; return $d ??= load_json('flash-sale'); }
function product_by_slug(string $slug): ?array { foreach(products() as $p) if((($p['slug']??'')===$slug || in_array($slug,$p['aliases']??[],true)) && ($p['active']??true)) return $p; return null; }

function product_og_image(array $p): string {
    $img=trim((string)($p['image']??''));
    if($img==='') return asset_url((string)(site()['logo']??'assets/images/logo.webp'));
    if(preg_match('#^https?://#i',$img)) return $img;
    $rel=ltrim($img,'/');
    $stem=pathinfo($rel,PATHINFO_FILENAME);
    $candidate='';
    if(str_starts_with($rel,'assets/images/')) $candidate='assets/og/'.$stem.'.jpg';
    elseif(str_starts_with($rel,'uploads/')) $candidate='uploads/og/'.$stem.'.jpg';
    if($candidate!=='' && is_file(__DIR__.'/../'.$candidate)) return asset_url($candidate);
    return asset_url($rel);
}
function og_image_type(string $url): string {
    $ext=strtolower(pathinfo((string)(parse_url($url,PHP_URL_PATH)??''),PATHINFO_EXTENSION));
    return match($ext){'jpg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif',default=>'image/jpeg'};
}

function safe_host(): string {
    $server=(string)($_SERVER['SERVER_NAME']??'');
    $http=(string)($_SERVER['HTTP_HOST']??'');
    $server=preg_replace('/[^A-Za-z0-9.\-:\[\]]/','',$server) ?: '';
    $http=preg_replace('/[^A-Za-z0-9.\-:\[\]]/','',$http) ?: '';
    if($server!=='' && $http!=='' && (strcasecmp($http,$server)===0 || str_starts_with(strtolower($http),strtolower($server).':'))) return $http;
    return $server!==''?$server:($http!==''?$http:'localhost');
}
function site_dir(): string {
    $doc=!empty($_SERVER['DOCUMENT_ROOT'])?realpath((string)$_SERVER['DOCUMENT_ROOT']):false;
    $app=realpath(__DIR__.'/..');
    if($doc&&$app&&str_starts_with(str_replace('\\','/',$app),rtrim(str_replace('\\','/',$doc),'/'))){
        $rel=substr(str_replace('\\','/',$app),strlen(rtrim(str_replace('\\','/',$doc),'/')));
        return $rel==='/'?'':'/'.trim($rel,'/');
    }
    $script=str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME']??'/'));
    $dir=rtrim(dirname($script),'/');
    if(str_ends_with($dir,'/admin')) $dir=substr($dir,0,-6);
    return ($dir==='.'||$dir==='/')?'':$dir;
}
function base_url(): string { return (is_https_request()?'https':'http').'://'.safe_host().site_dir(); }
function canonical_base_url(): string {
    $configured=trim((string)(site()['seo']['canonical_base']??''));
    if($configured!=='' && preg_match('#^https?://[A-Za-z0-9.\-:\[\]]+(?:/[^\s]*)?$#',$configured)) return rtrim($configured,'/');
    return rtrim(base_url(),'/');
}
function asset_url(string $path): string {
    if(preg_match('#^https?://#i',$path)) return $path;
    return rtrim(base_url(),'/').'/'.ltrim($path,'/');
}
function current_page(): string { return basename($_SERVER['PHP_SELF']??''); }
function public_url(string $path=''): string { return rtrim(canonical_base_url(),'/').'/'.ltrim($path,'/'); }
function product_public_url(array|string $p): string { $slug=is_array($p)?(string)($p['slug']??''):$p; return public_url('product.php?slug='.rawurlencode($slug)); }
function category_public_url(array|string $c): string { $slug=is_array($c)?(string)($c['slug']??''):$c; return public_url('category.php?slug='.rawurlencode($slug)); }
function file_lastmod(string $path): string { $t=@filemtime($path); return gmdate('c',$t?:time()); }
function seo_json(array $data): string { return (string)json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); }
function page_canonical(string $pageKey): string {
    $map=['home'=>'','shop'=>'shop.php','categories'=>'categories.php','promo'=>'promo.php','cara-belanja'=>'cara-belanja.php','delivery'=>'delivery.php','tentang'=>'tentang.php','faq'=>'faq.php','contact'=>'contact.php','terms'=>'terms.php','privacy'=>'privacy.php','cart'=>'cart.php','checkout'=>'checkout.php','success'=>'order-success.php','search'=>'search.php','404'=>'404.php'];
    return public_url($map[$pageKey]??'');
}
function noindex_page(string $pageKey): bool { return in_array($pageKey,['cart','checkout','success','search','404'],true); }
function asset_version(string $path): string { return $path.'?v='.(string)(@filemtime(__DIR__.'/../'.$path)?:1); }
function active_products(): array { return array_values(array_filter(products(),fn($p)=>($p['active']??true)!==false)); }
function active_categories(): array { return array_values(array_filter(categories(),fn($c)=>($c['active']??true)!==false)); }
function wa_url(string $message=''): string { $num=preg_replace('/\D/','',(string)(site()['contact']['whatsapp']??'')); if(str_starts_with($num,'0'))$num='62'.substr($num,1); return 'https://wa.me/'.$num.($message!==''?'?text='.rawurlencode($message):''); }
require_once __DIR__.'/flash-sale.php';

function responsive_srcset(string $path): string {
 $mobile=preg_replace('/\.webp$/','-mobile.webp',$path);
 if($mobile!==$path && is_file(__DIR__.'/../'.$mobile)) return 'srcset="'.h($mobile).' 768w, '.h($path).' 1360w"';
 return '';
}

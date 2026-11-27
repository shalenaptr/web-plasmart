<?php
declare(strict_types=1);
if (basename((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === 'bootstrap.php') {
    http_response_code(403); exit;
}
define('PLASMART_ADMIN_CONTEXT', true);
require_once __DIR__.'/../includes/bootstrap.php';
require_once __DIR__.'/session-storage.php';
$secure=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||($_SERVER['SERVER_PORT']??'')==='443';
$cookiePath='/';
if($cookiePath==='./')$cookiePath='/';
session_set_cookie_params(['lifetime'=>0,'path'=>$cookiePath,'secure'=>$secure,'httponly'=>true,'samesite'=>'Strict']);
prepare_admin_session_storage();
session_name('plasmart_admin_session');
// session_start options work even if the host disables the ini_set function.
if (!@session_start(['use_strict_mode'=>1,'use_only_cookies'=>1,'use_trans_sid'=>0])) admin_session_unavailable();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive');
function admin_config(): array{return require __DIR__.'/config.php';}
function logged_in():bool{return !empty($_SESSION['plasmart_admin']);}
function require_login():void{
    if(!logged_in()){header('Location:index.php');exit;}
    $now=time(); $last=(int)($_SESSION['last_activity']??$now); $ua=hash('sha256',(string)($_SERVER['HTTP_USER_AGENT']??''));
    if(($now-$last)>7200 || !hash_equals((string)($_SESSION['ua_hash']??''),$ua)){
        $_SESSION=[]; session_destroy(); header('Location:index.php?expired=1'); exit;
    }
    $_SESSION['last_activity']=$now;
}
function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function verify_csrf():void{if(empty($_SESSION['csrf'])||empty($_POST['csrf'])||!hash_equals((string)$_SESSION['csrf'],(string)$_POST['csrf'])){http_response_code(403);if(basename((string)($_SERVER['SCRIPT_FILENAME']??''))==='api.php'){header('Content-Type: application/json; charset=utf-8');exit(json_encode(['ok'=>false,'message'=>'Sesi berubah. Muat ulang halaman admin.']));}exit('Sesi tidak valid. Muat ulang halaman.');}}
function save_json(string $name,array $data):bool{
    $f=data_file($name); $tmp=dirname($f).'/.tmp-'.bin2hex(random_bytes(8)).'.php';
    $json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); if($json===false)return false; $json="<?php http_response_code(403); exit; ?>\n".$json;
    if(@file_put_contents($tmp,$json,LOCK_EX)===false){@unlink($tmp);return false;}
    if(function_exists('chmod')) @chmod($tmp,0644);
    if(!@rename($tmp,$f)){@unlink($tmp);return false;} return true;
}
function login_rate_file():string{$ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');return __DIR__.'/../data/.login-rate-'.hash('sha256',$ip).'.json.php';}
function login_rate_state():array{$f=login_rate_file();if(!is_file($f))return ['fails'=>0,'first'=>0,'blocked_until'=>0];$raw=(string)@file_get_contents($f);$d=json_decode(substr($raw,strpos($raw,"\n")+1),true);return is_array($d)?$d:['fails'=>0,'first'=>0,'blocked_until'=>0];}
function login_blocked():bool{$s=login_rate_state();return (int)($s['blocked_until']??0)>time();}
function login_fail():void{$f=login_rate_file();$s=login_rate_state();$now=time();if(($now-(int)($s['first']??0))>900)$s=['fails'=>0,'first'=>$now,'blocked_until'=>0];if(empty($s['first']))$s['first']=$now;$s['fails']=(int)($s['fails']??0)+1;if($s['fails']>=5)$s['blocked_until']=$now+900;@file_put_contents($f,"<?php http_response_code(403); exit; ?>\n".json_encode($s),LOCK_EX);}
function login_clear():void{@unlink(login_rate_file());}
function clip(string $v,int $max):string{$v=trim(strip_tags($v));return function_exists('mb_substr')?mb_substr($v,0,$max,'UTF-8'):substr($v,0,$max);}
function safe_image_path(string $v):string{
 $v=trim($v);if($v==='')return '';
 if(str_contains($v,'..')||str_contains($v,'\\'))return '';
 if(preg_match('#^(?:images|assets/images|uploads)/[a-zA-Z0-9/_-]+\.(?:webp|png|jpe?g)$#',$v)&&is_file(__DIR__.'/../'.$v))return $v;
 return '';
}
function safe_web_url(string $url):string { $url=trim($url); return $url===''?'':((filter_var($url,FILTER_VALIDATE_URL)&&preg_match('#^https?://#i',$url))?$url:''); }
?>

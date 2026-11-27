<?php
require __DIR__.'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');echo json_encode(['ok'=>false,'message'=>'Gunakan POST.']);exit;}
if(!logged_in()){http_response_code(401);echo json_encode(['ok'=>false,'message'=>'Silakan masuk kembali.']);exit;}
require_login();verify_csrf();
function payload():array{ $raw=(string)($_POST['payload']??'{}');if(strlen($raw)>1048576)throw new Exception('Data terlalu besar.');$value=json_decode($raw,true);if(!is_array($value))throw new Exception('Format data tidak valid.');return $value; }
function slugify(string $s):string{ return trim((string)preg_replace('/[^a-z0-9]+/','-',strtolower(trim($s))),'-'); }
function number_price($v):int{ if(!is_numeric($v)||!is_finite((float)$v)||(float)$v<0||(float)$v>1000000000)throw new Exception('Harga Rupiah tidak valid.');return (int)round((float)$v); }
function wholesale_rule($value,int $normal):?array {
 if($value===null||$value===[]||$value==='')return null;
 if(!is_array($value))throw new Exception('Aturan harga grosir tidak valid.');
 $minimum=filter_var($value['min_qty']??null,FILTER_VALIDATE_INT);
 if($minimum===false||$minimum<2||$minimum>1000000)throw new Exception('Minimal grosir harus bilangan bulat 2 sampai 1.000.000.');
 $price=number_price($value['price']??'');
 if($price<=0||$price>=$normal)throw new Exception('Harga grosir harus lebih dari nol dan di bawah harga normal.');
 return ['min_qty'=>$minimum,'price'=>$price];
}
function write_or_fail(string $name,array $data):void {if(!save_json($name,$data))throw new Exception('Tidak dapat menyimpan data. Periksa izin folder data.');}
function overlay_color(string $v):string {
 if(preg_match('/^#[a-f0-9]{6}$/i',$v))return $v;
 if(preg_match('/^rgba\(\s*(\d{1,6})\s*,\s*(\d{1,6})\s*,\s*(\d{1,6})\s*,\s*(0?(?:\.\d+)?|1(?:\.0+)?)\s*\)$/',$v,$m)&&max((int)$m[1],(int)$m[2],(int)$m[3])<=255)return $v;
 throw new Exception('Warna overlay tidak valid. Gunakan rgba atau warna hex.');
}
function upload_webp(bool $page):array{
 $f=$_FILES['image']??null;if(!$f||$f['error']!==UPLOAD_ERR_OK)throw new Exception('Pilih gambar yang valid.');
 if($f['size']>8*1024*1024)throw new Exception('Ukuran gambar maksimal 8 MB.');
 $info=@getimagesize($f['tmp_name']);if(!$info||!in_array($info['mime']??'',['image/jpeg','image/png','image/webp'],true))throw new Exception('Format harus JPG, PNG, atau WebP.');
 [$w,$h]=$info;if($w<1||$h<1||$w>8000||$h>8000||$w*$h>24000000)throw new Exception('Dimensi gambar terlalu besar.');
 $dir=$page?'uploads/pages':'uploads';$full=__DIR__.'/../'.$dir;if(!is_dir($full)&&!@mkdir($full,0755,true))throw new Exception('Folder upload tidak dapat dibuat.');
 $stem=substr(slugify(pathinfo((string)$f['name'],PATHINFO_FILENAME)),0,100)?:'gambar';$stem.='-'.bin2hex(random_bytes(6));$path=$dir.'/'.$stem.'.webp';$dest=__DIR__.'/../'.$path;
 if(!function_exists('imagewebp')){
  if(($info['mime']??'')!=='image/webp'||max($w,$h)>2200)throw new Exception('Aktifkan PHP GD/WebP atau gunakan upload WebP yang sudah dikompres.');
  if(!move_uploaded_file($f['tmp_name'],$dest))throw new Exception('Gambar gagal disimpan.');
 }else{
  $im=@imagecreatefromstring((string)file_get_contents($f['tmp_name']));if(!$im)throw new Exception('Gambar tidak dapat dibaca.');
  if(($info['mime']??'')==='image/jpeg'&&function_exists('exif_read_data')){ $exif=@exif_read_data($f['tmp_name']);$orientation=(int)($exif['Orientation']??1);if(in_array($orientation,[3,6,8],true)){ $angle=$orientation===3?180:($orientation===6?-90:90);$rot=imagerotate($im,$angle,0);if($rot){imagedestroy($im);$im=$rot;$w=imagesx($im);$h=imagesy($im);} } }
  $maxSide=$page?2200:1600;$ratio=min(1,$maxSide/max($w,$h));$nw=max(1,(int)round($w*$ratio));$nh=max(1,(int)round($h*$ratio));
  $canvas=imagecreatetruecolor($nw,$nh);imagealphablending($canvas,false);imagesavealpha($canvas,true);$alpha=imagecolorallocatealpha($canvas,255,255,255,127);imagefill($canvas,0,0,$alpha);imagecopyresampled($canvas,$im,0,0,0,0,$nw,$nh,$w,$h);imagedestroy($im);
  if(!imagewebp($canvas,$dest,84)){imagedestroy($canvas);throw new Exception('Konversi WebP gagal.');}
  if(!$page&&function_exists('imagejpeg')){$ogDir=__DIR__.'/../uploads/og';if(!is_dir($ogDir))@mkdir($ogDir,0755,true);$og=imagecreatetruecolor(1200,630);$bg=imagecolorallocate($og,248,244,236);imagefill($og,0,0,$bg);$sc=min(1080/$nw,560/$nh);$ow=(int)round($nw*$sc);$oh=(int)round($nh*$sc);imagecopyresampled($og,$canvas,(int)((1200-$ow)/2),(int)((630-$oh)/2),0,0,$ow,$oh,$nw,$nh);imagejpeg($og,$ogDir.'/'.$stem.'.jpg',88);imagedestroy($og);}
  imagedestroy($canvas);
 }
 if(function_exists('chmod')) @chmod($dest,0644);return ['ok'=>true,'path'=>$path,'format'=>'webp','bytes'=>filesize($dest)];
}
$lock=null;
try{
 $action=(string)($_POST['action']??'');
 if(in_array($action,['upload_image','upload_category_image','upload_page_image'],true)){echo json_encode(upload_webp($action==='upload_page_image'));exit;}
 $lock=fopen(__DIR__.'/../data/.write-lock.php','c');if(!$lock||!flock($lock,LOCK_EX))throw new Exception('Penyimpanan sedang sibuk. Coba kembali.');
 if($action==='save_site'){
  $d=payload();$old=load_json('site');$new=$old;$new['brand']=clip((string)($d['brand']??''),120);if($new['brand']==='')throw new Exception('Nama toko wajib diisi.');
  $logo=trim((string)($d['logo']??''));$new['logo']=safe_image_path($logo);if($logo!==''&&$new['logo']==='')throw new Exception('Logo tidak valid. Gunakan gambar hasil upload.');
  $minimum=filter_var($d['shipping']['free_minimum']??($old['shipping']['free_minimum']??200000),FILTER_VALIDATE_INT);if($minimum===false||$minimum<0||$minimum>1000000000)throw new Exception('Minimal gratis ongkir tidak valid.');$new['shipping']['free_minimum']=$minimum;
  foreach(['primary','navy','accent'] as $k){$v=(string)($d['colors'][$k]??'');if(!preg_match('/^#[0-9a-f]{6}$/i',$v))throw new Exception('Gunakan warna hex enam digit.');$new['colors'][$k]=$v;}
  foreach(['category_overlay','footer_overlay'] as $k)$new['colors'][$k]=overlay_color((string)($d['colors'][$k]??''));
  $n=preg_replace('/\D/','',(string)($d['contact']['whatsapp']??''));if(str_starts_with($n,'0'))$n='62'.substr($n,1);if(strlen($n)<8||strlen($n)>15)throw new Exception('Nomor WhatsApp tidak valid.');$new['contact']['whatsapp']=$n;
  $new['contact']['whatsapp_label']=str_starts_with($n,'62')?'0'.substr($n,2):'+'.$n;
  foreach(['address','hours'] as $k)$new['contact'][$k]=clip((string)($d['contact'][$k]??''),$k==='address'?1000:120);
  $email=trim((string)($d['contact']['email']??''));if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))throw new Exception('Email tidak valid.');$new['contact']['email']=$email;
  foreach(['instagram','tiktok'] as $k)$new['social'][$k]=clip((string)($d['social'][$k]??''),120);
  foreach(['instagram_url','tiktok_url','facebook_url','shopee_url'] as $k){$v=trim((string)($d['social'][$k]??''));$safe=safe_web_url($v);if($v!==''&&$safe==='')throw new Exception('Tautan media sosial tidak valid.');$new['social'][$k]=$safe;}
  $canonical=trim((string)($d['seo']['canonical_base']??''));if($canonical!==''&&!preg_match('#^https?://[a-z0-9.-]+(?::[0-9]+)?(?:/[a-z0-9._/-]*)?$#i',$canonical))throw new Exception('Alamat website tidak valid.');$new['seo']['canonical_base']=rtrim($canonical,'/');$new['seo']['default_title']=$new['brand'].' | Plastik, Alat Tulis & Perlengkapan Hajatan';$new['powered_by']=clip((string)($d['powered_by']??''),120);
  write_or_fail('site',$new);echo json_encode(['ok'=>true]);exit;
 }
 if($action==='save_pages'){
  $d=payload();$old=page_defaults(load_json('pages'));$clean=[];
  foreach($old as $key=>$pg){$src=$d[$key]??$pg;foreach($pg as $field=>$value){if(!is_string($value))continue;$v=(string)($src[$field]??$value);if(str_ends_with($field,'_image')){ $v=safe_image_path($v);if($v==='')throw new Exception('Upload gambar halaman yang valid.'); }elseif(str_ends_with($field,'_url')){$raw=trim($v);$v=safe_web_url($raw);if($raw!==''&&$v==='')throw new Exception('Tautan halaman harus berupa URL http atau https yang valid.');}else $v=clip($v,$field==='body'||$field==='about_body'?12000:2200);$pg[$field]=$v;}$clean[$key]=$pg;}
  write_or_fail('pages',$clean);echo json_encode(['ok'=>true]);exit;
 }
 if($action==='save_categories'){
  $d=payload();if(count($d)>100)throw new Exception('Maksimal 100 kategori.');$cats=[];$slugs=[];$mapping=[];
  foreach($d as $row){$name=clip((string)($row['name']??''),120);if($name==='')throw new Exception('Nama kategori wajib diisi.');$slug=slugify((string)(($row['slug']??'')?:$name));if($slug===''||isset($slugs[$slug]))throw new Exception('Slug kategori harus unik dan tidak kosong.');$slugs[$slug]=true;$img=trim((string)($row['image']??''));$image=safe_image_path($img);if($img!==''&&$image==='')throw new Exception('Gambar kategori tidak valid.');$prev=null;foreach(load_json('categories') as $existing)if($existing['slug']===($row['previous_slug']??$slug))$prev=$existing;$cats[]=array_merge($prev??[],['active'=>!empty($row['active']),'name'=>$name,'slug'=>$slug,'description'=>clip((string)($row['description']??''),600),'image'=>$image]);$previous=(string)($row['previous_slug']??$slug);if($previous!=='')$mapping[$previous]=$slug;}
  $products=load_json('products');$names=array_column($cats,'name','slug');foreach($products as &$pr){$old=(string)$pr['category_slug'];$new=$mapping[$old]??$old;if(!isset($names[$new]))throw new Exception('Kategori masih dipakai oleh '.$pr['title'].'. Pindahkan produknya ke kategori lain sebelum menghapus.');$pr['category_slug']=$new;$pr['category']=$names[$new];}unset($pr);
  write_or_fail('categories',$cats);write_or_fail('products',$products);echo json_encode(['ok'=>true]);exit;
 }
 if($action==='save_product'){
  $d=payload();$all=load_json('products');$id=(int)($d['id']??0);$old=null;foreach($all as $pr)if((int)$pr['id']===$id)$old=$pr;
  if($id>0&&!$old)throw new Exception('Produk tidak ditemukan. Muat ulang panel.');$p=$old??[];$p['title']=clip((string)($d['title']??''),200);if($p['title']==='')throw new Exception('Nama produk wajib diisi.');$p['name']=$p['title'];$p['slug']=slugify((string)(($d['slug']??'')?:$p['title']));if($p['slug']==='')throw new Exception('Slug produk tidak valid.');
  $p['sku']=clip((string)($d['sku']??''),80);foreach($all as $pr)if((int)$pr['id']!==$id){if($pr['slug']===$p['slug'])throw new Exception('Slug produk sudah digunakan.');if($p['sku']!==''&&strcasecmp($p['sku'],(string)$pr['sku'])===0)throw new Exception('SKU produk sudah digunakan.');}
  $p['price']=number_price($d['price']??'');$p['sale_price']=$old['sale_price']??null;
  $p['category_slug']=(string)($d['category_slug']??'');$cat=null;foreach(load_json('categories') as $c)if($c['slug']===$p['category_slug'])$cat=$c;if(!$cat)throw new Exception('Pilih kategori yang valid.');$p['category']=$cat['name'];
  foreach(['brand'=>120,'weight'=>80,'short_description'=>500,'description'=>12000,'meta_title'=>220,'meta_description'=>500] as $field=>$max)$p[$field]=clip((string)($d[$field]??''),$max);
  $image=trim((string)($d['image']??''));$p['image']=safe_image_path($image);if($image!==''&&$p['image']==='')throw new Exception('Gambar produk tidak valid. Gunakan upload lokal.');$p['alt']=$p['title'];
  $p['variants']=[];foreach((array)($d['variants']??[]) as $v){if(count($p['variants'])>=30)throw new Exception('Maksimal 30 varian.');$name=clip((string)($v['name']??''),140);if($name==='')throw new Exception('Nama varian wajib diisi.');$stock=filter_var($v['stock']??0,FILTER_VALIDATE_INT);if($stock===false||$stock<0||$stock>1000000)throw new Exception('Stok varian harus berupa angka 0 sampai 1.000.000.');$price=number_price($v['price']??'');$p['variants'][]=['name'=>$name,'price'=>$price,'stock'=>$stock,'wholesale'=>wholesale_rule($v['wholesale']??null,$price)];}
  if($p['variants'])$p['price']=min(array_column($p['variants'],'price'));
  $p['wholesale']=wholesale_rule($d['wholesale']??null,$p['price']);
  $stock=filter_var($d['stock']??0,FILTER_VALIDATE_INT);if($stock===false||$stock<0||$stock>1000000)throw new Exception('Stok produk harus berupa angka 0 sampai 1.000.000.');$p['stock']=$stock;
  $imgs=$d['images']??($p['image']!==''?[$p['image']]:[]);if(!is_array($imgs)||count($imgs)>5)throw new Exception('Maksimal 5 foto produk.');$p['images']=[];foreach($imgs as $img){$safe=safe_image_path((string)$img);if($safe==='')throw new Exception('Foto produk tidak valid.');if(!in_array($safe,$p['images'],true))$p['images'][]=$safe;}$p['image']=$p['images'][0]??'';
  $p['aliases']=$old['aliases']??[];if($old&&$old['slug']!==$p['slug'])$p['aliases']=array_values(array_unique([...$p['aliases'],$old['slug']]));
  $p['specs']=[];foreach((array)($d['specs']??($old['specs']??[])) as $spec){if(!is_array($spec)||count($spec)!==2)throw new Exception('Spesifikasi tidak valid.');$p['specs'][]=[clip((string)$spec[0],100),clip((string)$spec[1],500)];}
  if($p['meta_title']==='')$p['meta_title']=$p['title'].' | '.site()['brand'];if($p['meta_description']==='')$p['meta_description']=$p['short_description'];
  $p['featured']=!empty($d['featured']);$p['active']=!empty($d['active']);$p['currency']='IDR';$p['stock_status']='Konfirmasi stok';$p['updated_at']=gmdate('c');$p['created_at']=$old['created_at']??$p['updated_at'];
  if(!$old){$p['id']=max(array_column($all,'id')?:[0])+1;if(!$p['sku'])$p['sku']='PLS-'.str_pad((string)$p['id'],3,'0',STR_PAD_LEFT);$all[]=$p;}else foreach($all as $i=>$pr)if((int)$pr['id']===$id)$all[$i]=$p;
  write_or_fail('products',$all);if($old&&$old['slug']!==$p['slug']){$flash=load_json('flash-sale');$flash['products']=array_map(fn($s)=>$s===$old['slug']?$p['slug']:$s,$flash['products']);if(isset($flash['offers'][$old['slug']])){$flash['offers'][$p['slug']]=$flash['offers'][$old['slug']];unset($flash['offers'][$old['slug']]);}write_or_fail('flash-sale',$flash);}
  echo json_encode(['ok'=>true,'product'=>$p]);exit;
 }
 if($action==='delete_product'){
  $id=(int)($_POST['id']??0);$all=load_json('products');$found=null;foreach($all as $p)if((int)$p['id']===$id)$found=$p;if(!$found)throw new Exception('Produk tidak ditemukan.');write_or_fail('products',array_values(array_filter($all,fn($p)=>(int)$p['id']!==$id)));$f=load_json('flash-sale');$f['products']=array_values(array_filter($f['products'],fn($s)=>$s!==$found['slug']));unset($f['offers'][$found['slug']]);write_or_fail('flash-sale',$f);echo json_encode(['ok'=>true]);exit;
 }
 if($action==='save_flash'){
  $d=payload();$catalog=array_column(array_filter(load_json('products'),fn($p)=>($p['active']??true)!==false),null,'slug');
  $ids=$d['products']??[];if(!is_array($ids)||count($ids)>7)throw new Exception('Pilih maksimal 7 produk yang valid.');
  foreach($ids as $slug)if(!is_string($slug)||!isset($catalog[$slug]))throw new Exception('Pilih produk aktif yang valid.');
  $ids=array_values(array_unique($ids));$active=!empty($d['active']);
  $parseDate=static function($v):string{
   if(!is_string($v))throw new Exception('Jadwal flash sale tidak valid.');if($v==='')return '';
   if(!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2}(?:\.\d{1,6})?)?(?:Z|[+-]\d{2}:\d{2})$/',$v))throw new Exception('Jadwal harus menyertakan zona waktu.');
   try{$dt=new DateTimeImmutable($v);}catch(Throwable $e){throw new Exception('Tanggal flash sale tidak valid.');}
   $errors=DateTimeImmutable::getLastErrors();if($errors&&($errors['warning_count']||$errors['error_count']))throw new Exception('Tanggal flash sale tidak valid.');
   return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
  };
  $start=$parseDate($d['starts_at']??'');$end=$parseDate($d['ends_at']??'');
  if($start!==''&&$end!==''&&strtotime($end)<=strtotime($start))throw new Exception('Waktu selesai harus setelah waktu mulai.');
  if($active&&(!$ids||$end===''||strtotime($end)<=time()))throw new Exception('Untuk mengaktifkan, pilih produk dan isi waktu selesai yang belum lewat.');
  $stock=static function($value):int{if(is_bool($value)||filter_var($value,FILTER_VALIDATE_INT)===false||(int)$value<0||(int)$value>1000000)throw new Exception('Stok harus bilangan bulat antara 0 dan 1.000.000.');return (int)$value;};
  $offers=[];foreach($ids as $slug){
   $offer=$d['offers'][$slug]??null;if(!is_array($offer))throw new Exception('Lengkapi harga dan stok produk terpilih.');
   $pr=$catalog[$slug];$normal=!empty($pr['variants'])?min(array_column($pr['variants'],'price')):$pr['price'];
   $price=number_price($offer['price']??'');if($price<=0||$price>(int)$normal)throw new Exception('Harga flash sale harus lebih dari nol dan tidak melebihi harga normal '.$pr['title'].'.');
   $total=$stock($offer['total_stock']??'');$remaining=$stock($offer['remaining_stock']??'');
   if($total<1||$remaining>$total)throw new Exception('Stok awal minimal 1 dan sisa stok tidak boleh melebihinya.');
   $offers[$slug]=['price'=>$price,'total_stock'=>$total,'remaining_stock'=>$remaining];
  }
  $f=['active'=>$active,'heading'=>clip((string)($d['heading']??''),120)?:'Flash Sale','subheading'=>clip((string)($d['subheading']??''),500),'products'=>$ids,'starts_at'=>$start,'ends_at'=>$end,'offers'=>$offers];
  write_or_fail('flash-sale',$f);echo json_encode(['ok'=>true,'flash'=>$f],JSON_UNESCAPED_UNICODE);exit;
 }
 if($action==='change_password'){
  $d=payload();$c=admin_config();if(!password_verify((string)($d['current']??''),$c['password_hash']))throw new Exception('Password saat ini salah.');$pass=(string)($d['password']??'');if(strlen($pass)<10||strlen($pass)>200)throw new Exception('Password baru minimal 10 karakter.');$c['password_hash']=password_hash($pass,PASSWORD_DEFAULT);$content='<?php if (!defined("PLASMART_ADMIN_CONTEXT")) { http_response_code(403); exit; } return '.var_export($c,true).';';$tmp=__DIR__.'/.config-'.bin2hex(random_bytes(8)).'.php';
  if(@file_put_contents($tmp,$content,LOCK_EX)===false){@unlink($tmp);throw new Exception('Gagal menyimpan akun. Periksa izin folder admin.');}
  if(function_exists('chmod')) @chmod($tmp,0644);
  if(!@rename($tmp,__DIR__.'/config.php')){@unlink($tmp);throw new Exception('Gagal menyimpan akun. Periksa izin folder admin.');}
  if(function_exists('opcache_invalidate')) @opcache_invalidate(__DIR__.'/config.php',true);
  echo json_encode(['ok'=>true]);exit;
 }
 throw new Exception('Aksi tidak tersedia.');
}catch(Throwable $e){http_response_code(400);echo json_encode(['ok'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG);}
finally{if(is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}}

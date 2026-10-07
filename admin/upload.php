<?php
require __DIR__.'/common.php';require_login();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
require_csrf();
$file=$_FILES['file']??null;$alt=trim((string)($_POST['alt']??''));
if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']<1||$file['size']>15*1024*1024||mb_strlen($alt)<3||mb_strlen($alt)>250)redirect('/admin/media.php?error=1');
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
$extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
if(!isset($extensions[$mime]))redirect('/admin/media.php?error=1');
if(str_starts_with($mime,'image/')){$size=@getimagesize($file['tmp_name']);if(!$size||$size[0]>10000||$size[1]>10000)redirect('/admin/media.php?error=1');}
$dir=__DIR__.'/../uploads';if(!is_dir($dir))mkdir($dir,0755,true);
$name=bin2hex(random_bytes(16)).'.'.$extensions[$mime];$path=$dir.'/'.$name;
if(!move_uploaded_file($file['tmp_name'],$path))redirect('/admin/media.php?error=1');
chmod($path,0644);
$q=db()->prepare('INSERT INTO media (path,alt,mime) VALUES (?,?,?)');$q->execute(['uploads/'.$name,$alt,$mime]);
redirect('/admin/media.php?uploaded=1');

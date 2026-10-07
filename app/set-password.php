<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$password=rtrim(stream_get_contents(STDIN),"\r\n");
if (strlen($password)!==8) { fwrite(STDERR,"Password must be exactly 8 characters.\n"); exit(1); }
$path=data_dir().'/admin-password.hash';
if (file_put_contents($path,password_hash($password,PASSWORD_DEFAULT),LOCK_EX)===false) exit(1);
chmod($path,0600);
fwrite(STDOUT,"Admin password installed.\n");

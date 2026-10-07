<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
function admin_headers(): void {
    security_headers(); header('X-Robots-Tag: noindex, nofollow'); header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");
}
function admin_header(string $title): void {
    admin_headers(); ?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=h($title)?> · IGSL Verwaltung</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/css/admin.css"></head><body class="admin-body"><header class="admin-top"><a class="admin-logo" href="/admin/">IGSL <span>Verwaltung</span></a><?php if(logged_in()):?><nav><a href="/admin/">Übersicht</a><a href="/admin/media.php">Medien</a><a href="/index.html" target="_blank" rel="noopener">Website ↗</a><form action="/admin/logout.php" method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><button type="submit">Abmelden</button></form></nav><?php endif;?></header><main class="admin-wrap"><?php
}
function admin_footer(): void { ?></main><script src="/assets/js/admin.js" defer></script></body></html><?php }
function media_options(): array {
    $files=[];
    foreach (glob(__DIR__.'/../assets/img/*/*') ?: [] as $path) if(is_file($path)) $files[]='assets/img/'.basename(dirname($path)).'/'.basename($path);
    foreach (glob(__DIR__.'/../uploads/*') ?: [] as $path) if(is_file($path) && preg_match('/\.(jpg|jpeg|png|webp)$/i',$path)) $files[]='uploads/'.basename($path);
    sort($files); return $files;
}

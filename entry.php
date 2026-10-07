<?php
require __DIR__.'/app/view.php';
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT);
if(!$id){http_response_code(404);exit('Beitrag nicht gefunden.');}
$q=db()->prepare('SELECT * FROM entries WHERE id=? AND published=1');$q->execute([$id]);$item=$q->fetch();
if(!$item){http_response_code(404);exit('Beitrag nicht gefunden.');}
site_header($item['title'],$item['type']==='book'?'buecher.html':'aktuelles.html');
?><main id="content"><section class="igs-page-hero"><div class="igs-wrap"><a class="igs-back-link" href="/<?= $item['type']==='book'?'buecher':'aktuelles' ?>.html">← Zur Übersicht</a><span class="igs-eyebrow"><?=h($item['kind'])?></span><h1><?=h($item['title'])?></h1><p><?=h($item['summary'])?></p></div></section><article class="igs-detail igs-wrap"><?php if(safe_path($item['image'])):?><img src="/<?=h($item['image'])?>" alt="<?=h($item['title'])?>"><?php endif;?><div class="igs-detail-content"><?= $item['body'] ?: '<p>'.h($item['summary']).'</p>' ?></div></article></main><?php site_footer(); ?>

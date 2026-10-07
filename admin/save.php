<?php
require __DIR__.'/common.php'; require_login();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}
require_csrf(); $type=(string)($_POST['type']??'');
function field(string $key,int $max=1000): string { $v=trim((string)($_POST[$key]??'')); if(mb_strlen($v)>$max) {http_response_code(422);exit('Field too long.');} return $v; }
if($type==='settings'){
    $keys=['site_name'=>160,'tagline'=>300,'home_intro'=>700,'home_hero'=>250,'contact_address'=>250,'contact_email'=>250,'contact_phone'=>100,'intro_heading'=>700,'intro_body'=>700,'features_heading'=>700,'features_intro'=>700,'feature_exhibitions'=>700,'feature_workshops'=>700,'feature_research'=>700,'feature_exhibitions_image'=>250,'feature_workshops_image'=>250,'feature_research_image'=>250,'books_heading'=>700,'articles_heading'=>700,'cta_heading'=>700];
    $q=db()->prepare('INSERT INTO settings (key,value) VALUES (?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value');
    foreach($keys as $key=>$max){$value=field($key,$max);if(($key==='home_hero'||str_ends_with($key,'_image'))&&safe_path($value)==='')$value=setting($key);if($key==='contact_email'&&$value!==''&&!filter_var($value,FILTER_VALIDATE_EMAIL)){http_response_code(422);exit('Invalid email.');}$q->execute([$key,$value]);}
    redirect('/admin/edit.php?type=settings&saved=1');
}
if($type==='page'){
    $slug=field('slug',80);if(!in_array($slug,IGSL_PAGE_SLUGS,true)){http_response_code(404);exit;}
    $title=field('title',200);if($title===''){http_response_code(422);exit('Title required.');}
    $intro=field('intro',700);$body=(string)($_POST['body']??'');if(strlen($body)>300000){http_response_code(422);exit('Content too long.');}
    $hero=safe_path(field('hero_image',250))?:'assets/img/hero/hero-02.jpg';
    $q=db()->prepare('UPDATE pages SET title=?,intro=?,body=?,hero_image=? WHERE slug=?');$q->execute([$title,$intro,sanitize_html($body),$hero,$slug]);
    redirect('/admin/edit.php?type=page&slug='.rawurlencode($slug).'&saved=1');
}
if($type==='book'||$type==='article'){
    $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);$title=field('title',500);if($title===''){http_response_code(422);exit('Title required.');}
    $kind=field('kind',100);$summary=field('summary',1200);$image=field('image',250);if($image!==''&&safe_path($image)==='')$image='';
    $body=(string)($_POST['body']??'');if(strlen($body)>300000){http_response_code(422);exit('Content too long.');}$body=sanitize_html($body);
    $link=safe_link(field('link',500));$position=max(0,min(9999,(int)($_POST['position']??0)));$published=isset($_POST['published'])?1:0;
    if($id){$q=db()->prepare('UPDATE entries SET title=?,kind=?,summary=?,body=?,image=?,link=?,position=?,published=? WHERE id=? AND type=?');$q->execute([$title,$kind,$summary,$body,$image,$link,$position,$published,$id,$type]);if(!$q->rowCount()){http_response_code(404);exit;}}
    else{$q=db()->prepare('INSERT INTO entries (type,title,kind,summary,body,image,link,position,published) VALUES (?,?,?,?,?,?,?,?,?)');$q->execute([$type,$title,$kind,$summary,$body,$image,$link,$position,$published]);$id=(int)db()->lastInsertId();}
    redirect('/admin/edit.php?type='.$type.'&id='.$id.'&saved=1');
}
http_response_code(400);exit('Unknown content type.');

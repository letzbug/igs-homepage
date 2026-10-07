<?php
declare(strict_types=1);

const IGSL_PAGE_SLUGS = ['aktuelles','ausstellungen','buecher','datenschutz','exkursionen','forschung','impressum','kontakt','presse','ueber-uns','vortraege','workshops'];

function data_dir(): string {
    $dir = getenv('IGSL_DATA_DIR') ?: dirname(__DIR__, 3) . '/igsl-data';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Content storage is unavailable.');
    }
    return $dir;
}

function db(): PDO {
    static $db;
    if ($db instanceof PDO) return $db;
    $db = new PDO('sqlite:' . data_dir() . '/content.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT NOT NULL)');
    $db->exec('CREATE TABLE IF NOT EXISTS pages (slug TEXT PRIMARY KEY, title TEXT NOT NULL, intro TEXT NOT NULL, body TEXT NOT NULL, hero_image TEXT NOT NULL)');
    $db->exec('CREATE TABLE IF NOT EXISTS entries (id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT NOT NULL, title TEXT NOT NULL, kind TEXT NOT NULL DEFAULT "", summary TEXT NOT NULL DEFAULT "", body TEXT NOT NULL DEFAULT "", image TEXT NOT NULL DEFAULT "", link TEXT NOT NULL DEFAULT "", position INTEGER NOT NULL DEFAULT 0, published INTEGER NOT NULL DEFAULT 1)');
    $columns=$db->query('PRAGMA table_info(entries)')->fetchAll();
    if(!in_array('body',array_column($columns,'name'),true)) $db->exec('ALTER TABLE entries ADD COLUMN body TEXT NOT NULL DEFAULT ""');
    $db->exec('CREATE TABLE IF NOT EXISTS media (id INTEGER PRIMARY KEY AUTOINCREMENT, path TEXT NOT NULL UNIQUE, alt TEXT NOT NULL, mime TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $db->exec('CREATE TABLE IF NOT EXISTS auth_attempts (ip TEXT NOT NULL, at INTEGER NOT NULL)');
    if ((int)$db->query('SELECT COUNT(*) FROM settings')->fetchColumn() === 0) {
        $seed = json_decode(file_get_contents(__DIR__ . '/../content/seed.json'), true, 512, JSON_THROW_ON_ERROR);
        $db->beginTransaction();
        $s = $db->prepare('INSERT INTO settings (key,value) VALUES (?,?)');
        foreach ($seed['settings'] as $key => $value) $s->execute([$key,$value]);
        $p = $db->prepare('INSERT INTO pages (slug,title,intro,body,hero_image) VALUES (?,?,?,?,?)');
        foreach ($seed['pages'] as $slug => $page) $p->execute([$slug,$page['title'],$page['intro'],$page['body'],$page['hero_image']]);
        $e = $db->prepare('INSERT INTO entries (type,title,kind,summary,image,link,position) VALUES (?,?,?,?,?,?,?)');
        foreach ($seed['books'] as $i => $book) $e->execute(['book',$book[0],'Publikation','',$book[1],'kontakt.html',$i]);
        foreach ($seed['articles'] as $i => $item) $e->execute(['article',$item['title'],$item['kind'],$item['summary'],$item['image'],$item['link'],$i]);
        $db->commit();
    }
    $extra=['intro_heading'=>'Vergangene Lebenswege sichtbar machen. Gegenwärtige Fragen öffnen.','intro_body'=>'Das Institut verbindet historische Forschung mit Ausstellungen, Vorträgen und Bildungsangeboten. Im Mittelpunkt stehen Menschen und Geschichten, die zu selten gehört werden.','features_heading'=>'Wissen wird Begegnung.','features_intro'=>'Entdecken Sie Forschung und Vermittlung in verschiedenen Formaten.','feature_exhibitions'=>'Geschichten, die Raum einnehmen.','feature_workshops'=>'Geschichte gemeinsam befragen.','feature_research'=>'Spuren sorgfältig verfolgen.','feature_exhibitions_image'=>'assets/img/sections/ausstellungen.jpg','feature_workshops_image'=>'assets/img/sections/workshops.jpg','feature_research_image'=>'assets/img/sections/forschung.jpg','books_heading'=>'Bücher, die Zeugnis geben.','articles_heading'=>'Aus dem Institut.','cta_heading'=>'Sie planen eine Ausstellung, einen Vortrag oder ein Bildungsprojekt?'];
    $insert=$db->prepare('INSERT OR IGNORE INTO settings (key,value) VALUES (?,?)'); foreach($extra as $key=>$value)$insert->execute([$key,$value]);
    return $db;
}

function h(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function setting(string $key): string {
    $s=db()->prepare('SELECT value FROM settings WHERE key=?'); $s->execute([$key]); return (string)($s->fetchColumn() ?: '');
}
function page(string $slug): ?array {
    $s=db()->prepare('SELECT * FROM pages WHERE slug=?'); $s->execute([$slug]); return $s->fetch() ?: null;
}
function entries(string $type, int $limit=100): array {
    $s=db()->prepare('SELECT * FROM entries WHERE type=? AND published=1 ORDER BY position,id LIMIT ?');
    $s->bindValue(1,$type); $s->bindValue(2,$limit,PDO::PARAM_INT); $s->execute(); return $s->fetchAll();
}
function safe_path(string $path): string {
    return preg_match('~^(assets/[a-zA-Z0-9_./-]+|uploads/[a-zA-Z0-9_.-]+)$~', $path) && !str_contains($path, '..') ? $path : '';
}
function safe_link(string $link): string {
    if ($link==='') return '';
    if (preg_match('~^(https://[^\s"<>]+|mailto:[^\s"<>]+|[a-z0-9-]+\.html|#[a-zA-Z0-9_-]+)$~i',$link)) return $link;
    return '';
}
function sanitize_html(string $html): string {
    $dom=new DOMDocument('1.0','UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root=$dom->getElementById('root');
    if (!$root) return '';
    $allowed=['section','article','div','p','br','strong','em','b','i','h2','h3','h4','ul','ol','li','blockquote','a','img','span','details','summary','figure','figcaption'];
    $walk=function(DOMNode $node) use (&$walk,$allowed,$dom): void {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (!$child instanceof DOMElement) { if ($child->nodeType !== XML_TEXT_NODE) $node->removeChild($child); continue; }
            $tag=strtolower($child->tagName);
            if (!in_array($tag,$allowed,true)) { $node->removeChild($child); continue; }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name=strtolower($attr->name);
                $okay=($name==='class' && preg_match('/^[a-zA-Z0-9 _-]{0,160}$/',$attr->value))
                    || ($name==='id' && preg_match('/^[a-zA-Z0-9_-]{1,80}$/',$attr->value))
                    || ($name==='alt' && $tag==='img')
                    || ($name==='src' && $tag==='img' && safe_path($attr->value)!=='')
                    || ($name==='href' && $tag==='a' && safe_link($attr->value)===$attr->value);
                if (!$okay) $child->removeAttributeNode($attr);
            }
            $walk($child);
        }
    };
    $walk($root);
    $out=''; foreach ($root->childNodes as $child) $out.=$dom->saveHTML($child); return $out;
}
function start_session(): void {
    if (session_status()===PHP_SESSION_ACTIVE) return;
    session_name('igsl_editor');
    session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Strict','path'=>'/admin']);
    session_start();
}
function logged_in(): bool { start_session(); return !empty($_SESSION['editor']); }
function require_login(): void { if (!logged_in()) { header('Location: /admin/login.php'); exit; } }
function csrf(): string { start_session(); return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function require_csrf(): void { if (!hash_equals(csrf(),(string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Invalid request token.'); } }
function redirect(string $url): never { header('Location: '.$url, true, 303); exit; }
function security_headers(): void {
    header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
}

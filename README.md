# Institut für Geschichte und Soziales website

The public site runs on PHP 8 with SQLite. It keeps the original HTML pages in the repository as source reference; `.htaccess` routes their `.html` URLs through the new templates. Page copy starts from `content/seed.json` on the first request and is then managed in SQLite.

## Editing

Open `https://igsl.luxxow.com/admin/`. The console edits the homepage, all interior pages, books, articles, and uploaded media. A new book or article with no external link gets its own detail page. Unchecking **Auf der Website sichtbar** archives an item without deleting it.

The password hash and content database are stored in `igsl-data` outside the public document root. Never add either to Git. Set or rotate the editor password through the protected secret intake, piping it to `php app/set-password.php` on the server. The script accepts 6–8 characters and writes only a hash. The login rate limit remains five attempts per IP per 15 minutes.

The original source contains placeholder contact details and unfinished `Impressum` and `Datenschutz` pages. The institute should replace those through the console with verified details and approved legal text.

## Local development

Use PHP 8.2+ with `pdo_sqlite`, `fileinfo`, and `dom`. Set `IGSL_DATA_DIR` to a private writable directory outside the document root if needed. The built-in PHP server does not process `.htaccess`, so use an Apache/LiteSpeed server or a local router that maps the existing `.html` URLs to `page.php`.

## Deployment

The current Hostinger document root is `/home/u715391695/domains/luxxow.com/public_html/igsl`. Back up this directory and the private database directory at `/home/u715391695/domains/luxxow.com/igsl-data` before updates. Deploy PHP, CSS, JS, images, and `content/seed.json`; keep uploaded media and the private database intact. The site does not require a build step.

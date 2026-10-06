# PHP Sitemap Generator

Dependency-free XML sitemap builder in plain PHP. Collects URLs and writes gzipped `sitemap-1.xml.gz … sitemap-N.xml.gz` files (split at the 50,000-URL sitemap protocol limit) plus a `sitemap_index.xml` — ready to submit to Google Search Console.

## Why

Most sitemap plugins are slow, memory-hungry, or tied to a CMS. This is ~150 lines of framework-free PHP you can drop into any project, cron job, or static-site build.

## Usage

```php
require 'src/SitemapGenerator.php';

$sm = new SitemapGenerator();
$sm->addUrl('https://example.com/', '2026-10-06', 'daily', 1.0);
$sm->addUrl('https://example.com/blog', '2026-10-01', 'weekly', 0.8);

$index = $sm->write('/var/www/html/sitemaps', 'https://example.com/sitemaps');
echo "Sitemap index: $index\n";
```

Or from the CLI — feed it a URL list (one per line, optional tab-separated `lastmod`, `changefreq`, `priority`):

```bash
php build-sitemap.php urls.txt https://example.com/sitemaps /var/www/html/sitemaps
```

`urls.txt` example:

```
https://example.com/	2026-10-06	daily	1.0
https://example.com/blog	2026-10-01	weekly	0.8
https://example.com/contact
```

Add this line to your `robots.txt` so crawlers find it:

```
Sitemap: https://example.com/sitemaps/sitemap_index.xml
```

Requires PHP 7.4+ with zlib (bundled by default). MIT licensed.

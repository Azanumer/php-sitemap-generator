#!/usr/bin/env php
<?php
/**
 * build-sitemap.php — CLI wrapper for SitemapGenerator.
 *
 * Usage:
 *   php build-sitemap.php urls.txt https://example.com/sitemaps /var/www/html/sitemaps
 *
 * urls.txt: one URL per line. Optionally tab-separated metadata:
 *   https://example.com/	2026-10-06	daily	1.0
 *   https://example.com/blog
 */
require __DIR__ . '/src/SitemapGenerator.php';

if ($argc < 4) {
	fwrite(STDERR, "Usage: php build-sitemap.php <urls.txt> <base-url> <out-dir>\n");
	exit(1);
}

list($script, $listFile, $baseUrl, $outDir) = $argv;

if (!is_readable($listFile)) {
	fwrite(STDERR, "Cannot read URL list: $listFile\n");
	exit(1);
}

$sm    = new SitemapGenerator();
$lines = file($listFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
	$line = trim($line);
	if ($line === '' || $line[0] === '#') {
		continue;
	}
	$parts = explode("\t", $line);
	$sm->addUrl(
		$parts[0],
		isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null,
		isset($parts[2]) && $parts[2] !== '' ? $parts[2] : null,
		isset($parts[3]) && $parts[3] !== '' ? (float) $parts[3] : null
	);
}

try {
	$index = $sm->write($outDir, $baseUrl);
	echo 'Wrote ' . $sm->count() . " URLs → $index\n";
} catch (RuntimeException $e) {
	fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
	exit(1);
}

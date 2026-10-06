<?php
/**
 * SitemapGenerator — dependency-free XML sitemap builder.
 *
 * Collects URLs, then writes gzipped sitemap files (split at the 50,000-URL
 * sitemap protocol limit) plus a sitemap index file.
 *
 *     $sm = new SitemapGenerator();
 *     $sm->addUrl('https://example.com/', '2026-10-06', 'daily', '1.0');
 *     $sm->addUrl('https://example.com/blog');
 *     $index = $sm->write('/var/www/html/sitemaps', 'https://example.com/sitemaps');
 *
 * Requires PHP 7.4+. No extensions beyond zlib needed.
 */
class SitemapGenerator {

	/** Max URLs per sitemap file (sitemap.org protocol limit). */
	const MAX_URLS = 50000;

	/** @var array */
	private $urls = array();

	/**
	 * @param string      $loc         Absolute URL.
	 * @param string|null $lastmod     Date in YYYY-MM-DD (or full W3C datetime).
	 * @param string|null $changefreq  always|hourly|daily|weekly|monthly|yearly|never
	 * @param float|null  $priority    0.0–1.0
	 */
	public function addUrl($loc, $lastmod = null, $changefreq = null, $priority = null) {
		$entry = array('loc' => $loc);
		if ($lastmod !== null) {
			$entry['lastmod'] = $lastmod;
		}
		if ($changefreq !== null) {
			$entry['changefreq'] = $changefreq;
		}
		if ($priority !== null) {
			$entry['priority'] = number_format(max(0.0, min(1.0, (float) $priority)), 1, '.', '');
		}
		$this->urls[] = $entry;
	}

	public function count() {
		return count($this->urls);
	}

	/**
	 * Write sitemap-1.xml.gz … sitemap-N.xml.gz and sitemap_index.xml.
	 *
	 * @param string $dir     Writable output directory (created if missing).
	 * @param string $baseUrl Public URL prefix of $dir, e.g. https://example.com/sitemaps
	 * @return string Absolute path of the written sitemap_index.xml
	 */
	public function write($dir, $baseUrl) {
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		$chunks = array_chunk($this->urls, self::MAX_URLS);
		if (empty($chunks)) {
			$chunks = array(array());
		}
		$indexUrls = array();
		foreach ($chunks as $i => $chunk) {
			$name  = 'sitemap-' . ($i + 1) . '.xml.gz';
			$xml   = $this->buildUrlset($chunk);
			$bytes = file_put_contents($dir . '/' . $name, gzencode($xml, 9));
			if ($bytes === false) {
				throw new RuntimeException('Could not write ' . $dir . '/' . $name);
			}
			$indexUrls[] = rtrim($baseUrl, '/') . '/' . $name;
		}
		$indexFile = $dir . '/sitemap_index.xml';
		if (file_put_contents($indexFile, $this->buildIndex($indexUrls)) === false) {
			throw new RuntimeException('Could not write ' . $indexFile);
		}
		return $indexFile;
	}

	private function buildUrlset(array $urls) {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ($urls as $u) {
			$xml .= "  <url>\n";
			$xml .= '    <loc>' . $this->esc($u['loc']) . "</loc>\n";
			if (isset($u['lastmod'])) {
				$xml .= '    <lastmod>' . $this->esc($u['lastmod']) . "</lastmod>\n";
			}
			if (isset($u['changefreq'])) {
				$xml .= '    <changefreq>' . $this->esc($u['changefreq']) . "</changefreq>\n";
			}
			if (isset($u['priority'])) {
				$xml .= '    <priority>' . $this->esc($u['priority']) . "</priority>\n";
			}
			$xml .= "  </url>\n";
		}
		return $xml . "</urlset>\n";
	}

	private function buildIndex(array $sitemapUrls) {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
			. '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ($sitemapUrls as $s) {
			$xml .= "  <sitemap>\n"
				. '    <loc>' . $this->esc($s) . "</loc>\n"
				. '    <lastmod>' . date('Y-m-d') . "</lastmod>\n"
				. "  </sitemap>\n";
		}
		return $xml . "</sitemapindex>\n";
	}

	private function esc($s) {
		return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
	}
}

<?php

namespace WebsiteCloner\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use WebsiteCloner\Utils\Logger;
use WebsiteCloner\Utils\Url;

class Crawler
{
    private $client;
    private $baseUrl;
    private $visitedUrls = [];
    private $pendingUrls = [];
    private $config;
    private $logger;
    private $downloader;
    private $rewriter;
    
    public function __construct(string $baseUrl, array $config, Downloader $downloader, Rewriter $rewriter)
    {
        $this->baseUrl = $baseUrl;
        $this->config = $config;
        $this->client = new Client([
            'timeout' => $config['request_timeout'] ?? 30,
            'verify' => $config['verify_ssl'] ?? true,
            'headers' => [
                'User-Agent' => $config['user_agent'] ?? 'Website-Cloner/1.0',
            ],
        ]);
        $this->logger = new Logger();
        $this->downloader = $downloader;
        $this->rewriter = $rewriter;
        
        // Add the base URL to pending URLs
        $this->pendingUrls[] = $baseUrl;
    }
    
    public function crawl(int $maxPages = 0): void
    {
        $pageCount = 0;
        
        while (!empty($this->pendingUrls) && ($maxPages === 0 || $pageCount < $maxPages)) {
            $url = array_shift($this->pendingUrls);
            
            if (in_array($url, $this->visitedUrls)) {
                continue;
            }
            
            $this->visitedUrls[] = $url;
            $pageCount++;
            
            $this->logger->info("Crawling: {$url}");
            
            try {
                $response = $this->client->get($url);
                $contentType = $response->getHeaderLine('Content-Type');
                
                // Only process HTML content
                if (strpos($contentType, 'text/html') !== false) {
                    $html = (string) $response->getBody();
                    $this->processHtml($url, $html);
                } else {
                    $this->downloader->downloadAsset($url);
                }
            } catch (RequestException $e) {
                $this->logger->error("Error crawling {$url}: " . $e->getMessage());
            }
        }
        
        $this->logger->info("Crawling completed. Processed {$pageCount} pages.");
    }
    
    private function processHtml(string $url, string $html): void
    {
        // Save the HTML content
        $this->downloader->savePage($url, $html);
        
        // Parse the HTML to find links and assets
        $crawler = new DomCrawler($html, $url);
        
        // Extract links
        $this->extractLinks($crawler, $url);
        
        // Extract and download assets
        $this->extractAssets($crawler, $url);
        
        // Rewrite URLs in the HTML
        $rewrittenHtml = $this->rewriter->rewriteHtml($html, $url);
        
        // Save the rewritten HTML
        $this->downloader->savePage($url, $rewrittenHtml, true);
    }
    
    private function extractLinks(DomCrawler $crawler, string $baseUrl): void
    {
        $links = $crawler->filter('a')->links();
        
        foreach ($links as $link) {
            $href = $link->getUri();
            
            // Skip if the URL is not part of the same domain
            if (!Url::isSameDomain($href, $this->baseUrl)) {
                continue;
            }
            
            // Skip if the URL should be excluded based on config
            if ($this->shouldExcludeUrl($href)) {
                continue;
            }
            
            // Add to pending URLs if not already visited
            if (!in_array($href, $this->visitedUrls) && !in_array($href, $this->pendingUrls)) {
                $this->pendingUrls[] = $href;
            }
        }
    }
    
    private function extractAssets(DomCrawler $crawler, string $baseUrl): void
    {
        // Extract and download CSS files
        $crawler->filter('link[rel="stylesheet"]')->each(function ($node) {
            $href = $node->attr('href');
            if ($href) {
                $absoluteUrl = Url::makeAbsolute($href, $this->baseUrl);
                $this->downloader->downloadAsset($absoluteUrl);
            }
        });
        
        // Extract and download JavaScript files
        $crawler->filter('script[src]')->each(function ($node) {
            $src = $node->attr('src');
            if ($src) {
                $absoluteUrl = Url::makeAbsolute($src, $this->baseUrl);
                $this->downloader->downloadAsset($absoluteUrl);
            }
        });
        
        // Extract and download images
        $crawler->filter('img[src]')->each(function ($node) {
            $src = $node->attr('src');
            if ($src) {
                $absoluteUrl = Url::makeAbsolute($src, $this->baseUrl);
                $this->downloader->downloadAsset($absoluteUrl);
            }
        });
    }
    
    private function shouldExcludeUrl(string $url): bool
    {
        $excludePatterns = $this->config['exclude_patterns'] ?? [];
        
        foreach ($excludePatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }
}
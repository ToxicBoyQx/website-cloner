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
    
    // Statistics tracking
    private $totalDownloadSize = 0;
    private $totalDownloadedFiles = 0;
    private $processStatus = 'Starting';
    
    public function __construct(string $baseUrl, array $config, Downloader $downloader, Rewriter $rewriter)
    {
        $this->baseUrl = $baseUrl;
        $this->config = $config;
        $this->client = new Client([
            'timeout' => $config['request_timeout'] ?? 300,
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
    
    // Getter methods for statistics
    public function getTotalDownloadSize(): int
    {
        return $this->totalDownloadSize;
    }
    
    public function getTotalDownloadedFiles(): int
    {
        return $this->totalDownloadedFiles;
    }
    
    public function getProcessStatus(): string
    {
        return $this->processStatus;
    }
    
    public function getTotalPendingUrls(): int
    {
        return count($this->pendingUrls);
    }
    
    public function getTotalVisitedUrls(): int
    {
        return count($this->visitedUrls);
    }
    
    /**
     * Get the logger instance
     *
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
    
    /**
     * Set the logger instance
     *
     * @param mixed $logger
     * @return void
     */
    public function setLogger($logger): void
    {
        $this->logger = $logger;
    }
    
    public function crawl(int $maxPages = 0): void
    {
        $pageCount = 0;
        $errorCount = 0;
        
        while (!empty($this->pendingUrls) && ($maxPages === 0 || $pageCount < $maxPages)) {
            $url = array_shift($this->pendingUrls);
            
            if (in_array($url, $this->visitedUrls)) {
                continue;
            }
            
            $this->visitedUrls[] = $url;
            $pageCount++;
            
            $this->processStatus = "Crawling: {$url}";
            $this->logger->info($this->processStatus);
            
            try {
                // Add timeout options per URL
                $options = [];
                if (preg_match('/\.(pdf|doc|docx|xls|xlsx|ppt|pptx)$/i', $url)) {
                    // For large document files, use a larger timeout
                    $options = ['timeout' => ($this->config['request_timeout'] ?? 300) * 2];
                }
                
                $response = $this->client->get($url, $options);
                $contentType = $response->getHeaderLine('Content-Type');
                
                // Only process HTML content
                if (strpos($contentType, 'text/html') !== false) {
                    $html = (string) $response->getBody();
                    $this->processHtml($url, $html);
                    
                    // Track download stats
                    $this->totalDownloadSize += strlen($html);
                    $this->totalDownloadedFiles++;
                } else {
                    $fileSize = $this->downloader->downloadAsset($url);
                    if ($fileSize > 0) {
                        $this->totalDownloadSize += $fileSize;
                        $this->totalDownloadedFiles++;
                    }
                }
            } catch (RequestException $e) {
                $errorCount++;
                $this->logger->error("Error crawling {$url}: " . $e->getMessage());
                $this->processStatus = "Error: {$url} - " . $e->getMessage();
                $this->logger->info("Skipping URL and continuing with next...");
                
                // Optional: you can add URLs to a failed list if you want to retry them later
                // $this->failedUrls[] = $url;
            } catch (\Exception $e) {
                $errorCount++;
                $this->logger->error("Unexpected error processing {$url}: " . $e->getMessage());
                $this->processStatus = "Error: {$url} - " . $e->getMessage();
                $this->logger->info("Skipping URL and continuing with next...");
            }
        }
        
        $this->processStatus = "Completed";
        $this->logger->info("Crawling completed. Processed {$pageCount} pages with {$errorCount} errors.");
    }
    
    private function processHtml(string $url, string $html): void
    {
        try {
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
        } catch (\Exception $e) {
            $this->logger->error("Error processing HTML for {$url}: " . $e->getMessage());
            $this->logger->info("Skipping HTML processing and continuing...");
        }
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
            try {
                $href = $node->attr('href');
                if ($href) {
                    $absoluteUrl = Url::makeAbsolute($href, $this->baseUrl);
                    $this->downloader->downloadAsset($absoluteUrl);
                }
            } catch (\Exception $e) {
                $this->logger->error("Error downloading CSS asset: " . $e->getMessage());
                // Continue with the next asset
            }
        });
        
        // Extract and download JavaScript files
        $crawler->filter('script[src]')->each(function ($node) {
            try {
                $src = $node->attr('src');
                if ($src) {
                    $absoluteUrl = Url::makeAbsolute($src, $this->baseUrl);
                    $this->downloader->downloadAsset($absoluteUrl);
                }
            } catch (\Exception $e) {
                $this->logger->error("Error downloading JS asset: " . $e->getMessage());
                // Continue with the next asset
            }
        });
        
        // Extract and download images
        $crawler->filter('img[src]')->each(function ($node) {
            try {
                $src = $node->attr('src');
                if ($src) {
                    $absoluteUrl = Url::makeAbsolute($src, $this->baseUrl);
                    $this->downloader->downloadAsset($absoluteUrl);
                }
            } catch (\Exception $e) {
                $this->logger->error("Error downloading image asset: " . $e->getMessage());
                // Continue with the next asset
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
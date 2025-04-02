<?php

namespace WebsiteCloner\Core;

use Symfony\Component\DomCrawler\Crawler as DomCrawler;
use WebsiteCloner\Utils\Logger;
use WebsiteCloner\Utils\Url;

class Rewriter
{
    private $baseUrl;
    private $logger;
    
    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
        $this->logger = new Logger();
    }
    
    public function rewriteHtml(string $html, string $currentUrl): string
    {
        $crawler = new DomCrawler($html, $currentUrl);
        $currentRelativePath = Url::getRelativePath($currentUrl, $this->baseUrl);
        
        // Rewrite links
        $crawler->filter('a')->each(function ($node) use ($currentRelativePath) {
            $href = $node->attr('href');
            if ($href) {
                $newHref = $this->rewriteUrl($href, $currentRelativePath);
                $node->getNode(0)->setAttribute('href', $newHref);
            }
        });
        
        // Rewrite CSS links
        $crawler->filter('link[rel="stylesheet"]')->each(function ($node) use ($currentRelativePath) {
            $href = $node->attr('href');
            if ($href) {
                $newHref = $this->rewriteUrl($href, $currentRelativePath);
                $node->getNode(0)->setAttribute('href', $newHref);
            }
        });
        
        // Rewrite script sources
        $crawler->filter('script[src]')->each(function ($node) use ($currentRelativePath) {
            $src = $node->attr('src');
            if ($src) {
                $newSrc = $this->rewriteUrl($src, $currentRelativePath);
                $node->getNode(0)->setAttribute('src', $newSrc);
            }
        });
        
        // Rewrite image sources
        $crawler->filter('img[src]')->each(function ($node) use ($currentRelativePath) {
            $src = $node->attr('src');
            if ($src) {
                $newSrc = $this->rewriteUrl($src, $currentRelativePath);
                $node->getNode(0)->setAttribute('src', $newSrc);
            }
        });
        
        // Get the updated HTML
        $html = $crawler->html();
        
        // Add DOCTYPE if missing
        if (strpos($html, '<!DOCTYPE') === false) {
            $html = '<!DOCTYPE html>' . PHP_EOL . $html;
        }
        
        return $html;
    }
    
    private function rewriteUrl(string $url, string $currentRelativePath): string
    {
        // Skip rewriting for anchors, javascript, data URLs, and mailto links
        if (strpos($url, '#') === 0 || 
            strpos($url, 'javascript:') === 0 || 
            strpos($url, 'data:') === 0 || 
            strpos($url, 'mailto:') === 0) {
            return $url;
        }
        
        // Skip rewriting for external URLs
        if (Url::isAbsolute($url) && !Url::isSameDomain($url, $this->baseUrl)) {
            return $url;
        }
        
        // Convert to absolute URL first
        $absoluteUrl = Url::makeAbsolute($url, $this->baseUrl);
        
        // Get the relative path from the base URL
        $relativePath = Url::getRelativePath($absoluteUrl, $this->baseUrl);
        
        // Calculate the relative path from the current page
        $depth = substr_count($currentRelativePath, '/') - (substr($currentRelativePath, -1) === '/' ? 1 : 0);
        $prefix = $depth > 0 ? str_repeat('../', $depth) : '';
        
        // If the URL ends with a slash, append index.html
        if (substr($relativePath, -1) === '/') {
            $relativePath .= 'index.html';
        } elseif (!pathinfo($relativePath, PATHINFO_EXTENSION) && $relativePath !== '') {
            // If there's no file extension, add .html
            $relativePath .= '.html';
        }
        
        return $prefix . $relativePath;
    }
}
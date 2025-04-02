<?php

namespace WebsiteCloner\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use WebsiteCloner\Utils\Logger;
use WebsiteCloner\Utils\Url;
use WebsiteCloner\Utils\File;

class Downloader
{
    private $client;
    private $outputDir;
    private $baseUrl;
    private $logger;
    private $downloadedAssets = [];
    private $config;
    
    public function __construct(string $baseUrl, string $outputDir, array $config)
    {
        $this->baseUrl = $baseUrl;
        $this->outputDir = rtrim($outputDir, '/\\');
        $this->config = $config;
        $this->client = new Client([
            'timeout' => $config['request_timeout'] ?? 30,
            'verify' => $config['verify_ssl'] ?? true,
            'headers' => [
                'User-Agent' => $config['user_agent'] ?? 'Website-Cloner/1.0',
            ],
        ]);
        $this->logger = new Logger();
        
        // Create output directory if it doesn't exist
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    public function savePage(string $url, string $content, bool $isRewritten = false): void
    {
        $relativePath = Url::getRelativePath($url, $this->baseUrl);
        $outputPath = $this->getOutputPath($relativePath);
        
        // Create directory if it doesn't exist
        $directory = dirname($outputPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // If the URL ends with a slash, save as index.html
        if (substr($url, -1) === '/' || $relativePath === '') {
            $outputPath = rtrim($outputPath, '/\\') . '/index.html';
        } elseif (!pathinfo($outputPath, PATHINFO_EXTENSION)) {
            // If there's no file extension, add .html
            $outputPath .= '.html';
        }
        
        // Save the content
        File::write($outputPath, $content);
        
        if ($isRewritten) {
            $this->logger->info("Saved rewritten page: {$outputPath}");
        } else {
            $this->logger->info("Saved page: {$outputPath}");
        }
    }
    
    public function downloadAsset(string $url): bool
    {
        // Skip if already downloaded
        if (in_array($url, $this->downloadedAssets)) {
            return true;
        }
        
        // Skip if not from the same domain and not configured to download external assets
        if (!Url::isSameDomain($url, $this->baseUrl) && !($this->config['download_external_assets'] ?? false)) {
            return false;
        }
        
        $this->logger->info("Downloading asset: {$url}");
        
        try {
            $response = $this->client->get($url);
            $content = (string) $response->getBody();
            
            $relativePath = Url::getRelativePath($url, $this->baseUrl);
            $outputPath = $this->getOutputPath($relativePath);
            
            // Create directory if it doesn't exist
            $directory = dirname($outputPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Save the content
            File::write($outputPath, $content);
            
            $this->downloadedAssets[] = $url;
            $this->logger->info("Saved asset: {$outputPath}");
            
            return true;
        } catch (RequestException $e) {
            $this->logger->error("Error downloading asset {$url}: " . $e->getMessage());
            return false;
        }
    }
    
    private function getOutputPath(string $relativePath): string
    {
        return $this->outputDir . '/' . ltrim($relativePath, '/\\');
    }
}
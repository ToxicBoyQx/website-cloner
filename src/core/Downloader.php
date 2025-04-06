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
            'timeout' => $config['request_timeout'] ?? 300,
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
    
    public function downloadAsset(string $url): int
    {
        // Skip if already downloaded
        if (in_array($url, $this->downloadedAssets)) {
            return 0;
        }
        
        // Skip if not from the same domain and not configured to download external assets
        if (!Url::isSameDomain($url, $this->baseUrl) && !($this->config['download_external_assets'] ?? false)) {
            return 0;
        }
        
        $this->logger->info("Downloading asset: {$url}");
        
        // Check file extension to determine if it's a large file type
        $isLargeFile = preg_match('/\.(pdf|zip|doc|docx|ppt|pptx|xls|xlsx|mp4|mp3)$/i', $url);
        
        // Get file extension to check for specific size limits
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        // Check if there's a specific size limit for this file type
        $maxFileSize = 10 * 1024 * 1024; // Default 10MB
        if (!empty($extension) && isset($this->config['file_size_limits'][$extension])) {
            $maxFileSize = $this->config['file_size_limits'][$extension];
            $this->logger->info("Using specific size limit for .{$extension} files: " . ($maxFileSize / (1024 * 1024)) . "MB");
        } else {
            $maxFileSize = $this->config['max_file_size'] ?? 10 * 1024 * 1024;
        }
        
        try {
            $relativePath = Url::getRelativePath($url, $this->baseUrl);
            $outputPath = $this->getOutputPath($relativePath);
            
            // Create directory if it doesn't exist
            $directory = dirname($outputPath);
            if (!is_dir($directory)) {
                // Try to create directory with proper error handling
                if (!@mkdir($directory, 0755, true)) {
                    $error = error_get_last();
                    $this->logger->error("Failed to create directory: {$directory}. Error: {$error['message']}");
                    // Try to normalize the path further for Windows compatibility
                    $normalizedDir = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $directory);
                    $normalizedDir = rtrim($normalizedDir, DIRECTORY_SEPARATOR);
                    
                    // Second attempt with normalized path
                    if ($normalizedDir !== $directory && !is_dir($normalizedDir) && !@mkdir($normalizedDir, 0755, true)) {
                        $error = error_get_last();
                        $this->logger->error("Second attempt failed: {$normalizedDir}. Error: {$error['message']}");
                        // Continue anyway - the file_put_contents will fail and be caught in the catch block
                    } else if ($normalizedDir !== $directory) {
                        $this->logger->info("Created directory with normalized path: {$normalizedDir}");
                        $directory = $normalizedDir;
                    }
                }
            }
            
            // Set special options for large files
            $options = [];
            if ($isLargeFile) {
                $options = [
                    'timeout' => ($this->config['request_timeout'] ?? 300) * 2,
                    'stream' => true,
                    'verify' => $this->config['verify_ssl'] ?? true,
                    'headers' => [
                        'User-Agent' => $this->config['user_agent'] ?? 'Website-Cloner/1.0',
                    ]
                ];
            }
            
            $fileSize = 0;
            
            if ($isLargeFile) {
                // Stream download for large files
                $response = $this->client->request('GET', $url, $options);
                
                $body = $response->getBody();
                $fileHandle = fopen($outputPath, 'w');
                
                // Download file in chunks
                $downloadedBytes = 0;
                while (!$body->eof()) {
                    $chunk = $body->read(1024 * 1024); // 1MB chunks
                    fwrite($fileHandle, $chunk);
                    $downloadedBytes += strlen($chunk);
                    
                    // Check if downloaded size exceeds max file size
                    if ($maxFileSize > 0 && $downloadedBytes > $maxFileSize) {
                        fclose($fileHandle);
                        // Delete partial file
                        if (file_exists($outputPath)) {
                            unlink($outputPath);
                        }
                        $this->logger->warning("Asset {$url} exceeds max file size ({$maxFileSize} bytes). Download aborted.");
                        return 0;
                    }
                }
                
                fclose($fileHandle);
                $fileSize = $downloadedBytes;
            } else {
                // Regular download for smaller files
                $response = $this->client->get($url);
                $content = (string) $response->getBody();
                $fileSize = strlen($content);
                
                // Check file size before saving
                if ($maxFileSize > 0 && $fileSize > $maxFileSize) {
                    $this->logger->warning("Asset {$url} exceeds max file size ({$maxFileSize} bytes). Download aborted.");
                    return 0;
                }
                
                // Save the content
                File::write($outputPath, $content);
            }
            
            $this->downloadedAssets[] = $url;
            $this->logger->info("Saved asset: {$outputPath} ({$fileSize} bytes)");
            
            return $fileSize;
        } catch (RequestException $e) {
            $this->logger->error("Error downloading asset {$url}: " . $e->getMessage());
            return 0;
        } catch (\Exception $e) {
            $this->logger->error("Unexpected error downloading asset {$url}: " . $e->getMessage());
            return 0;
        }
    }
    
    private function getOutputPath(string $relativePath): string
    {
        // If the relative path is a full URL (which can happen when downloading external assets)
        // we need to convert it to a valid file path
        if (preg_match('/^https?:\/\//i', $relativePath)) {
            // Parse the URL to get its components
            $urlParts = parse_url($relativePath);
            
            // Create a path structure based on the URL components
            $path = '';
            
            // Add the host as a directory
            if (isset($urlParts['host'])) {
                $path .= $urlParts['host'] . DIRECTORY_SEPARATOR;
            }
            
            // Add the path component, removing any leading slash
            if (isset($urlParts['path'])) {
                $path .= ltrim($urlParts['path'], '/');
            }
            
            // If there's a query string, add it as a filename suffix
            if (isset($urlParts['query'])) {
                // Limit query string length to avoid path length issues
                $query = substr($urlParts['query'], 0, 50);
                $path .= '_' . str_replace(['=', '&', '?'], '_', $query);
            }
            
            $relativePath = $path;
        }
        
        // Remove any relative path components (../) to prevent directory traversal issues
        // This is safer than trying to resolve them, which can lead to unexpected paths
        $relativePath = str_replace('../', '', $relativePath);
        
        // Normalize directory separators for the current OS
        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        
        // Ensure the path doesn't contain invalid characters for Windows
        $relativePath = str_replace([':', '*', '?', '"', '<', '>', '|'], '_', $relativePath);
        
        // Limit path length to avoid Windows MAX_PATH issues (260 characters)
        // Reserve some characters for the output directory path
        $maxRelativePathLength = 200;
        if (strlen($relativePath) > $maxRelativePathLength) {
            // Keep the beginning and end parts of the path, truncate the middle
            $start = substr($relativePath, 0, $maxRelativePathLength / 2);
            $end = substr($relativePath, -($maxRelativePathLength / 2));
            $relativePath = $start . '_truncated_' . $end;
            $this->logger->info("Path was truncated due to length: {$relativePath}");
        }
        
        return $this->outputDir . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
    }
}
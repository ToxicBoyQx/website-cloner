<?php

namespace WebsiteCloner\Utils;

class Url
{
    /**
     * Check if a URL is absolute
     *
     * @param string $url The URL to check
     * @return bool True if the URL is absolute, false otherwise
     */
    public static function isAbsolute(string $url): bool
    {
        return preg_match('/^(https?:\/\/|mailto:|tel:|ftp:|#|javascript:|data:)/i', $url) === 1;
    }
    
    /**
     * Make a URL absolute
     *
     * @param string $url The URL to make absolute
     * @param string $baseUrl The base URL to use
     * @return string The absolute URL
     */
    public static function makeAbsolute(string $url, string $baseUrl): string
    {
        // If the URL is already absolute, return it
        if (self::isAbsolute($url)) {
            return $url;
        }
        
        // Parse the base URL
        $parsedBase = parse_url($baseUrl);
        $scheme = $parsedBase['scheme'] ?? 'http';
        $host = $parsedBase['host'] ?? '';
        $path = $parsedBase['path'] ?? '/';
        
        // If the URL starts with //, it's a protocol-relative URL
        if (strpos($url, '//') === 0) {
            return $scheme . ':' . $url;
        }
        
        // If the URL starts with /, it's relative to the root
        if (strpos($url, '/') === 0) {
            return $scheme . '://' . $host . $url;
        }
        
        // It's a relative URL, so we need to resolve it against the base URL path
        $basePath = dirname($path);
        if ($basePath !== '/') {
            $basePath .= '/';
        }
        
        return $scheme . '://' . $host . self::resolvePath($basePath . $url);
    }
    
    /**
     * Get the relative path of a URL from the base URL
     *
     * @param string $url The URL to get the relative path for
     * @param string $baseUrl The base URL
     * @return string The relative path
     */
    public static function getRelativePath(string $url, string $baseUrl): string
    {
        // Parse the URLs
        $parsedUrl = parse_url($url);
        $parsedBase = parse_url($baseUrl);
        
        // If the URL is not from the same domain, return the full URL
        if (($parsedUrl['host'] ?? '') !== ($parsedBase['host'] ?? '')) {
            return $url;
        }
        
        // Get the paths
        $urlPath = $parsedUrl['path'] ?? '/';
        $basePath = $parsedBase['path'] ?? '/';
        
        // If the base path is just /, the relative path is the URL path without the leading /
        if ($basePath === '/') {
            return ltrim($urlPath, '/');
        }
        
        // If the URL path starts with the base path, remove the base path
        if (strpos($urlPath, $basePath) === 0) {
            return substr($urlPath, strlen($basePath));
        }
        
        // Otherwise, return the URL path
        return ltrim($urlPath, '/');
    }
    
    /**
     * Check if a URL is from the same domain as the base URL
     *
     * @param string $url The URL to check
     * @param string $baseUrl The base URL
     * @return bool True if the URL is from the same domain, false otherwise
     */
    public static function isSameDomain(string $url, string $baseUrl): bool
    {
        // If the URL is not absolute, it's from the same domain
        if (!self::isAbsolute($url)) {
            return true;
        }
        
        // Parse the URLs
        $parsedUrl = parse_url($url);
        $parsedBase = parse_url($baseUrl);
        
        // Compare the hosts
        return ($parsedUrl['host'] ?? '') === ($parsedBase['host'] ?? '');
    }
    
    /**
     * Resolve a path with ../ and ./ components
     *
     * @param string $path The path to resolve
     * @return string The resolved path
     */
    private static function resolvePath(string $path): string
    {
        // Replace // with /
        $path = preg_replace('#/+#', '/', $path);
        
        // Split the path into segments
        $segments = explode('/', $path);
        $result = [];
        
        foreach ($segments as $segment) {
            if ($segment === '..') {
                // Go up one level
                array_pop($result);
            } elseif ($segment !== '.' && $segment !== '') {
                // Add the segment to the result
                $result[] = $segment;
            }
        }
        
        // Join the segments back together
        return '/' . implode('/', $result);
    }
}
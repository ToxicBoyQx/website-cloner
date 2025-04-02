<?php

return [
    // General settings
    'user_agent' => 'Website-Cloner/1.0',
    'request_timeout' => 30, // seconds
    'verify_ssl' => true,
    
    // Crawling settings
    'max_pages' => 0, // 0 for unlimited
    'respect_robots_txt' => true,
    'crawl_delay' => 0, // seconds between requests
    
    // Download settings
    'download_external_assets' => false,
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    
    // Patterns to exclude from crawling
    'exclude_patterns' => [
        '/\.git/',
        '/\.svn/',
        '/wp-admin/',
        '/wp-json/',
        '/wp-login\.php/',
        '/xmlrpc\.php/',
        '/feed/',
        '/\?s=/', // WordPress search
        '/\?p=/', // WordPress post ID
    ],
    
    // File types to download
    'allowed_file_types' => [
        // HTML
        'html', 'htm', 'xhtml',
        
        // CSS
        'css',
        
        // JavaScript
        'js',
        
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico',
        
        // Fonts
        'woff', 'woff2', 'ttf', 'eot', 'otf',
        
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt',
    ],
    
    // Logging settings
    'log_to_file' => true,
    'log_file' => 'website-cloner.log',
    'log_level' => 'info', // debug, info, warning, error
];
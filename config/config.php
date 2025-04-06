<?php

return [
    // General settings
    'user_agent' => 'Website-Cloner/1.0',
    'request_timeout' => 180, // seconds
    'verify_ssl' => true,
    
    // Crawling settings
    'max_pages' => 0, // 0 for unlimited
    'respect_robots_txt' => true,
    'crawl_delay' => 0, // seconds between requests
    
    // Download settings
    'download_external_assets' => false,
    'max_file_size' => 150 * 1024 * 1024, // 150MB (increased from 10MB)
    'skip_oversized_files' => true, // Skip files that exceed max_file_size instead of aborting
    'file_size_limits' => [
        // Define specific size limits for different file types (in bytes)
        // Examples:
        'pdf' => 5 * 1024 * 1024,  // 5MB for PDF files
        'zip' => 200 * 1024 * 1024, // 200MB for ZIP files
        'mp4' => 300 * 1024 * 1024, // 300MB for MP4 files
        // Add more file types as needed
    ]
    
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

        // JSON
        'json',

        // XML
        'xml',
        
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
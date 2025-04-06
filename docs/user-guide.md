# Website Cloner User Guide

Website Cloner is a powerful tool for cloning websites for offline viewing. It crawls through web pages, downloads assets, and rewrites URLs to make the site functional without an internet connection.

## Table of Contents

1. [Installation](#installation)
2. [Basic Usage](#basic-usage)
3. [Command-Line Options](#command-line-options)
4. [Configuration](#configuration)
5. [Advanced Usage](#advanced-usage)
6. [Troubleshooting](#troubleshooting)

## Installation

### Requirements

- PHP 7.4 or higher
- Composer

### Installation Steps

1. Clone the repository:
   ```
   git clone https://github.com/ToxicBoyQx/website-cloner.git
   cd website-cloner
   ```

2. Install dependencies:
   ```
   composer install
   ```

## Basic Usage

The most basic way to use Website Cloner is to provide a URL to clone:

```
php index.php clone https://example.com
```

This will clone the website to the `output` directory by default. The cloner will crawl through the website, following links and downloading assets.

## Command-Line Options

Website Cloner provides several command-line options:

| Option | Short | Description | Default |
|--------|-------|-------------|---------|
| `--output` | `-o` | Output directory | `./output` |
| `--max-pages` | `-m` | Maximum number of pages to clone (0 for unlimited) | `0` |
| `--download-external` | `-e` | Download external assets | `false` |
| `--verify-ssl` | | Verify SSL certificates | `false` |
| `--timeout` | `-t` | Request timeout in seconds | `30` |
| `--user-agent` | `-u` | User agent string | `Website-Cloner/1.0` |

### Examples

Clone a website with a 5-minute timeout:
```
php index.php clone -t 300 https://example.com
```

Clone a website including external assets:
```
php index.php clone -e https://example.com
```

Clone only the first 10 pages:
```
php index.php clone -m 10 https://example.com
```

Clone to a specific directory:
```
php index.php clone -o my-cloned-site https://example.com
```

## Configuration

Website Cloner can be configured through the `config/config.php` file. The following options are available:

### General Settings

- `user_agent`: The user agent to use when making requests
- `request_timeout`: Timeout for HTTP requests in seconds
- `verify_ssl`: Whether to verify SSL certificates

### Crawling Settings

- `max_pages`: Maximum number of pages to crawl (0 for unlimited)
- `respect_robots_txt`: Whether to respect robots.txt
- `crawl_delay`: Delay between requests in seconds

### Download Settings

- `download_external_assets`: Whether to download assets from external domains
- `max_file_size`: Maximum file size to download (in bytes)
- `skip_oversized_files`: Whether to skip files that exceed the size limit instead of aborting (default: true)
- `file_size_limits`: Define specific size limits for different file types

### Exclude Patterns

You can specify regex patterns to exclude URLs from crawling:

```php
'exclude_patterns' => [
    '/\.git/',
    '/wp-admin/',
    '/wp-login\.php/',
    // Add your patterns here
],
```

### Allowed File Types

You can specify which file types to download:

```php
'allowed_file_types' => [
    // HTML
    'html', 'htm', 'xhtml',
    // CSS
    'css',
    // Add your file types here
],
```

## Advanced Usage

### Handling Large Files

When downloading large files like PDFs or videos, you might want to increase the timeout and max file size:

```
php index.php clone -t 600 https://example.com
```

You can also edit `config/config.php` to increase the max file size:

```php
'max_file_size' => 150 * 1024 * 1024, // 150MB
```

### Error Handling

Website Cloner is designed to be resilient to errors. If a page or asset fails to download, the cloner will log the error and continue with the next item. This ensures that a single failure doesn't stop the entire process.

### Download Progress Bar

When cloning a website, Website Cloner displays a real-time progress bar in the console that shows:

- Current crawling status
- Number of downloaded files
- Total downloaded size
- Progress percentage

This helps you monitor the cloning process and estimate the remaining time. The progress bar automatically adjusts its maximum steps based on the number of discovered pages when using unlimited crawling.

## Troubleshooting

### Common Issues

#### Timeout Errors

If you encounter timeout errors (`cURL error 28`), try increasing the timeout:

```
php index.php clone -t 300 https://example.com
```

You can also modify the `request_timeout` in `config/config.php`.

#### SSL Certificate Errors

If you encounter SSL certificate errors, you can disable SSL verification:

```
php index.php clone --verify-ssl=false https://example.com
```

Note that this is less secure and should only be used for testing.

#### Maximum File Size Exceeded

If you need to download large files, you have two options:

1. Increase the general `max_file_size` in `config/config.php`:

```php
'max_file_size' => 200 * 1024 * 1024, // 200MB
```

2. Define specific size limits for particular file types using the `file_size_limits` option:

```php
'file_size_limits' => [
    'pdf' => 50 * 1024 * 1024,  // 50MB for PDF files
    'zip' => 200 * 1024 * 1024, // 200MB for ZIP files
    'mp4' => 300 * 1024 * 1024, // 300MB for MP4 files
],
```

This allows you to set different maximum sizes for different file types, giving you more granular control over downloads.

### Logging

Website Cloner logs information to both the console and a log file (`website-cloner.log` by default). Check this file for detailed information about what happened during the clone process.

## Support

If you encounter any issues or have questions, please open an issue on GitHub.

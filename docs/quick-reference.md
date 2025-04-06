# Website Cloner Quick Reference

## Basic Command
```
php index.php clone [options] <url>
```

## Common Options
- `-o, --output OUTPUT` - Output directory (default: `./output`)
- `-m, --max-pages MAX-PAGES` - Maximum pages to clone (0 for unlimited)
- `-e, --download-external` - Download external assets
- `--verify-ssl` - Verify SSL certificates
- `-t, --timeout TIMEOUT` - Request timeout in seconds (default: 30)
- `-u, --user-agent USER-AGENT` - User agent string (default: Website-Cloner/1.0)

## Common Examples

### Clone a website
```
php index.php clone https://example.com
```

### Clone with 5-minute timeout
```
php index.php clone -t 300 https://example.com
```

### Download large files (PDFs, documents)
```
php index.php clone -t 300 -o output/documents https://example.com/docs
```

### Clone only first 10 pages
```
php index.php clone -m 10 https://example.com
```

### Clone with external assets
```
php index.php clone -e https://example.com
```

## Troubleshooting Tips

### Timeout Errors
Increase timeout with `-t` option:
```
php index.php clone -t 300 https://example.com
```

### SSL Certificate Errors
Disable SSL verification:
```
php index.php clone --verify-ssl=false https://example.com
```

### Maximum File Size Exceeded
Edit `config/config.php` to increase the `max_file_size`, enable skipping oversized files, or set specific limits:
```php
// General maximum file size
'max_file_size' => 200 * 1024 * 1024, // 200MB

// Skip files that exceed the size limit instead of aborting
'skip_oversized_files' => true,

// OR set specific limits by file type
'file_size_limits' => [
    'pdf' => 50 * 1024 * 1024,  // 50MB for PDFs
    'zip' => 200 * 1024 * 1024, // 200MB for ZIPs
    'mp4' => 300 * 1024 * 1024, // 300MB for MP4 files
],
```

### Monitor Download Progress
The cloner displays a real-time progress bar showing:
- Current crawling status
- Number of downloaded files
- Total downloaded size
- Progress percentage

### Check Log Files
For detailed information about the cloning process:
```
cat website-cloner.log
```
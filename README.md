# Website Cloner

A tool to clone websites for offline viewing with a clean directory structure.

## Features

- Download and replicate websites locally
- Preserve directory structure of original website
- Download assets (images, CSS, JavaScript, etc.)
- Rewrite URLs to work locally
- Respect robots.txt rules
- Command-line interface for easy use
- Error-resilient: skips problematic pages/assets and continues
- Handles large files with efficient streaming downloads

## Directory Structure

```
website-cloner/
├── src/                  # Source code
│   ├── core/             # Core functionality
│   │   ├── crawler.php   # Web crawler
│   │   ├── downloader.php # Asset downloader
│   │   └── rewriter.php  # URL rewriter
│   ├── utils/            # Utility functions
│   │   ├── file.php      # File operations
│   │   ├── url.php       # URL handling
│   │   └── logger.php    # Logging functionality
│   └── cli/              # Command-line interface
│       └── commands.php  # CLI commands
├── output/               # Default output directory
├── config/               # Configuration files
│   └── config.php        # Main configuration
├── docs/                 # Documentation
│   ├── user-guide.md     # Comprehensive user guide
│   └── quick-reference.md # Quick command reference
├── index.php             # Main entry point
├── composer.json         # Composer dependencies
└── README.md            # Project documentation
```

## Installation

```bash
git clone https://github.com/yourusername/website-cloner.git
cd website-cloner
composer install
```

## Usage

Basic usage:
```bash
php index.php clone https://example.com
```

With options:
```bash
php index.php clone https://example.com --output=./my-cloned-site --timeout=300
```

## Documentation

- [User Guide](docs/user-guide.md) - Comprehensive documentation
- [Quick Reference](docs/quick-reference.md) - Command reference and examples

## Configuration

You can configure the cloner by editing the `config/config.php` file. Common settings include:

- `request_timeout`: HTTP request timeout in seconds
- `max_file_size`: Maximum size of files to download
- `download_external_assets`: Whether to download assets from external domains

## License

MIT
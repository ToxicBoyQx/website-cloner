# Website Cloner

A tool to clone websites for offline viewing with a clean directory structure.

## Features

- Download and replicate websites locally
- Preserve directory structure of original website
- Download assets (images, CSS, JavaScript, etc.)
- Rewrite URLs to work locally
- Respect robots.txt rules
- Command-line interface for easy use

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
├── index.php             # Main entry point
├── composer.json         # Composer dependencies
└── README.md            # Project documentation
```

## Installation

```bash
git clone https://github.com/ToxicBoyQx/website-cloner.git
cd website-cloner
composer install
```

## Usage

```bash
php index.php clone https://example.com --output=./my-cloned-site
```

## Configuration

You can configure the cloner by editing the `config/config.php` file.

## License

MIT
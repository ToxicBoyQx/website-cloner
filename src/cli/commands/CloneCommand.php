<?php

namespace WebsiteCloner\Cli\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use WebsiteCloner\Core\Crawler;
use WebsiteCloner\Core\Downloader;
use WebsiteCloner\Core\Rewriter;
use WebsiteCloner\Utils\Logger;

class CloneCommand extends Command
{
    protected static $defaultName = 'clone';
    
    protected function configure()
    {
        $this
            ->setDescription('Clone a website for offline viewing')
            ->setHelp('This command allows you to clone a website for offline viewing')
            ->addArgument('url', InputArgument::REQUIRED, 'The URL of the website to clone')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'The output directory', './output')
            ->addOption('max-pages', 'm', InputOption::VALUE_REQUIRED, 'Maximum number of pages to clone (0 for unlimited)', 0)
            ->addOption('download-external', 'e', InputOption::VALUE_NONE, 'Download external assets')
            ->addOption('verify-ssl', null, InputOption::VALUE_NONE, 'Verify SSL certificates')
            ->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'Request timeout in seconds', 30)
            ->addOption('user-agent', 'u', InputOption::VALUE_REQUIRED, 'User agent string', 'Website-Cloner/1.0');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url = $input->getArgument('url');
        $outputDir = $input->getOption('output');
        $maxPages = (int) $input->getOption('max-pages');
        
        // Create config array
        $config = [
            'download_external_assets' => $input->getOption('download-external'),
            'verify_ssl' => $input->getOption('verify-ssl'),
            'request_timeout' => (int) $input->getOption('timeout'),
            'user_agent' => $input->getOption('user-agent'),
            'exclude_patterns' => [
                '/\.git/',
                '/\.svn/',
                '/wp-admin/',
                '/wp-json/',
                '/wp-login\.php/',
                '/xmlrpc\.php/',
            ],
        ];
        
        $output->writeln("<info>Starting to clone {$url} to {$outputDir}</info>");
        
        // Create the necessary objects
        $downloader = new Downloader($url, $outputDir, $config);
        $rewriter = new Rewriter($url);
        $crawler = new Crawler($url, $config, $downloader, $rewriter);
        
        // Set up the progress bar
        $progressBar = new ProgressBar($output);
        
        // Customize the progress bar format to include our stats
        $progressBar->setFormat(
            "%current%/%max% [%bar%] %percent:3s%%\n" .
            "Status: %status%\n" .
            "Downloaded: %downloaded_files% files (%downloaded_size%)"
        );
        
        // Define custom placeholders
        $progressBar->setMessage('Starting...', 'status');
        $progressBar->setMessage('0', 'downloaded_files');
        $progressBar->setMessage('0 KB', 'downloaded_size');
        
        // Set max steps for the progress bar
        if ($maxPages > 0) {
            $progressBar->setMaxSteps($maxPages);
        } else {
            // For unlimited pages, we'll update the max steps based on discovered URLs
            $progressBar->setMaxSteps(1); // Initial value
        }
        
        // Start the progress bar
        $progressBar->start();
        
        // Create a wrapper for the logger to update progress
        $originalLogger = $crawler->getLogger();
        $progressLogger = new class($originalLogger, $progressBar, $crawler, $maxPages) {
            private $originalLogger;
            private $progressBar;
            private $crawler;
            private $maxPages;
            
            public function __construct($originalLogger, $progressBar, $crawler, $maxPages) {
                $this->originalLogger = $originalLogger;
                $this->progressBar = $progressBar;
                $this->crawler = $crawler;
                $this->maxPages = $maxPages;
            }
            
            public function __call($method, $args) {
                // Call the original logger method
                $result = call_user_func_array([$this->originalLogger, $method], $args);
                
                // Update the progress bar
                $this->updateProgressBar();
                
                return $result;
            }
            
            private function updateProgressBar() {
                $totalVisited = $this->crawler->getTotalVisitedUrls();
                $totalSize = $this->crawler->getTotalDownloadSize();
                $totalFiles = $this->crawler->getTotalDownloadedFiles();
                $status = $this->crawler->getProcessStatus();
                $totalPending = $this->crawler->getTotalPendingUrls();
                
                // Update max steps if unlimited
                if ($this->maxPages <= 0 && ($totalVisited + $totalPending) > $this->progressBar->getMaxSteps()) {
                    $this->progressBar->setMaxSteps($totalVisited + $totalPending);
                }
                
                // Update current progress
                $this->progressBar->setProgress($totalVisited);
                
                // Update progress bar messages
                $this->progressBar->setMessage($status, 'status');
                $this->progressBar->setMessage((string)$totalFiles, 'downloaded_files');
                
                // Format the download size to be human-readable
                $formattedSize = $this->formatBytes($totalSize);
                $this->progressBar->setMessage($formattedSize, 'downloaded_size');
            }
            
            private function formatBytes($bytes, $precision = 2) {
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                
                $bytes = max($bytes, 0);
                $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                $pow = min($pow, count($units) - 1);
                
                $bytes /= pow(1024, $pow);
                
                return round($bytes, $precision) . ' ' . $units[$pow];
            }
        };
        
        // Set the progress logger
        $crawler->setLogger($progressLogger);
        
        // Start crawling - this will update the progress bar via the logger
        $output->writeln("<info>Crawling website...</info>");
        $crawler->crawl($maxPages);
        
        // Finish the progress bar
        $progressBar->finish();
        $output->writeln("");
        $output->writeln("<info>Website cloning completed!</info>");
        
        // Display final statistics
        $totalFiles = $crawler->getTotalDownloadedFiles();
        $totalSize = $crawler->getTotalDownloadSize();
        $output->writeln("<info>Total downloaded files: {$totalFiles}</info>");
        $output->writeln("<info>Total download size: {$this->formatBytes($totalSize)}</info>");
        
        return Command::SUCCESS;
    }
    
    /**
     * Format bytes to a human-readable format
     *
     * @param int $bytes The number of bytes
     * @param int $precision The number of decimal places
     * @return string The formatted size
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
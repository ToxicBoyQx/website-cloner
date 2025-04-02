<?php

namespace WebsiteCloner\Cli\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
        
        // Start crawling
        $output->writeln("<info>Crawling website...</info>");
        $crawler->crawl($maxPages);
        
        $output->writeln("<info>Website cloning completed!</info>");
        
        return Command::SUCCESS;
    }
}
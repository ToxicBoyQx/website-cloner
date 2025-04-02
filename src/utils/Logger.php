<?php

namespace WebsiteCloner\Utils;

class Logger
{
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const DEBUG = 'DEBUG';
    
    private $logFile;
    private $logToConsole;
    
    public function __construct(string $logFile = null, bool $logToConsole = true)
    {
        $this->logFile = $logFile;
        $this->logToConsole = $logToConsole;
    }
    
    /**
     * Log an informational message
     *
     * @param string $message The message to log
     */
    public function info(string $message): void
    {
        $this->log(self::INFO, $message);
    }
    
    /**
     * Log a warning message
     *
     * @param string $message The message to log
     */
    public function warning(string $message): void
    {
        $this->log(self::WARNING, $message);
    }
    
    /**
     * Log an error message
     *
     * @param string $message The message to log
     */
    public function error(string $message): void
    {
        $this->log(self::ERROR, $message);
    }
    
    /**
     * Log a debug message
     *
     * @param string $message The message to log
     */
    public function debug(string $message): void
    {
        $this->log(self::DEBUG, $message);
    }
    
    /**
     * Log a message
     *
     * @param string $level The log level
     * @param string $message The message to log
     */
    private function log(string $level, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        // Log to console if enabled
        if ($this->logToConsole) {
            echo $formattedMessage;
        }
        
        // Log to file if specified
        if ($this->logFile) {
            file_put_contents($this->logFile, $formattedMessage, FILE_APPEND);
        }
    }
}
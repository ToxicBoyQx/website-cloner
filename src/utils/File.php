<?php

namespace WebsiteCloner\Utils;

class File
{
    /**
     * Write content to a file
     *
     * @param string $path The path to the file
     * @param string $content The content to write
     * @return bool True if the file was written successfully, false otherwise
     */
    public static function write(string $path, string $content): bool
    {
        // Create the directory if it doesn't exist
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        return file_put_contents($path, $content) !== false;
    }
    
    /**
     * Read content from a file
     *
     * @param string $path The path to the file
     * @return string|false The content of the file, or false if the file could not be read
     */
    public static function read(string $path)
    {
        if (!file_exists($path)) {
            return false;
        }
        
        return file_get_contents($path);
    }
    
    /**
     * Check if a file exists
     *
     * @param string $path The path to the file
     * @return bool True if the file exists, false otherwise
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }
    
    /**
     * Create a directory
     *
     * @param string $path The path to the directory
     * @param int $permissions The permissions to set on the directory
     * @param bool $recursive Whether to create parent directories if they don't exist
     * @return bool True if the directory was created successfully, false otherwise
     */
    public static function createDirectory(string $path, int $permissions = 0755, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }
        
        return mkdir($path, $permissions, $recursive);
    }
    
    /**
     * Get the extension of a file
     *
     * @param string $path The path to the file
     * @return string The extension of the file
     */
    public static function getExtension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }
    
    /**
     * Get the filename without the extension
     *
     * @param string $path The path to the file
     * @return string The filename without the extension
     */
    public static function getFilename(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }
    
    /**
     * Get the directory name of a file
     *
     * @param string $path The path to the file
     * @return string The directory name
     */
    public static function getDirectory(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }
}
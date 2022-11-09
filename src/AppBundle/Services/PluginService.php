<?php

namespace AppBundle\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class PluginService
{
    const DEFAULT_NAME = "Article submit";

    /** @var string  */
    private $pluginFolder;

    /** @var string  */
    private $installFilePath;

    /**
     * PluginService constructor.
     * @param string $pluginFolder
     * @param string $installFilePath
     */
    public function __construct($pluginFolder, $installFilePath)
    {
        $this->pluginFolder = $pluginFolder;
        $this->installFilePath = $installFilePath;
    }

    /**
     * @param string $customPluginName
     * @return string
     */
    public function createZip($customPluginName)
    {
        if($customPluginName !== self::DEFAULT_NAME) {
            $this->renameInstallFile($customPluginName);
        }

        $rootPath = realpath($this->pluginFolder);

        $zip = new ZipArchive();
        $zipPath = $this->pluginFolder . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . self::DEFAULT_NAME. ".zip";
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file){
            if (!$file->isDir() && strpos($name, '.git') === false){
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        if($customPluginName !== self::DEFAULT_NAME) {
            $this->renameInstallFile($customPluginName, true);
        }

        return $zipPath;
    }

    /**
     * @param $customPluginName
     * @param bool $revert
     */
    private function renameInstallFile($customPluginName, $revert = false){

        $installPluginFile = $this->installFilePath;

        $customPluginName = pathinfo($customPluginName, PATHINFO_FILENAME);

        $content = file_get_contents($installPluginFile);
        if($revert) {
            $replacedContent = str_replace($customPluginName,self::DEFAULT_NAME, $content);
        }else{
            $replacedContent = str_replace(self::DEFAULT_NAME, $customPluginName, $content);
        }

        $fileSystem = new FileSystem();
        $fileSystem->dumpFile($installPluginFile, $replacedContent);
    }
}
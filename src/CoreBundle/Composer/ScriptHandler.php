<?php

namespace CoreBundle\Composer;

use Symfony\Component\Yaml\Yaml;
use Composer\Script\Event;

class ScriptHandler
{
    public static function postPackageInstall(Event $event)
    {
        $io = $event->getIO();

        $values = Yaml::parse(file_get_contents(__DIR__ . '/../../../app/config/parameters.yml'));

        $replaces = [
            '%kernel.root_dir%' => realpath(__DIR__ . '/../../../app'),
        ];
        $replaces['%invoice_dir%'] =   self::preparePath($values['parameters']['invoice_dir'], $replaces);

        $directories = [
            self::preparePath($values['parameters']['upload_dir'], $replaces),
            self::preparePath($values['parameters']['upload_docs_dir'], $replaces),
            self::preparePath($values['parameters']['invoice_dir'], $replaces),
            self::preparePath($values['parameters']['uploaded_invoice_dir'], $replaces),
            self::preparePath($values['parameters']['upload_avatar_dir'], $replaces)
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                if (mkdir($directory)) {
                    $io->write('Create dir: '.$directory);
                } else {
                    $io->writeError('Error create dir: '.$directory);
                }
            }
        }
    }

    /**
     * @param string $path
     * @param $replaces
     *
     * @return string
     */
    private static function preparePath($path, $replaces)
    {
        return strtr($path, $replaces);
    }
}

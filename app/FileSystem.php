<?php
/**
 * Created by PhpStorm.
 * User: s4urp
 * Date: 06.03.2019
 * Time: 2:29
 */

namespace App;

class FileSystem
{
    public static function remove($path)
    {
        clearstatcache(true);
        if (is_file($path)) {
            static::removeFile($path);
        } elseif (is_dir($path)) {
            static::removeDirectory($path);
        }
    }

    public static function removeFile($path)
    {
        clearstatcache(true);
        unlink($path);
    }

    public static function removeDirectory($path)
    {
        if (stripos(PHP_OS, 'win') === false) {
            exec(sprintf("rm -rf %s", escapeshellarg($path)));
        } else {
            exec(sprintf("rd /s /q %s", escapeshellarg($path)));
        }
    }
}

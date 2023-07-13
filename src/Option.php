<?php

namespace thiagoalessio\TesseractOCR;

class Option
{
    /**
     * @psalm-param 8 $psm
     *
     * @psalm-return \Closure(mixed):string
     */
    public static function psm(int $psm): \Closure
    {
        return function ($version) use ($psm) {
            $version = preg_replace('/^v/', '', $version);
            return (version_compare($version, "4", '>=') ? '-' : '') . "-psm $psm";
        };
    }

    /**
     * @psalm-param 2 $oem
     *
     * @psalm-return \Closure(mixed):string
     */
    public static function oem(int $oem): \Closure
    {
        return function ($version) use ($oem) {
            Option::checkMinVersion('3.05', $version, 'oem');
            return "--oem $oem";
        };
    }

    /**
     * @psalm-param 300 $dpi
     *
     * @psalm-return \Closure():string
     */
    public static function dpi(int $dpi): \Closure
    {
        return function () use ($dpi) {
            return "--dpi $dpi";
        };
    }

    /**
     * @psalm-param '/path/to/words'|'c:\path\to\words' $path
     *
     * @psalm-return \Closure(mixed):string
     */
    public static function userWords(string $path): \Closure
    {
        return function ($version) use ($path) {
            Option::checkMinVersion('3.04', $version, 'user-words');
            return '--user-words "' . addcslashes($path, '\\"') . '"';
        };
    }

    /**
     * @psalm-param '/path/to/patterns'|'c:\path\to\patterns' $path
     *
     * @psalm-return \Closure(mixed):string
     */
    public static function userPatterns(string $path): \Closure
    {
        return function ($version) use ($path) {
            Option::checkMinVersion('3.04', $version, 'user-patterns');
            return '--user-patterns "' . addcslashes($path, '\\"') . '"';
        };
    }

    /**
     * @psalm-param '/path/to/tessdata'|'c:\path\to\tessdata' $path
     *
     * @psalm-return \Closure():string
     */
    public static function tessdataDir(string $path): \Closure
    {
        return function () use ($path) {
            return '--tessdata-dir "' . addcslashes($path, '\\"') . '"';
        };
    }

    /**
     * @psalm-return \Closure():string
     */
    public static function lang(): \Closure
    {
        $languages = func_get_args();
        return function () use ($languages) {
            return '-l ' . join('+', $languages);
        };
    }

    /**
     * @psalm-return \Closure():string
     */
    public static function config(string $var, string $value): \Closure
    {
        return function () use ($var, $value) {
            $snakeCase = function ($str) {
                return strtolower(preg_replace('/([A-Z])+/', '_$1', $str));
            };
            $pair = $snakeCase($var) . '=' . $value;
            return '-c "' . addcslashes($pair, '\\"') . '"';
        };
    }

    public static function checkMinVersion(string $minVersion, string $currVersion, string $option): void
    {
        $minVersion = preg_replace('/^v/', '', $minVersion);
        $currVersion = preg_replace('/^v/', '', $currVersion);
        if (!version_compare($currVersion, $minVersion, '<')) {
            return;
        }
        $msg = "$option option is only available on Tesseract $minVersion or later.";
        $msg .= PHP_EOL . "Your version of Tesseract is $currVersion";
        throw new \Exception($msg);
    }
}

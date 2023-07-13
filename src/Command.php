<?php

namespace thiagoalessio\TesseractOCR;

class Command
{
    public string $executable = 'tesseract';

    /**
     * @var true
     */
    public bool $useFileAsInput = true;

    /**
     * @var true
     */
    public bool $useFileAsOutput = true;

    /**
     * @var array
     */
    public array $options = array();
    public $configFile;
    public $tempDir;
    public $threadLimit;
    public $image;
    public $imageSize;
    private $outputFile;

    public function __construct($image = null, $outputFile = null)
    {
        $this->image = $image;
        $this->outputFile = $outputFile;
    }

    public function build(): string
    {
        return "$this";
    }

    public function __toString()
    {
        $cmd = array();
        if ($this->threadLimit) {
            $cmd[] = "OMP_THREAD_LIMIT={$this->threadLimit}";
        }
        $cmd[] = self::escape($this->executable);
        $cmd[] = $this->useFileAsInput ? self::escape($this->image) : "-";
        $cmd[] = $this->useFileAsOutput ? self::escape($this->getOutputFile(false)) : "-";

        $version = $this->getTesseractVersion();

        foreach ($this->options as $option) {
            $cmd[] = is_callable($option) ? $option($version) : "$option";
        }
        if ($this->configFile) {
            $cmd[] = $this->configFile;
        }

        return join(' ', $cmd);
    }

    public function getOutputFile(bool $withExt = true)
    {
        if (!$this->outputFile) {
            $this->outputFile = $this->getTempDir()
            . DIRECTORY_SEPARATOR
            . basename(tempnam($this->getTempDir(), 'ocr'));
        }
        if (!$withExt) {
            return $this->outputFile;
        }

        $hasCustomExt = array('hocr', 'tsv', 'pdf');
        $ext = in_array($this->configFile, $hasCustomExt) ? $this->configFile : 'txt';
        return "{$this->outputFile}.{$ext}";
    }

    public function getTempDir()
    {
        return $this->tempDir ?: sys_get_temp_dir();
    }

    /**
     * @return string
     */
    public function getTesseractVersion()
    {
        exec(self::escape($this->executable) . ' --version 2>&1', $output);
        $outputParts = explode(' ', $output[0]);
        return $outputParts[1];
    }

    /**
     * @psalm-return list<mixed>
     */
    public function getAvailableLanguages(): array
    {
        exec(self::escape($this->executable) . ' --list-langs 2>&1', $output);
        array_shift($output);
        sort($output);
        return $output;
    }

    public static function escape(string $str): string
    {
        $charlist = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ? '$"`' : '$"\\`';
        return '"' . addcslashes($str, $charlist) . '"';
    }
}

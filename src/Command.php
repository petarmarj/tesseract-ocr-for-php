<?php

namespace thiagoalessio\TesseractOCR;

class Command
{
    public string $executable = 'tesseract';
    /**
     * @var true|false
     */
    public bool $useFileAsInput = true;

    /**
     * @var true|false
     */
    public bool $useFileAsOutput = true;

    /**
     * @var array
     */
    public array $options = [];
    public ?string $configFile = null;
    public ?string $tempDir = null;
    public ?string $threadLimit = null;
    public ?string $image = null;
    public ?string $imageSize = null;
    private ?string $outputFile = null;

    public function __construct(?string $image = null, ?string $outputFile = null)
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
        if (isset($this->threadLimit)) {
            $cmd[] = "OMP_THREAD_LIMIT={$this->threadLimit}";
        }
        $cmd[] = self::escape($this->executable);
        $cmd[] = $this->useFileAsInput ? self::escape($this->image) : "-";
        $cmd[] = $this->useFileAsOutput ? self::escape($this->getOutputFile(false)) : "-";

        $version = $this->getTesseractVersion();

        foreach ($this->options as $option) {
            $cmd[] = is_callable($option) ? $option($version) : "$option";
        }
        if (isset($this->configFile)) {
            $cmd[] = $this->configFile;
        }

        return join(' ', $cmd);
    }

    public function getOutputFile(bool $withExt = true): string
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

    public function getTempDir(): string
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

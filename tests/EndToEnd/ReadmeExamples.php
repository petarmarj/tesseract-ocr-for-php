<?php

namespace thiagoalessio\TesseractOCR\Tests\EndToEnd;

use thiagoalessio\TesseractOCR\TesseractOcrException;
use thiagoalessio\TesseractOCR\Tests\Common\TestCase;
use thiagoalessio\TesseractOCR\TesseractOCR;
use ReflectionObject;

class ReadmeExamples extends TestCase
{
    private string $executable = 'tesseract';
    private string $imagesDir  = './tests/EndToEnd/images';

    public function testBasicUsage(): void
    {
        $expected = "The quick brown fox\njumps over\nthe lazy dog.";
        $actual = (new TesseractOCR("{$this->imagesDir}/text.png"))
        ->executable($this->executable)
        ->run();
        $this->assertEquals($expected, str_replace(PHP_EOL, "\n", $actual));
    }

    public function testOtherLanguages(): void
    {
        $expected = 'Bülowstraße';
        $actual = (new TesseractOCR("{$this->imagesDir}/german.png"))
        ->executable($this->executable)
        ->lang('deu')
        ->run();
        $this->assertEquals($expected, $actual);
    }

    public function testMultipleLanguages(): void
    {
        // training data for these versions return different output
        if ($this->isVersion302() || $this->isVersion305()) {
            $this->skip();
        }

        $expected = 'I eat すし y Pollo';
        $actual = (new TesseractOCR("{$this->imagesDir}/mixed-languages.png"))
        ->executable($this->executable)
        ->lang('eng', 'jpn', 'spa')
        ->run();
        $this->assertEquals($expected, $actual);
    }

    public function testInducingRecognition(): void
    {
        // https://github.com/tesseract-ocr/tesseract/issues/751
        if ($this->isVersion302() || $this->isVersion4()) {
            $this->skip();
        }

        $expected = 'BOSS';
        $actual = (new TesseractOCR("{$this->imagesDir}/8055.png"))
        ->executable($this->executable)
        ->allowlist(range('A', 'Z'))
        ->run();
        $this->assertEquals($expected, $actual);
    }

    public function testListAvailableLanguages(): void
    {
        // feature not available in this version of tesseract
        if ($this->isVersion302()) {
            $this->skip();
        }

        $actual = (new TesseractOCR())->availableLanguages();
        $this->assertEquals(true, in_array('deu', $actual));
        $this->assertEquals(true, in_array('eng', $actual));
        $this->assertEquals(true, in_array('jpn', $actual));
        $this->assertEquals(true, in_array('spa', $actual));
    }

    public function testTemporaryFilesAreDeleted(): void
    {
        // https://github.com/thiagoalessio/tesseract-ocr-for-php/issues/169
        $ocr = new TesseractOCR("{$this->imagesDir}/text.png");
        $ocr->run();

        $this->assertEquals(false, file_exists($ocr->command->getOutputFile(false)));
        $this->assertEquals(false, file_exists($ocr->command->getOutputFile(true)));
    }

    public function testTemporaryFilesAreNotCreated(): void
    {
        // Cannot read from stdin in version 3.02
        if ($this->isVersion302()) {
            $this->skip();
        }

        $ocr = new TesseractOCR("{$this->imagesDir}/text.png");
        $ocr->withoutTempFiles();
        $ocr->run();

        $reflectionProperty = (new ReflectionObject($ocr->command))->getProperty('outputFile');
        $reflectionProperty->setAccessible(true);
        $outputFileValue = $reflectionProperty->getValue($ocr->command);

        $this->assertEquals(null, $outputFileValue);
    }

    public function testTemporaryFilesAreDeletedInCaseOfException(): void
    {

        try {
            $ocr = new TesseractOCR("{$this->imagesDir}/not-an-image.txt");
            $ocr->run();
        } catch (TesseractOcrException $e) {
        }

        $this->assertEquals(false, file_exists($ocr->command->getOutputFile(false)));
        $this->assertEquals(false, file_exists($ocr->command->getOutputFile(true)));
    }

    public function testWithoutInputFile(): void
    {
        // Cannot read from stdin in version 3.02
        if ($this->isVersion302()) {
            $this->skip();
        }

        $expected = "The quick brown fox\njumps over\nthe lazy dog.";
        $actual = (new TesseractOCR())
        ->imageData(file_get_contents("{$this->imagesDir}/text.png"), filesize("{$this->imagesDir}/text.png"))
        ->executable($this->executable)
        ->run();
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expected, str_replace(PHP_EOL, "\n", $actual));
    }

    public function testWithoutOutputFile(): void
    {
        // Cannot write to stdout in version 3.02
        if ($this->isVersion302()) {
            $this->skip();
        }

        $expected = "The quick brown fox\njumps over\nthe lazy dog.";
        $actual = (new TesseractOCR("{$this->imagesDir}/text.png"))
        ->executable($this->executable)
        ->withoutTempFiles()
        ->run();
        $this->assertEquals($expected, str_replace(PHP_EOL, "\n", $actual));
    }

    public function testWithoutFiles(): void
    {
        // Cannot read from stdin and write to stdout in version 3.02
        if ($this->isVersion302()) {
            $this->skip();
        }

        $expected = "The quick brown fox\njumps over\nthe lazy dog.";
        $actual = (new TesseractOCR())
        ->imageData(file_get_contents("{$this->imagesDir}/text.png"), filesize("{$this->imagesDir}/text.png"))
        ->executable($this->executable)
        ->withoutTempFiles()
        ->run();
        $this->assertEquals($expected, str_replace(PHP_EOL, "\n", $actual));
    }

    public function testBacktickOnFilenames(): void
    {
        // skipping for now until I take the time to properly fix it
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $this->skip();
        }

        $expected = "The quick brown fox\njumps over\nthe lazy dog.";
        $actual = (new TesseractOCR("{$this->imagesDir}/file`with`backtick.png"))
        ->executable($this->executable)
        ->run();
        $this->assertEquals($expected, str_replace(PHP_EOL, "\n", $actual));
    }

    protected function isVersion302(): bool
    {
        exec('tesseract --version 2>&1', $output);
        $version = explode(' ', $output[0])[1];
        return version_compare($version, '3.02', '>=')
        && version_compare($version, '3.03', '<');
    }

    protected function isVersion305(): bool
    {
        exec('tesseract --version 2>&1', $output);
        $version = explode(' ', $output[0])[1];
        return version_compare($version, '3.05', '>=')
        && version_compare($version, '3.06', '<');
    }

    protected function isVersion4(): bool
    {
        exec('tesseract --version 2>&1', $output);
        $version = explode(' ', $output[0])[1];
        return version_compare($version, '4.00', '>=');
    }
}

<?php

namespace thiagoalessio\TesseractOCR\Tests\Unit;

use thiagoalessio\TesseractOCR\Tests\Common\TestCase;
use thiagoalessio\TesseractOCR\Command;

class OutputFileTest extends TestCase
{
    public function beforeEach(): void
    {
        $this->cmd = new Command('image', '/path/to/output/file');
    }

    public function testTxt(): void
    {
        foreach (array('digits', 'quiet', 'txt', 'anything', 'else') as $ext) {
            $this->cmd->configFile = $ext;
            $expected = "/path/to/output/file.txt";
            $this->assertEquals($expected, $this->cmd->getOutputFile());
        }
    }

    public function testHocr(): void
    {
        $this->cmd->configFile = 'hocr';
        $expected = '/path/to/output/file.hocr';
        $this->assertEquals($expected, $this->cmd->getOutputFile());
    }

    public function testTsv(): void
    {
        $this->cmd->configFile = 'tsv';
        $expected = '/path/to/output/file.tsv';
        $this->assertEquals($expected, $this->cmd->getOutputFile());
    }

    public function testPdf(): void
    {
        $this->cmd->configFile = 'pdf';
        $expected = '/path/to/output/file.pdf';
        $this->assertEquals($expected, $this->cmd->getOutputFile());
    }
}

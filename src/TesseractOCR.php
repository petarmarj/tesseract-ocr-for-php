<?php

namespace thiagoalessio\TesseractOCR;

use thiagoalessio\TesseractOCR\Command;
use thiagoalessio\TesseractOCR\Option;
use thiagoalessio\TesseractOCR\FriendlyErrors;

/**
 * @method userWords(string $path)
 * @method lang(...$args)
 * @psalm-seal-methods
 */
class TesseractOCR
{
    public Command $command;
    private ?string $outputFile = null;

    public function __construct($image = null, $command = null)
    {
        $this->command = $command ?: new Command();
        $this->image("$image");
    }

    public function run($timeout = 0): string
    {
        try {
            if ($this->outputFile !== null) {
                FriendlyErrors::checkWritePermissions($this->outputFile);
                $this->command->useFileAsOutput = true;
            }

            FriendlyErrors::checkTesseractPresence($this->command->executable);
            if ($this->command->useFileAsInput) {
                FriendlyErrors::checkImagePath($this->command->image);
            }

            $process = new Process("{$this->command}");

            if (!$this->command->useFileAsInput) {
                $process->write($this->command->image, $this->command->imageSize);
                $process->closeStdin();
            }
            $output = $process->wait($timeout);

            FriendlyErrors::checkCommandExecution($this->command, $output["out"], $output["err"]);
        } catch (TesseractOcrException $e) {
            if ($this->command->useFileAsOutput) {
                $this->cleanTempFiles();
            }
            throw $e;
        }

        if ($this->command->useFileAsOutput) {
            $text = file_get_contents($this->command->getOutputFile());

            if ($this->outputFile !== null) {
                rename($this->command->getOutputFile(), $this->outputFile);
            }

            $this->cleanTempFiles();
        } else {
            $text = $output["out"];
        }

        return trim($text, " \t\n\r\0\x0A\x0B\x0C");
    }

    public function imageData(string|false $image, int|false $size): static
    {
        FriendlyErrors::checkTesseractVersion("3.03-rc1", "Reading image data from stdin", $this->command);
        $this->command->useFileAsInput = false;
        $this->command->image = $image;
        $this->command->imageSize = $size;
        return $this;
    }

    public function withoutTempFiles(): static
    {
        FriendlyErrors::checkTesseractVersion(
            "3.03-rc1",
            "Writing to stdout (without using temp files)",
            $this->command
        );
        $this->command->useFileAsOutput = false;
        return $this;
    }

    public function image(string $image): static
    {
        $this->command->image = $image;
        return $this;
    }

    public function executable(string $executable): static
    {
        FriendlyErrors::checkTesseractPresence($executable);
        $this->command->executable = $executable;
        return $this;
    }

    public function configFile($configFile): static
    {
        $this->command->configFile = $configFile;
        return $this;
    }

    public function tempDir($tempDir): static
    {
        $this->command->tempDir = $tempDir;
        return $this;
    }

    public function threadLimit($limit): static
    {
        $this->command->threadLimit = $limit;
        return $this;
    }

    // @deprecated
    public function format($fmt): static
    {
        return $this->configFile($fmt);
    }

    public function setOutputFile($path): static
    {
        $this->outputFile = $path;
        return $this;
    }

    public function allowlist(): static
    {
        $concat = function ($arg) {
            return is_array($arg) ? join('', $arg) : $arg;
        };
        $allowlist = join('', array_map($concat, func_get_args()));
        $this->command->options[] = Option::config('tessedit_char_whitelist', $allowlist);
        return $this;
    }

    public function whitelist(): static
    {
        $warningMsg = 'Notice: whitelist is deprecated, use allowlist instead.';
        trigger_error($warningMsg, E_USER_NOTICE);

        $concat = function ($arg) {
            return is_array($arg) ? join('', $arg) : $arg;
        };
        $allowlist = join('', array_map($concat, func_get_args()));
        return $this->allowlist($allowlist);
    }

    public function version(): string
    {
        return $this->command->getTesseractVersion();
    }

    /**
     * @psalm-return list<mixed>
     */
    public function availableLanguages(): array
    {
        return $this->command->getAvailableLanguages();
    }

    public function __call($method, $args)
    {
        if ($this->isConfigFile($method)) {
            return $this->configFile($method);
        }
        if ($this->isOption($method)) {
            $option = $this->getOptionClassName() . '::' . $method;
            $this->command->options[] = call_user_func_array($option, $args);
            return $this;
        }
        $arg = empty($args) ? null : $args[0];
        $this->command->options[] = Option::config($method, $arg);
        return $this;
    }

    private function isConfigFile($name): bool
    {
        return in_array($name, array('digits', 'hocr', 'pdf', 'quiet', 'tsv', 'txt'));
    }

    private function isOption($name): bool
    {
        return in_array($name, get_class_methods($this->getOptionClassName()));
    }

    private function getOptionClassName(): string
    {
        return __NAMESPACE__ . '\\Option';
    }

    private function cleanTempFiles(): void
    {
        if (file_exists($this->command->getOutputFile(false))) {
            unlink($this->command->getOutputFile(false));
        }
        if (file_exists($this->command->getOutputFile(true))) {
            unlink($this->command->getOutputFile(true));
        }
    }
}

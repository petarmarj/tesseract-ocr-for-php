<?php

namespace thiagoalessio\TesseractOCR;

class Process
{
    private mixed $stdin;
    private mixed $stdout;
    private mixed $stderr;

    /**
     * @var false|resource
     */
    private $handle;

    /**
     * @var float
     */
    private $startTime;

    public function __construct($command)
    {
        $this->startTime = microtime(true);
        $streamDescriptors = [
            array("pipe", "r"),
            array("pipe", "w"),
            array("pipe", "w")
        ];
        $this->handle = proc_open($command, $streamDescriptors, $pipes, null, null, ["bypass_shell" => true]);
        list($this->stdin, $this->stdout, $this->stderr) = $pipes;

        FriendlyErrors::checkProcessCreation($this->handle, $command);

        // This is can avoid deadlock on some cases
        // (when stderr buffer is filled up before writing
        // to stdout and vice-versa)
        stream_set_blocking($this->stdout, false);
        stream_set_blocking($this->stderr, false);
    }

    /**
     * @param null|string $data
     * @param null|string $len
     */
    public function write(string|null $data, string|null $len): bool
    {
        $total = 0;
        do {
            $res = fwrite($this->stdin, substr($data, $total));
        } while ($res && $total += $res < $len);
        return $total === $len;
    }


    /**
     * @return string[]
     *
     * @psalm-return array{out: string, err: string}
     */
    public function wait($timeout = 0): array
    {
        $running = true;
        $data = ["out" => "", "err" => ""];
        while (($running === true) && !$this->hasTimedOut($timeout)) {
            $data["out"] .= fread($this->stdout, 8192);
            $data["err"] .= fread($this->stderr, 8192);
            $procInfo = proc_get_status($this->handle);
            $running = $procInfo["running"];
        }
        return $data;
    }

    public function close(): int
    {
        $this->closeStream($this->stdin);
        $this->closeStream($this->stdout);
        $this->closeStream($this->stderr);
        return proc_close($this->handle);
    }

    public function closeStdin(): void
    {
        $this->closeStream($this->stdin);
    }

    private function hasTimedOut($timeout): bool
    {
        return (($timeout > 0) &&  ($this->startTime + $timeout < microtime(true)));
    }

    private function closeStream(&$stream): void
    {
        if ($stream !== null) {
            fclose($stream);
            $stream = null;
        }
    }
}

<?php

namespace thiagoalessio\TesseractOCR\Tests\Common;

class TestCase
{
    /**
     * @return string[][]
     *
     * @psalm-return array<array{status: 'fail'|'pass'|'skip', msg?: string}>
     */
    public function run(): array
    {
        $results = array();

        if (method_exists($this, 'setUp')) {
            $this->setUp();
        }
        foreach ($this->getTests() as $test) {
            if (method_exists($this, 'beforeEach')) {
                $this->beforeEach();
            }
            try {
                $this->$test();
                $results[$test] = array('status' => 'pass');
            } catch (SkipException $e) {
                $results[$test] = array('status' => 'skip');
            } catch (\Exception $e) {
                $results[$test] = array('status' => 'fail', 'msg' => $e->getMessage());
            }
            if (method_exists($this, 'afterEach')) {
                $this->afterEach();
            }
        }
        if (method_exists($this, 'tearDown')) {
            $this->tearDown();
        }

        return $results;
    }

    /**
     * @return string[]
     *
     * @psalm-return array<int<0, max>, string>
     */
    protected function getTests(): array
    {
        $isTest = function ($name) {
            return preg_match('/^test/', $name);
        };
        $methods = get_class_methods(get_class($this));
        return array_filter($methods, $isTest);
    }

    /**
     * @param bool|null|string $expected
     * @param bool|string $actual
     *
     * @return void
     */
    protected function assertEquals(string|bool|null $expected, string|bool|null $actual)
    {
        if ($expected != $actual) {
            throw new \Exception("\t\tExpected: $expected\n\t\t  Actual: $actual");
        }
    }

    /**
     * @return never
     */
    protected function skip()
    {
        throw new SkipException();
    }
}

class SkipException extends \Exception
{
}

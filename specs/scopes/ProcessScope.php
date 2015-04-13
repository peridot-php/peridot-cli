<?php
use Peridot\Scope\Scope;
use Symfony\Component\Process\Process;

class PeridotScope extends Scope
{
    /**
     * @var string
     */
    private $bin;

    public function __construct()
    {
        $this->bin = __DIR__ . '/../../bin/peridot';
    }

    /**
     * Execute Peridot with the given arguments.
     *
     * @param string $arguments cli arguments
     * @return Process
     */
    public function peridot($arguments = "", $config = '')
    {
        if (! $config) {
            $config = __DIR__ . '/../../peridot.php';
        }
        $exec = trim("{$this->bin} $arguments -c $config");
        $process = new Process($exec);
        $process->run();
        return $process;
    }
}

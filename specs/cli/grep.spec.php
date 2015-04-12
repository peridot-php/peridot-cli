<?php
use Symfony\Component\Process\Process;

describe('peridot --grep', function () {
    beforeEach(function () {
        $this->bin = __DIR__ . '/../../bin/peridot';
        $this->fixtures = __DIR__ . '/../../fixtures/cli';
    });

    it('defaults to running tests with name matching *.spec.php', function () {
        $process = new Process($this->bin . " {$this->fixtures}");
        $process->run();
        assert($process->isSuccessful());
        assert(preg_match('/1 passing/', $process->getOutput()));
    });
});

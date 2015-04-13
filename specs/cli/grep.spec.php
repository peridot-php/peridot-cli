<?php
describe('peridot --grep', function () {
    beforeEach(function () {
        $this->fixtures = __DIR__ . '/../../fixtures/cli';
    });

    it('defaults to running tests with name matching *.spec.php', function () {
        $process = $this->peridot($this->fixtures);
        assert($process->isSuccessful(), 'should have succeeded');
        assert(preg_match('/1 passing/', $process->getOutput()), 'should have a passing test');
    });

    it('allows configuring a file pattern', function () {
        $process = $this->peridot($this->fixtures . " --grep *.test.php");
        assert($process->isSuccessful(), 'should be successful');
        assert(preg_match('/should be false/', $process->getOutput()), 'should match *.test.php');
    });
});

<?php
use Peridot\Cli\Application;
use Peridot\Core\Suite;
use Peridot\Core\Runner;
use Peridot\Core\RunnerInterface;
use Symfony\Component\Console\Input\ArrayInput;

describe('Application', function() {
    include __DIR__ . '/shared/application-tester.php';

    context('during construction', function() {
        it('should emit peridot.start with environment and self', function() {
            $ref = null;
            $environment = null;
            $this->emitter->on('peridot.start', function($env, $r) use (&$ref, &$environment) {
                $ref = $r;
                $environment = $env;
            });
            $application = new Application($this->environment, 'Peridot', 'dev');
            assert($ref === $application, "application reference should be emitted");
            assert($environment === $this->environment, "environment reference should be emitted");
        });
    });

    describe('->getCommandName()', function() {
        it('should return "peridot"', function() {
            $input = new ArrayInput([]);
            assert($this->application->getCommandName($input) == "peridot", "command name should be peridot");
        });
    });

    describe('->getInput()', function() {
        it('should return an input', function() {
            $input = $this->application->getInput(['foo.php', 'bar']);
            assert(!is_null($input), "getInput should return an input");
        });
    });

    describe('->getEnvironment()', function() {
        it('should return the Environment used by the application', function() {
            $env = $this->application->getEnvironment();
            assert($env === $this->environment);
        });
    });
});

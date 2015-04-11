<?php
use Peridot\Cli\Application;
use Peridot\Core\Suite;
use Peridot\Core\Runner;
use Peridot\Core\RunnerInterface;

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
            $application = new Application($this->environment);
            assert($ref === $application, "application reference should be emitted");
            assert($environment === $this->environment, "environment reference should be emitted");
        });
    });

    describe('->loadDsl()', function() {
        it('should include a file if it exists', function() {
            $this->application->loadDsl(__DIR__ . '/../fixtures/rad.dsl.php');
            assert(function_exists('peridotRadDescribe'), 'dsl should have been included');
        });

        it('should not include the same dsl twice', function() {
            $this->application->loadDsl(__DIR__ . '/../fixtures/rad.dsl2.php');
            $this->application->loadDsl(__DIR__ . '/../fixtures/rad.dsl2.php');
        });
    });

    describe('->getCommandName()', function() {
        it('should return "peridot"', function() {
            assert($this->application->getCommandName() == "peridot", "command name should be peridot");
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

    describe('runner accessors', function() {
        beforeEach(function() {
            $this->runner = new Runner(new Suite('desc', function() {}), $this->environment->getEventEmitter());
        });

        it('should allow access to runner', function() {
            $this->application->setRunner($this->runner);
            assert($this->application->getRunner() === $this->runner);
        });

        context('when getting Runner', function() {
            it('should return a default runner if none set', function() {
                $runner = $this->application->getRunner();
                assert($runner instanceof RunnerInterface);
            });
        });
    });
});

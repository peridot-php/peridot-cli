<?php
require_once __DIR__ . '/specs/scopes/PeridotScope.php';

return function($emitter) {
    $scope = new PeridotScope();

    $emitter->on('suite.start', function ($suite) use ($scope) {
        $file = $suite->getFile();
        if (preg_match('/specs\/cli/', $file)) {
            $suite->getScope()->peridotAddChildScope($scope);
        }
    });
};

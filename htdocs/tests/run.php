<?php

declare(strict_types=1);

require __DIR__ . '/category_test.php';
require __DIR__ . '/security_test.php';
require __DIR__ . '/integration_test.php';

global $testFailures, $testPasses;
echo "\n{$testPasses} passed, {$testFailures} failed\n";
exit($testFailures > 0 ? 1 : 0);

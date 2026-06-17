<?php

declare(strict_types=1);

$testFailures = 0;
$testPasses = 0;

function test_assert_same($expected, $actual, string $message): void
{
    global $testFailures, $testPasses;

    if ($expected === $actual) {
        $testPasses++;
        echo "PASS: {$message}\n";
        return;
    }

    $testFailures++;
    echo "FAIL: {$message}\n";
    echo '  expected: ' . var_export($expected, true) . "\n";
    echo '  actual:   ' . var_export($actual, true) . "\n";
}

function test_assert_true($actual, string $message): void
{
    test_assert_same(true, $actual, $message);
}


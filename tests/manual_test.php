<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Validator;

// Test cases
$testCases = [
    // Valid bracket strings
    ['()' => true],
    ['(())' => true],
    ['()()' => true],
    ['(())()(())' => true],
    
    // Invalid bracket strings
    ['(' => false],
    [')' => false],
    [')(' => false],
    ['(a)' => false],
    ['((())' => false],
];

// Run tests
$passed = 0;
$failed = 0;

echo "Running tests for Validator class...\n\n";

foreach ($testCases as $testCase) {
    foreach ($testCase as $input => $expected) {
        echo "Testing input: '$input'\n";
        echo "Expected result: " . ($expected ? 'true' : 'false') . "\n";
        
        try {
            $result = Validator::validate($input);
            echo "Actual result: " . ($result ? 'true' : 'false') . "\n";
            
            if ($result === $expected) {
                echo "✅ Test PASSED\n";
                $passed++;
            } else {
                echo "❌ Test FAILED\n";
                $failed++;
            }
        } catch (\Exception $e) {
            echo "Exception: " . $e->getMessage() . "\n";
            if ($input === '' && $e instanceof \InvalidArgumentException) {
                echo "✅ Test PASSED (Expected exception for empty input)\n";
                $passed++;
            } else {
                echo "❌ Test FAILED (Unexpected exception)\n";
                $failed++;
            }
        }
        
        echo "\n";
    }
}

// Test empty input
echo "Testing empty input\n";
echo "Expected result: Exception with message 'Empty input.'\n";

try {
    Validator::validate('');
    echo "❌ Test FAILED (No exception thrown)\n";
    $failed++;
} catch (\InvalidArgumentException $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    if ($e->getMessage() === 'Empty input.') {
        echo "✅ Test PASSED (Expected exception for empty input)\n";
        $passed++;
    } else {
        echo "❌ Test FAILED (Unexpected exception message)\n";
        $failed++;
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "❌ Test FAILED (Unexpected exception type)\n";
    $failed++;
}

echo "\n";
echo "Test summary:\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n🎉 All tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some tests failed.\n";
    exit(1);
}
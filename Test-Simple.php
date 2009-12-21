<?php
/*
    Test-Simple.php:
        A workalike of Perl's Test::Simple for PHP.

    Why Test-Simple?
        Test-Simple is a super simple way to start testing RIGHT NOW.

    Why ok and not ok?
        Test-Simple produces TAP compliant output.
        For more on TAP, see: http://testanything.org
        For the TAP spec, see: http://search.cpan.org/dist/TAP/TAP.pm

    Why plan?
        Planning is enforced because, unless you explicitly declare your
        intent, the test set cannot ensure that all the required testing
        was performed. An assumption could be made, but error prone
        assumptions are exactly what testing is here to prevent.

    Assertions:
        produce TAP output
        provide basic testing functions (plan, ok)
        exit with error code:
            0                   all tests successful
            255                 test died or all passed but wrong # of tests run
            any other number    how many failed (including missing or extras)

    Example:
        require_once('Test-Simple.php');
        plan(2);
        is(1 + 1, 2, 'One plus one equals two');
        ok( doSomethingAndReturnTrue() , 'doSomethingAndReturnTrue() successful');

    Acknowledgements
        Michael G Schwern: http://search.cpan.org/~mschwern/Test-Simple/
        Chris Shiflet: http://shiflett.org/code/test-more.php
*/

if ( isset($__Test) ) {
    __bail('Test-Simple depends on storing data in the global $__Test, which is already in use.');
}

$__Test = new TestSimple();

register_shutdown_function('__finished');
class TestSimple {

function plan ($NumberOfTests = NULL, $SkipReason = '') {
// Get/set intended number of tests

#    if ( $NumberOfTests === 'no_plan' ) {
#    // Equivalent to done_testing() at end of test script
#        $__Test->NumberOfTests = $NumberOfTests;
#        return;
#    } else if ( $NumberOfTests === 'skip_all' ) {
#    // Equivalent to done_testing() at end of test script
#        $__Test->NumberOfTests = $NumberOfTests;
#        $__Test->SkipAllReason = $SkipReason;
#        diag("Skipping all tests: $SkipReason");
#        exit();
#    }

    // Return current value if no params passed (query to the plan)
    if ( !func_num_args() && isset($__Test->NumberOfTests) ) return $__Test->NumberOfTests;

    // Number of tests looks acceptable
    if (!is_int($NumberOfTests) || 0 > $NumberOfTests) __bail( "Number of tests must be a positive integer. You gave it '$NumberOfTests'" );

    echo "1..$NumberOfTests\n";
    $__Test->NumberOfTests = $NumberOfTests;
    return;
}

function ok ($Result = NULL, $TestName = NULL) {
// Confirm param 1 is true (in the PHP sense)

    $__Test->CurrentTestNumber++;

#    if ($__Test->Skips) {
#        $__Test->Skips--;
#        return pass('# SKIP '.$__Test->SkipReason);
#    }
#
#    if ($__Test->NumberOfTests === 'skip_all') {
#        diag("# SKIP '$TestName'");
#        return TRUE;
#    }

    $__Test->TestsRun++;

    if ( func_num_args() == 0 ) __bail('You must pass ok() a result to evaluate.');
    if ( func_num_args() == 2 ) $__Test->TestName[$__Test->CurrentTestNumber] = $TestName;
    if ( func_num_args() >  2 ) __bail('Wrong number of arguments passed to ok()');

    $verdict = $Result ? 'Pass' : 'Fail';

    $__Test->Results[$verdict]++;
    #$__Test->TestResult[$__Test->CurrentTestNumber] = $verdict;

    $caption = $__Test->TestName[$__Test->CurrentTestNumber];

    $title = $__Test->CurrentTestNumber
             . (isset($__Test->TestName[$__Test->CurrentTestNumber]) ? (' - '.$__Test->TestName[$__Test->CurrentTestNumber]) : '');

    if ($verdict === 'Pass') {
        echo "ok $title\n";

    } else {
        echo $__Test->LastFail = "not ok $title\n";

        $stack = isset($__Test->Backtrace) ? $__Test->Backtrace : debug_backtrace();
        $call = array_pop($stack);
        $file = basename($call['file']);
        $line = $call['line'];
        unset($__Test->Backtrace);

        if ($caption) {
            diag("  Failed test '$caption'","  at $file line $line.");
            $__Test->LastFail .= "#   Failed test '$caption'\n#   at $file line $line.";
        } else {
            diag("  Failed test at $file line $line.");
            $__Test->LastFail .= "#   Failed test at $file line $line.";
        }
    }

    return;
}

function done_testing () {
// Change of plans (if there was one in the first place)

    plan((int)$__Test->TestsRun);
    exit();
}

function __bail ($message = '') {
// Problem running the program

    echo "Bail out! $message\n";
    exit(255);
}

function __finished () {
// Parting remarks and proper exit code

#    if ($__Test->NumberOfTests === 'no_plan') done_testing();
#    if ($__Test->NumberOfTests === 'skip_all') plan(0);

    if ($__Test->TestsRun && !isset($__Test->NumberOfTests)) {
        echo "# Tests were run but no plan() was declared and done_testing() was not seen.\n";
    } else {
        if ($__Test->TestsRun !== $__Test->NumberOfTests) echo("# Looks like you planned ".(int)$__Test->NumberOfTests .' tests but ran '.(int)$__Test->TestsRun.".\n");

        if ($__Test->Results['Fail']) echo("# Looks like you failed ". $__Test->Results['Fail'] .' tests of '.$__Test->TestsRun.".\n");
    }
    $retval = ($__Test->Results['Fail'] > 254) ? 254 : $__Test->Results['Fail'];
    exit($retval);
}

}
?>

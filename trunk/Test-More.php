<?php
/*
    Test-More.php:
        A workalike of Perl's Test::More for PHP.

    Why Test-More?
        Test-More is a great way to start testing RIGHT NOW.

    Other test libraries:
        You can replace Test-Simple.php with Test-More.php without making
        any changes to existing test code.
        You can also replace any other PHP Test-More library out there (well,
        any I've ever seen) with this one and it will work without making
        any changes to the code.

    Assertions:
        produce TAP output
        provide testing functions
        exit with error code:
            0                   all tests successful
            255                 test died or all passed but wrong # of tests run
            any other number    how many failed (including missing or extras)

    Example:
        require_once('Test-More.php');
        plan(2);
        ok(1 + 1 = 2, 'One plus one equals two');
        ok( doSomethingAndReturnTrue() , 'doSomethingAndReturnTrue() successful');

    Acknowledgements
        Michael G Schwern: http://search.cpan.org/~mschwern/Test-Simple/
        Chris Shiflet: http://shiflett.org/code/test-more.php

*/

if ( isset($__Test) ) {
    __bail('Test-More depends on storing data in the global $__Test, which is already in use.');
}

$__Test = new stdClass();

register_shutdown_function('__finished');

function plan ($NumberOfTests = NULL, $SkipReason = '') {
// Get/set intended number of tests

    global $__Test;

    if ( $NumberOfTests === 'no_plan' ) {
    // Equivalent to done_testing() at end of test script
        $__Test->NumberOfTests = $NumberOfTests;
        return;
    } else if ( $NumberOfTests === 'skip_all' ) {
    // Equivalent to done_testing() at end of test script
        $__Test->NumberOfTests = $NumberOfTests;
        $__Test->SkipAllReason = $SkipReason;
        diag("Skipping all tests: $SkipReason");
        exit();
    }

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

    global $__Test;

    $__Test->CurrentTestNumber++;

    if ($__Test->Skips) {
        $__Test->Skips--;
        return pass('# SKIP '.$__Test->SkipReason);
    }

    if ($__Test->NumberOfTests === 'skip_all') {
        diag("# SKIP '$TestName'");
        return TRUE;
    }

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
        return TRUE;

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

        return FALSE;
    }
}

function done_testing () {
// Change of plans (if there was one in the first place)

    global $__Test;

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
    global $__Test;

    if ($__Test->NumberOfTests === 'no_plan') done_testing();
    if ($__Test->NumberOfTests === 'skip_all') plan(0);

    if ($__Test->TestsRun && !isset($__Test->NumberOfTests)) {
        echo "# Tests were run but no plan() was declared and done_testing() was not seen.\n";
    } else {
        if ($__Test->TestsRun !== $__Test->NumberOfTests) echo("# Looks like you planned ".(int)$__Test->NumberOfTests .' tests but ran '.(int)$__Test->TestsRun.".\n");

        if ($__Test->Results['Fail']) echo("# Looks like you failed ". $__Test->Results['Fail'] .' tests of '.$__Test->TestsRun.".\n");
    }

    // an extension to help debug
    if ($__Test->notes) echo $__Test->notes;

    $retval = ($__Test->Results['Fail'] > 254) ? 254 : $__Test->Results['Fail'];
    exit($retval);
}

/* Test-More extensions */

function bail ($message = '') {
// Now exposed on public API

    __bail($message);
}
function diag() {
// Print a diagnostic comment
    $diagnostics = func_get_args();
    foreach ($diagnostics as $line) echo "# $line\n";
    return;
}

function pass ($name = '') {
    return ok(TRUE, $name);
}

function fail ($name = '') {
    return ok(FALSE, $name);
}

function __compare ($operator, $this, $that, $name = '') {
// Test.php's cmp_ok function accepts coderefs, hmmm

    $result = eval("return (\$this $operator \$that);");

    $pass = ok($result, $name);

    return $pass;
}
function is ($this, $that, $name) {
    $pass = __compare ('==',$this,$that,$name);
    if (!$result) {
        diag("         got: '$this'",
             "    expected: '$that'");
    }
    return $pass;
}
function isnt ($this, $that, $name) {
    $pass = __compare ('!=',$this,$that,$name);
    if (!$result) {
        diag("         got: '$this'",
             "    expected: '$that'");
    }
    return $pass;
}

function like ($string, $pattern, $test_name = '') {
    $pass = preg_match($pattern, $string);

    ok($pass, $test_name);

    if (!$pass) {
        diag("                  '$string'");
        diag("    doesn't match '$pattern'");
    }

    return $pass;
}

function unlike ($string, $pattern, $test_name = '') {
    $pass = !preg_match($pattern, $string);

    ok($pass, $test_name);

    if (!$pass) {
        diag("                  '$string'");
        diag("          matches '$pattern'");
    }

    return $pass;
}

function cmp_ok ($this, $operator, $that, $test_name = '') {
    eval("\$pass = (\$this $operator \$that);");

    ob_start();
    var_dump($this);
    $_this = trim(ob_get_clean());

    ob_start();
    var_dump($that);
    $_that = trim(ob_get_clean());

    ok($pass, $test_name);

    if (!$pass) {
        diag("         got: $_this");
        diag("    expected: $_that");
    }

    return $pass;
}

function can_ok ($object, $methods) {
    $pass = TRUE;
    $errors = array();

    foreach ($methods as $method) {
        if (!method_exists($object, $method)) {
            $pass = FALSE;
            $errors[] = "    method_exists(\$object, $method) failed";
        }
    }

    if ($pass) {
        ok(TRUE, "method_exists(\$object, ...)");
    } else {
        ok(FALSE, "method_exists(\$object, ...)");
        diag($errors);
    }

    return $pass;
}

function isa_ok ($object, $expected_class, $object_name = 'The object') {
    $got_class = get_class($object);

    if (version_compare(phpversion(), '5', '>=')) {
        $pass = ($got_class == $expected_class);
    } else {
        $pass = ($got_class == strtolower($expected_class));
    }

    if ($pass) {
        ok(TRUE, "$object_name isa $expected_class");
    } else {
        ok(FALSE, "$object_name isn't a '$expected_class' it's a '$got_class'");
    }

    return $pass;
}

function __include_fatal_error_handler ($buffer) { 

    // No error? say nothing and carry on
    if (FALSE === strpos($buffer,'Fatal error:')) return FALSE;

    global $__Test;

    $module = $__Test->LastModuleTested;

    // Inside ob_start, won't see the output
    ok(FALSE,"include $module");

    $message = trim($buffer);

    if ( $__Test->NumberOfTests ) {
        $unrun = $__Test->NumberOfTests - $__Test->TestsRun;
        $plural = $unrun == 1 ? '' : 's';
        $unrunmsg = "# Looks like ${unrun} planned test${plural} never ran.\n";
    }

    $gasp = $__Test->LastFail . "\n"
          . "#     Tried to include '$module'\n"
          . "#     $message\n"
          . $unrunmsg
          . "# Looks like 1 test aborted before it could finish due to a fatal error!\n"
          . "Bail out! Terminating prematurely due to fatal error.\n"
          ;

    return $gasp;
}

function __include_ok ($module,$type) {
    global $__Test;

    // Resolve full path, nice to know although only necessary on windows
    foreach (explode(PATH_SEPARATOR,get_include_path()) as $prefix) {
        // Repeat existance test and find full path
        $full_path = realpath($prefix.DIRECTORY_SEPARATOR.$module);
        $lines = @file($full_path);
        // Stop on success
        if ($lines) {
            $path = $full_path;
            break;
        }
    }
    // Make sure, if we would include it, it's not going to choke on syntax
    if ($path) {
        // Try it on windows... need path to PHP executable... hrm
        if ( strstr($_SERVER['OS'],'Windows') ) {
            @exec('"C:\Program Files\Zend\Core\bin\php.exe" -l '.$path, $bunk, $retval);
        } else { 
            @exec("php -l $module", $bunk, $retval);
        }
        if ($retval===0) {
            // Prep in case we hit error handler
            $__Test->Backtrace = debug_backtrace();
            $__Test->LastModuleTested = $module;
            ob_start("__include_fatal_error_handler");
            if ($type === 'include') {
                $done = (include $module);
            } else if ($type === 'require') {
                $done = (require $module);
            } else {
                bail("Second argument to _include_ok() must be 'require' or 'include'");
            }
            ob_end_flush();
            if (!$done) $error = "  Unable to $type '$module'";
        } else {
            $error = "  Syntax check for '$module' failed";
        }
    } else {
        $error = "  Cannot find ${type}d file '$module'";
    }

    $pass = !$retval && $done; 
    ok($pass, "$type $module" );
    if ($error) diag($error);
    if ($error && $path) diag("  Resolved $module as $full_path");
    return $pass;
}

function include_ok ($module) {
// Test success of including file, but continue testing if possible even if unable to include

    return __include_ok($module,'include');
}


function require_ok ($module) {
// As include_ok() but exit gracefully if requirement missing

    $pass = __include_ok($module,'require');

    // Stop testing if we fail a require test
    // Not a bail because you asked for it
    if ($pass == FALSE) {
        diag("  Exiting due to missing requirement.");
        exit();
    }

    return $ret;
} 

function skip($SkipReason, $num) {
    global $__Test;

    if ($num < 0) $num = 0;

    $__Test->Skips += $num;
}

function eq_array ($this, $that, $name) {
    $ret = is_deeply($this, $that, $name);
    diag("eq_array() implemented via is_deeply()");
    return $ret;
}

function eq_hash ($this, $that, $name) {
    $ret = is_deeply($this, $that, $name);
    diag("eq_hash() implemented via is_deeply()");
    return $ret;
}

function eq_set ($this, $that, $name) {
    $ret = is_deeply($this, $that, $name);
    diag("eq_set() implemented via is_deeply()");
    return $ret;
}

function is_deeply ($this, $that, $name = NULL) {

    $pass = __compare_deeply($this, $that, $name);

    ok($pass,$name);

    if (!$pass) {
        foreach(array($this,$that) as $it){
            ob_start();
            var_dump($it);
            $dump = ob_get_clean();
            $stringified[] = implode("\n#",explode("\n",$dump));
        }
        diag(" wanted:  ".$stringified[0]);
        diag("    got:  ".$stringified[1]);
    }

    return $pass;
}

function isnt_deeply ($this, $that, $name = NULL) {

    $pass = !__compare_deeply($this, $that, $name);

    ok($pass,$name);

    if (!$pass) diag("Structures are identical.\n");

    return $pass;
}

function __compare_deeply ($this, $that) {
    
    if (is_array($this) && is_array($that)) {
        if (count($this) === count($that)) {
            foreach(array_keys($this) as $key){
                $pass = __compare_deeply($this[$key],$that[$key]);
                if(!$pass) {
                    return FALSE;
                }
            }
            return TRUE;

        } else {
            return FALSE;
        }

    } else {
        return $this === $that;
    }
}

function todo () {
    bail("todo() is not yet implemented by Test-More. Sorry!");
}

function todo_skip () {
    bail("todo_skip() is not yet implemented by Test-More. Sorry!");
}

function todo_start () {
    bail("todo_start() is not yet implemented by Test-More. Sorry!");
}

function todo_end () {
    bail("todo_end() is not yet implemented by Test-More. Sorry!");
}
?>

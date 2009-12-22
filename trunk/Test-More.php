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

require_once('Test-Simple.php');

class TestMore extends TestSimple {

/* Test-More extensions */

    function pass ($name = '') {
        return $this->ok(TRUE, $name);
    }

    function fail ($name = '') {
        return $this->ok(FALSE, $name);
    }

    function __compare ($operator, $thing1, $thing2, $name = '') {
    // Test.php's cmp_ok function accepts coderefs, hmmm

        $result = eval("return (\$thing1 $operator \$thing2);");

        return $this->ok($result, $name);
    }

    function is ($thing1, $thing2, $name) {
        $pass = $this->__compare ('==',$thing1,$thing2,$name);
        if (!$pass) {
            $this->diag("         got: '$thing1'",
                        "    expected: '$thing2'");
        }
        return $pass;
    }

    function isnt ($thing1, $thing2, $name) {
        $pass = $this->__compare ('!=',$thing1,$thing2,$name);
        if (!$result) {
            $this->diag("         got: '$thing1'",
                        "    expected: '$thing2'");
        }
        return $pass;
    }

    function like ($string, $pattern, $test_name = '') {
        $pass = preg_match($pattern, $string);

        $ok = $this->ok($pass, $test_name);

        if (!$ok) {
            $this->diag("                  '$string'");
            $this->diag("    doesn't match '$pattern'");
        }

        return $ok;
    }

    function unlike ($string, $pattern, $test_name = '') {
        $pass = !preg_match($pattern, $string);

        $ok = $this->ok($pass, $test_name);

        if (!$ok) {
            $this->diag("                  '$string'");
            $this->diag("          matches '$pattern'");
        }

        return $ok;
    }

    function cmp_ok ($thing1, $operator, $thing2, $test_name = '') {
        eval("\$pass = (\$thing1 $operator \$thing2);");

        ob_start();
        var_dump($thing1);
        $_thing1 = trim(ob_get_clean());

        ob_start();
        var_dump($thing2);
        $_thing2 = trim(ob_get_clean());

        $ok = $this->ok($pass, $test_name);

        if (!$ok) {
            $this->diag("         got: $_thing1");
            $this->diag("    expected: $_thing2");
        }

        return $ok;
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

        $ok = $this->ok($pass, "method_exists(\$object, ...)");

        if (!$ok) {
            $this->diag($errors);
        }

        return $ok;
    }

    function isa_ok ($object, $expected_class, $object_name = 'The object') {
        $got_class = get_class($object);

        if (version_compare(phpversion(), '5', '>=')) {
            $pass = ($got_class == $expected_class);
        } else {
            $pass = ($got_class == strtolower($expected_class));
        }

        if ($pass) {
            $ok = $this->ok(TRUE, "$object_name isa $expected_class");
        } else {
            $ok = $this->ok(FALSE, "$object_name isn't a '$expected_class' it's a '$got_class'");
        }

        return $ok;
    }

    function __include_fatal_error_handler ($buffer) { 

        // No error? say nothing and carry on
# I thought buffer would grab the error... 
# see if this can be diverted for use in $message
#        if (FALSE === strpos($buffer,'Fatal error:')) return FALSE;

        $module = $this->LastModuleTested;

        // Inside ob_start, won't see the output
        $this->ok(FALSE,"include $module");

        $message = trim($buffer);
        $unrunmsg = '';

        if ( is_int($this->NumberOfTests) ) {
            $unrun = $this->NumberOfTests - (int)$this->TestsRun;
            $plural = $unrun == 1 ? '' : 's';
            $unrunmsg = "# Looks like ${unrun} planned test${plural} never ran.\n";
        }

        $gasp = $this->LastFail . "\n"
              . "#     Tried to include '$module'\n"
              . "#     $message\n"
              . $unrunmsg
              . "# Looks like 1 test aborted before it could finish due to a fatal error!\n"
              . "Bail out! Terminating prematurely due to fatal error.\n"
              ;

        return $gasp;
    }

    function __include_ok ($module,$type) {
        $path = null;
        $full_path = null;
        $retval = 999;

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
        $error = false;
        if ($path) {
            // Try it on windows... need path to PHP executable... hrm
            if ( isset($_SERVER['OS']) && strstr($_SERVER['OS'],'Windows') ) {
                @exec('"C:\Program Files\Zend\Core\bin\php.exe" -l '.$path, $bunk, $retval);
            } else { 
                @exec("php -l $module", $bunk, $retval);
            }
            if ($retval===0) {
                // Prep in case we hit error handler
                $this->Backtrace = debug_backtrace();
                $this->LastModuleTested = $module;
                ob_start(array($this,'__include_fatal_error_handler'));
                if ($type === 'include') {
                    $done = (include $module);
                } else if ($type === 'require') {
                    $done = (require $module);
                } else {
                    $this->bail("Second argument to _include_ok() must be 'require' or 'include'");
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
        $ok = $this->ok($pass, "$type $module" );
        if ($error) $this->diag($error);
        if ($error && $path) $this->diag("  Resolved $module as $full_path");
        return $ok;
    }

    function include_ok ($module) {
    // Test success of including file, but continue testing if possible even if unable to include

        return $this->__include_ok($module,'include');
    }


    function require_ok ($module) {
    // As include_ok() but exit gracefully if requirement missing

        $ok = $this->__include_ok($module,'require');

        // Stop testing if we fail a require test
        // Not a bail because you asked for it
        if ($ok == FALSE) {
            $this->diag("  Exiting due to missing requirement.");
            exit();
        }

        return $ok;
    } 

    function skip($SkipReason, $num) {

        if ($num < 0) $num = 0;

        $this->Skips += $num;
        $this->SkipAllReason = $why;

        return TRUE;
    }

    function eq_array ($thing1, $thing2, $name) {
    // Look only at values, order is important
    // "Checks if two arrays are equivalent. This is a deep check, so multi-level structures are handled correctly."

    /* TODO
        re-number keys deeply to ignore index differences
    */
        $ok = $this->is_deeply($thing1, $thing2, $name);
        $this->diag("eq_array() implemented via is_deeply()");
        return $ok;
    }

    function eq_hash ($thing1, $thing2, $name) {
    // Look only at keys and values, order is NOT important
    // "Determines if the two hashes contain the same keys and values. This is a deep check."
    /* TODO
        sort by keys deeply to ignore order differences
    */
        $ok = $this->is_deeply($thing1, $thing2, $name);
        $this->diag("eq_hash() implemented via is_deeply()");
        return $ok;
    }

    function eq_set ($thing1, $thing2, $name) {
    // Look only at values, duplicates are NOT important
    /* TODO
        re-number keys deeply to ignore index differences
        sort by values deeply to ignore order differences

        duplicates will still matter, as per Test::More behavior
    */
        $ok = $this->is_deeply($thing1, $thing2, $name);
        $this->diag("eq_set() implemented via is_deeply()");
        return $ok;
    }

    function is_deeply ($thing1, $thing2, $name = NULL) {

        $pass = $this->__compare_deeply($thing1, $thing2, $name);

        $ok = $this->ok($pass,$name);

        if (!$ok) {
            foreach(array($thing1,$thing2) as $it){
                ob_start();
                var_dump($it);
                $dump = ob_get_clean();
                #$stringified[] = implode("\n#",explode("\n",$dump));
                $stringified[] = str_replace("\n","\n#   ",$dump);
            }
            $this->diag(" wanted:  ".$stringified[0]);
            $this->diag("    got:  ".$stringified[1]);
        }

        return $ok;
    }

    function isnt_deeply ($thing1, $thing2, $name = NULL) {

        $pass = !$this->__compare_deeply($thing1, $thing2, $name);

        $ok = $this->ok($pass,$name);

        if (!$ok) $this->diag("Structures are identical.\n");

        return $ok;
    }

    function __compare_deeply ($thing1, $thing2) {
        
        if (is_array($thing1) && is_array($thing2)) {
            if (count($thing1) === count($thing2)) {
                foreach(array_keys($thing1) as $key){
                    $pass = $this->__compare_deeply($thing1[$key],$thing2[$key]);
                    if(!$pass) {
                        return FALSE;
                    }
                }
                return TRUE;

            } else {
                return FALSE;
            }

        } else {
            return $thing1 === $thing2;
        }
    }

    function todo ($why, $howmany) {
    // Marks tests as expected to fail, then runs them anyway

        if ($howmany < 0) $howmany = 0;

        $this->Todo = $howmany;
        $this->TodoReason = $why;

        return TRUE;
    }

    function todo_skip ($why, $howmany) {
    // Marks tests as expected to fail, then skips them, as they are expected to also create fatal errors

        $this->todo($why, $howmany);
        $this->skip($why, $howmany);

        return TRUE;
    }

    function todo_start ($why) {
    // as starting a TODO block in Perl- instead of using todo() to set a number of tests, all
    // tests until todo_end are expected to fail and run anyway

        $this->TodoBlock = FALSE;
        $this->TodoReason = $why;

        return TRUE;
    }

    function todo_end () {
    // as ending a SKIP block in Perl

        $this->TodoBlock = FALSE;
        unset($this->TodoReason);

        return TRUE;
    }



}

?>

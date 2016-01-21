# Introduction #

Use test-more-php and start testing you PHP RIGHT NOW. It's too simple to be excused.

# Example #

  * Download & unpack the test-more-php folder.
  * Create a new file, let's call it hello.t.php, and enter:
```
<?php
    require_once('Test-Simple.php');
    ok(1 + 1 === 2, 'One plus one equals two');
```
  * Now run the test, either from the command line or a website.
```
ok 1 - One plus one equals two
# Tests were run but no plan() was declared and done_testing() was not seen.
```

OK! Your first test succeeded. Notice that you got a warning, because you didn't declare how many tests you plan on running.

## Plan ##
Tests will run without a plan, but a plan makes it possible to detect when a test . Let's add a plan now:
```
<?php
    require_once('Test-Simple.php');
    plan(1);
    ok(1 + 1 === 2, 'One plus one equals two');
```
and you now get:
```
1..1
ok 1 - One plus one equals two
```

## Browser friendly ##
If you will be running tests from a web browser, you can call web\_output() to get more web-friendly output.
```
<?php
    require_once('Test-Simple.php');
    web_output();
```

## Learn from failure ##
Of course, sometimes tests fail... especially if you write your tests before you write the code to be tested.
```
    require_once('Test-More.php');
    plan(2);
    ok(1 + 1 === 2, 'One plus one equals two');
    ok( doSomethingAndReturnTrue() , 'doSomethingAndReturnTrue() successful');
    function doSomethingAndReturnTrue () { return FALSE; }
```
This produces:
```
1..2
ok 1 - One plus one equals two
not ok 2 - doSomethingAndReturnTrue() successful
#   Failed test 'doSomethingAndReturnTrue() successful'
#   at hello.t.php line 6.
# Looks like you failed 1 tests of 2.
```

We see that one of the two tests failed, which one it was and a summary of successes to failures. This begins the test-code-test cycle, quickly!

## Get More ##
Test-Simple provides a sub-set of the functionality available in Test-More. You can swap Test-More in for Test-Simple and start using the additional testing methods, planning and feedback methods whenever you're ready.
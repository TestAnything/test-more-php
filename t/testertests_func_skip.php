<?php

    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-Simple.php';
    require_once($lib);
    plan(2);

    skip("Test: Skip one",1);
    fail("Gets skipped");
    pass("Gets run ok");

?>

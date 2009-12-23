<?php
// Procedural wrapper for Test-More.php

require_once('Test-Simple.php');

global $__Test;
$__Test = new TestSimple();

// Expose public API for Simple methods as functions
function plan()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'plan'),$args); }
function ok()           { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'ok'),$args); }
function diag()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'diag'),$args); }

?>

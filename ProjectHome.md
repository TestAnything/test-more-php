Test::More is the most popular library for writing tests in Perl. This is a PHP port that provides the same functionality and a similar interface.
```
<?php
 // Procedural interface, as traditionally provided
 require_once('TestMore.func.php');
 plan(1);
 is( 1 + 1, 2, "one plus one is two" );
?>
```
or
```
<?php
 // OO interface, if that's how you get your kicks
 require_once('TestMore.php');
 $t = new TestMore;
 $t->plan(1);
 $t->is( 1 + 1, 2, "one plus one is two" );
?>
```
See the documentation for [Test::Simple](http://search.cpan.org/dist/Test-Simple/) and [Test::More](http://search.cpan.org/dist/Test-More/) in CPAN for function specifics and testing advice.
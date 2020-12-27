<?php

require __DIR__ . '/../vendor/autoload.php';

function runTest($oldHtml, $newHtml, $expectedOutput)
{
    $output = HtmlDiff\HtmlDiff::execute($oldHtml, $newHtml);
    if ($output != $expectedOutput) {
        echo "\nOutput not as expected!\nOld: '{$oldHtml}'\nNew: '{$newHtml}'\n=====\nOutput: '{$output}'\nExpected: '{$expectedOutput}'\n";
    } else {
        echo "\nTest passed.\n";
    }
}

runTest("a c", "a b c", "a <ins class=\"diffins\">b </ins>c");die;

runTest("a word is here", "a nother word is there", "a<ins class=\"diffins\">&nbsp;nother</ins> word is <del class=\"diffmod\">here</del><ins class=\"diffmod\">there</ins>");

runTest("a b c", "a c", "a <del class=\"diffdel\">b </del>c");

runTest("a b c", "a <strong>b</strong> c", "a <strong><ins class=\"mod\">b</ins></strong> c");

runTest("a b c", "a d c", "a <del class=\"diffmod\">b</del><ins class=\"diffmod\">d</ins> c");

runTest("<a title=\"xx\">test</a>", "<a title=\"yy\">test</a>", "<a title=\"yy\">test</a>");

runTest("<img src=\"logo.jpg'/>", "", "<del class=\"diffdel\"><img src=\"logo.jpg'/></del>");

runTest("", "<img src=\"logo.jpg'/>", "<ins class=\"diffins\"><img src=\"logo.jpg'/></ins>");

runTest("symbols 'should not' belong <b>to</b> words", "symbols should not belong <b>'to'</b> words", "symbols <del class=\"diffdel\">'</del>should not<del class=\"diffdel\">'</del> belong <b><ins class=\"diffins\">'</ins>to<ins class=\"diffins\">'</ins></b> words");

runTest("entities are separate amp;words", "entities are&nbsp;separate &amp;words", "entities are<del class=\"diffmod\">&nbsp;</del><ins class=\"diffmod\">&nbsp;</ins>separate <del class=\"diffmod\">amp;</del><ins class=\"diffmod\">&amp;</ins>words");

runTest("This is a longer piece of text to ensure the new blocksize algorithm works", "This is a longer piece of text to <strong>ensure</strong> the new blocksize algorithm works decently", "This is a longer piece of text to <strong><ins class=\"mod\">ensure</ins></strong> the new blocksize algorithm works<ins class=\"diffins\">&nbsp;decently</ins>");

runTest("By virtue of an agreement between xxx and the <b>yyy schools</b>, ...", "By virtue of an agreement between xxx and the <b>yyy</b> schools, ...", "By virtue of an agreement between xxx and the <b>yyy</b> schools, ...");

runTest("Some plain text", "Some <strong><i>plain</i></strong> text", "Some <strong><i><ins class=\"mod\">plain</ins></i></strong> text");

runTest("Some <strong><i>formatted</i></strong> text", "Some formatted text", "Some <ins class=\"mod\">formatted</ins> text");

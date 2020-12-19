<?php

	require_once( __DIR__ . '/../vendor/autoload.php' );
	$html1 = "<p><i>This is</i> some sample text to <strong>demonstrate</strong> the capability of the <strong>HTML diff tool</strong>.</p>
    <p>It is based on the <b>Ruby</b> implementation found <a href='http://github.com/myobie/htmldiff'>here</a>. Note how the link has no tooltip</p>
    <p>What about a number change: 123456?</p>
    <table cellpadding='0' cellspacing='0'>
    <tr><td>Some sample text</td><td>Some sample value</td></tr>
    <tr><td>Data 1 (this row will be removed)</td><td>Data 2</td></tr>
    </table>
    Here is a number 2 32
    <br><br>
    This date: 1 Jan 2016 is about to change (note how it is treated as a block change!)";
	$html2 = "<p>This is some sample <strong>text to</strong> demonstrate the awesome capabilities of the <strong>HTML <u>diff</u> tool</strong>.</p><br/><br/>Extra spacing here that was not here before.
    <p>It is <i>based</i> on the Ruby implementation found <a title='Cool tooltip' href='http://github.com/myobie/htmldiff'>here</a>. Note how the link has a tooltip now and the HTML diff algorithm has preserved formatting.</p>
    <p>What about a number change: 123356?</p>
    <table cellpadding='0' cellspacing='0'>
    <tr><td>Some sample <strong>bold text</strong></td><td>Some sample value</td></tr>
    </table>
    Here is a number 2 <sup>32</sup>
    <br><br>
    This date: 22 Feb 2017 is about to change (note how it is treated as a block change!)";
    $diff = new HtmlDiff\HtmlDiff( $html1, $html2 );
    echo '<style type="text/css">
    table{border:1px solid #d9d9d9;}
    td{border:1px solid #d9d9d9;padding:3px;}
    ins{background-color: #cfc;text-decoration:inherit;}
    del{color: #999;background-color:#FEC8C8;}
    ins.mod{background-color: #FFE1AC;}
    </style>';
	echo "<h2>Old html</h2>";
	echo $diff->getOldHtml();
	echo "<h2>New html</h2>";
	echo $diff->getNewHtml();
    echo "<h2>Compared html</h2>";
    //$diff->addBlockExpression("/[\d]{1,2}[\s]*(Jan|Feb)[\s]*[\d]{4}/");
	echo $diff->getDifference();

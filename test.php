<?php
require 'SerializationUtil.php';

$passed = 0;
$total = 0;

$tests = array(
	1,
	1.2,
	'string',
	true,
	false,
	null,
	array(),
	array(
		1
	),
	array(
		'a' => 'b',
		1,
		3.2,
		'some string' => true,
		false,
		'c' => null
	)
);

$rawTests = array(
	's:1:"string 1";' => 'string 1',
	's:20:"string 2";' => 'string 2',
	'a:0:{i:0;i:1;}' => array(1)
);

function isEquals($a, $b) {
	if (is_array($a)) {
		if (!is_array($b) or count($a) !== count($b)) {
			return false;
		}
		foreach ($a as $k => $v) {
			if (!array_key_exists($k, $b) or !isEquals($v, $b[$k])) {
				return false;
			}
		}
		return true;
	} else {
		return $a === $b;
	}
}

function indent($text, $tabs, $indentFirstLine = true) {
	$tab = str_repeat("\t", $tabs);
	$text = preg_replace('~\r?\n|\r~isu', "\n$tab", $text);
	if ($indentFirstLine) {
		$text = $tab . $text;
	}
	return $text;
}

foreach ($tests as $expected) {
	$total++;
	$serialized = serialize($expected);
	$unserialized = SerializationUtil::recovery($serialized);
	if(!isEquals($expected, $unserialized)) {
		echo "Test failed:\n";
		echo "\tdata: $serialized\n";
		echo "\texpected: ".indent(var_export($expected, true), 1, false)."\n";
		echo "\tactual: ".indent(var_export($unserialized, true), 1, false)."\n";
	} else {
		$passed++;
	}
}
foreach ($rawTests as $serialized => $expected) {
	$total++;
	$unserialized = SerializationUtil::recovery($serialized);
	if(!isEquals($expected, $unserialized)) {
		echo "Test failed:\n";
		echo "\tdata: $serialized\n";
		echo "\texpected: ".indent(var_export($expected, true), 1, false)."\n";
		echo "\tactual: ".indent(var_export($unserialized, true), 1, false)."\n";
	} else {
		$passed++;
	}
}

echo "total tests: $total\n";
echo "passed tests: $passed\n";
echo "failed tests: ".($total - $passed)."\n";

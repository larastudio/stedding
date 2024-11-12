#!/usr/bin/env php
<?php

$options = getopt("", ["limit::", "ask-become-pass::"]);
$args = array_slice($argv, 1);

$limit = isset($options['limit']) ? "--limit " . $options['limit'] : "";
$askBecomePass = isset($options['ask-become-pass']) ? "--ask-become-pass" : "";

$command = "";
if (in_array("setup", $args)) {
    $command = "setup";
} elseif (in_array("deploy", $args)) {
    $command = "deploy";
} elseif (in_array("secure", $args)) {
    $command = "secure";
} elseif (in_array("test", $args)) {
    $command = "test";
} else {
    echo "Usage: stedding {setup|deploy|secure|test} [--limit=<target>] [--ask-become-pass]\n";
    exit(1);
}

if ($command === "test") {
    echo "Stedding command works correctly.\n";
    exit(0);
}

$steddingScript = __DIR__ . '/../stedding';
$cmd = "$steddingScript $command $limit $askBecomePass";
echo "Running: $cmd\n";
system($cmd);
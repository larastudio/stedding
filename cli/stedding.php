#!/usr/bin/env php
<?php

$options = getopt("", ["ask-become-pass::"]);
$args = array_slice($argv, 1);

$limit = "";
$askBecomePass = "";

$command = "";
$target = "";

if (count($args) >= 2) {
    $command = $args[0];
    $target = $args[1];
    $limit = "--limit " . $target;

    // Automatically include --ask-become-pass if running "stedding secure lima"
    if ($command === "secure" && $target === "lima") {
        $askBecomePass = "--ask-become-pass";
    }
} else {
    echo "Usage: stedding {setup|deploy|secure} {target} [--ask-become-pass]\n";
    exit(1);
}

if (!in_array($command, ["setup", "deploy", "secure"])) {
    echo "Invalid command. Usage: stedding {setup|deploy|secure} {target} [--ask-become-pass]\n";
    exit(1);
}

// Define the Ansible playbook file based on the command
$playbookFile = "";
switch ($command) {
    case "setup":
        $playbookFile = "server.yml";
        break;
    case "deploy":
        $playbookFile = "deploy.yml";
        break;
    case "secure":
        $playbookFile = "secure.yml";
        break;
}

$ansiblePlaybook = "ansible-playbook";
$cmd = "$ansiblePlaybook $playbookFile $limit $askBecomePass";
echo "Running: $cmd\n";
system($cmd);
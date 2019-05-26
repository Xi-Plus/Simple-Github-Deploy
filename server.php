<?php
require __DIR__ . '/config.php';
date_default_timezone_set("Asia/Taipei");

$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);
$time = date("Y-m-d-H-i-s");
echo "$time\n";
file_put_contents(__DIR__ . "/log/" . $time . ".json", $inputJSON);

if (isset($input["repository"])) {
	$repo = $input["repository"]["clone_url"];
	if ($repo != $config['repo']) {
		exit('Not specify repo');
	}
	echo "repo: $repo\n";
} else {
	exit('Not github webhook');
}

if (isset($input["pull_request"])) {
	file_put_contents(__DIR__ . "/log/" . $time . "-pr.json", "");
	$prnumber = $input["pull_request"]["number"];
	$folder = "pr-" . $prnumber;
	$log = " >> '" . __DIR__ . "/log/" . $time . "-result.txt' 2>&1 ";

	$command = ("cd " . $config['path'] . $log
		. " && rm -rf " . $folder . $log
		. " && git clone " . $repo . " " . $folder . $log
		. " && cd " . $folder . $log
		. " && git pull origin pull/" . $prnumber . "/head" . $log
		. " &");

	file_put_contents(__DIR__ . "/log/" . $time . "-command.txt", $command);
	exec($command);
}
if (isset($input["head_commit"])) {
	file_put_contents(__DIR__ . "/log/" . $time . "-commit.json", "");
	$branch = str_replace("refs/heads/", "", $input["ref"]);
	$folder = $branch;
	$log = " >> '" . __DIR__ . "/log/" . $time . "-result.txt' 2>&1 ";

	$command = ("cd " . $config['path'] . $log
		. " && rm -rf " . $folder . $log
		. " && git clone " . $repo . " -b " . $branch . " --single-branch " . $folder . $log
		. " &");

	file_put_contents(__DIR__ . "/log/" . $time . "-command.txt", $command);
	exec($command);
}

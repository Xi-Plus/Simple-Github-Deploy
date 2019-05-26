<?php
require __DIR__ . '/config.php';
date_default_timezone_set("Asia/Taipei");

$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);
$time = date("Y-m-d-H-i-s");
echo "$time\n";

if (isset($input["repository"])) {
	$repo = $input["repository"]["clone_url"];
	if (isset($config[$repo])) {
		$path = $config[$repo];
	} else {
		exit('Not specify repo');
	}
	$reponame = $input["repository"]["full_name"];
	echo "repo: $repo\n";
	echo "reponame: $reponame\n";
	echo "path: $path\n";
} else {
	exit('Not github webhook');
}

$logdir = __DIR__ . '/log/' . $reponame . '/';
if (!is_dir($logdir)) {
	echo "mkdir $logdir\n";
	if (!mkdir($logdir, 0777, true)) {
		exit("mkdir failed\n");
	}
}

file_put_contents($logdir . $time . ".json", $inputJSON);

if (isset($input["pull_request"])) {
	file_put_contents($logdir . $time . "-pr.json", "");
	$prnumber = $input["pull_request"]["number"];
	$folder = "pr-" . $prnumber;
	$log = " >> '" . $logdir . $time . "-result.txt' 2>&1 ";

	$command = ("cd " . $path . $log
		. " && rm -rf " . $folder . $log
		. " && git clone " . $repo . " " . $folder . $log
		. " && cd " . $folder . $log
		. " && git pull origin pull/" . $prnumber . "/head" . $log
		. " &");

	file_put_contents($logdir . $time . "-command.txt", $command);
	exec($command);
}
if (isset($input["head_commit"])) {
	file_put_contents($logdir . $time . "-commit.json", "");
	$branch = str_replace("refs/heads/", "", $input["ref"]);
	$folder = $branch;
	$log = " >> '" . $logdir . $time . "-result.txt' 2>&1 ";

	$command = ("cd " . $path . $log
		. " && rm -rf " . $folder . $log
		. " && git clone " . $repo . " -b " . $branch . " --single-branch " . $folder . $log
		. " &");

	file_put_contents($logdir . $time . "-command.txt", $command);
	exec($command);
}

echo "Done\n";

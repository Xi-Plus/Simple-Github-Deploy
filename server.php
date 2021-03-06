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

if (isset($input["pull_request"])) {
	if ($input["pull_request"]["state"] == 'closed') {
		echo "pull request close\n";
		file_put_contents($logdir . $time . "-pull-request-close.json", $inputJSON);
		$prnumber = $input["pull_request"]["number"];
		$folder = "pr-" . $prnumber;
		$log = " >> '" . $logdir . $time . "-result.txt' 2>&1 ";

		$command = ("cd " . $path . $log
			. " && rm -rf " . $folder . $log
			. " &");

		file_put_contents($logdir . $time . "-command.txt", $command);
		exec($command);
	} else {
		echo "pull request open\n";
		file_put_contents($logdir . $time . "-pull-request-open.json", $inputJSON);
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
} else if (isset($input["head_commit"])) {
	echo "commit\n";
	file_put_contents($logdir . $time . "-commit.json", $inputJSON);
	$branch = str_replace("refs/heads/", "", $input["ref"]);
	$folder = $branch;
	$log = " >> '" . $logdir . $time . "-result.txt' 2>&1 ";

	$command = ("cd " . $path . $log
		. " && rm -rf " . $folder . $log
		. " && git clone " . $repo . " -b " . $branch . " --single-branch " . $folder . $log
		. " &");

	file_put_contents($logdir . $time . "-command.txt", $command);
	exec($command);
} else if (isset($input["deleted"]) && $input["deleted"] && isset($input["ref"])) {
	echo "delete branch\n";
	file_put_contents($logdir . $time . "-delete-branch.json", $inputJSON);
	$branch = str_replace("refs/heads/", "", $input["ref"]);
	$folder = $branch;
	$log = " >> '" . $logdir . $time . "-result.txt' 2>&1 ";

	$command = ("cd " . $path . $log
		. " && rm -rf " . $folder . $log
		. " &");

	file_put_contents($logdir . $time . "-command.txt", $command);
	exec($command);
} else {
	echo "nothing to do\n";
	file_put_contents($logdir . $time . "-unknown.json", $inputJSON);
}

echo "Done\n";

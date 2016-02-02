<?php

// TODO: Add support for custom messages

function git($command, &$output = null) {
	exec('git '.$command, $output, $exitCode);
	return $exitCode;
}

if ( ! isset($argv[1])) {
	echo 'Specify a revision or branch';
	exit(1);
}

$revision = $argv[1];
$remoteRevision = $revision;

$file = file_get_contents('repo.txt');
$lines = preg_split('/\r\n|\r|\n/', $file);
$repositories = [];
foreach ($lines as $line) {
	list($identifier, $url, $branch) = explode(' ', $line);
	$repositories[] = [
		'identifier' => $identifier,
		'url' => $url,
		'branch' => $branch,
	];
}

foreach ($repositories as $repository) {
	echo 'Adding remote '.$repository['identifier'].PHP_EOL;
	git('remote add '.$repository['identifier'].' '.$repository['url']);
}

echo 'Fetching all repositories'.PHP_EOL;
git('fetch --all --no-tags');

foreach ($repositories as $repository) {
	$branch = $repository['identifier'].'/'.$repository['branch'];
	$remoteBranch = 'remotes/'.$branch;

	echo 'Checking out repository '.$repository['identifier'].PHP_EOL;
	git('reset --hard');
	git('checkout -b '.$branch.' '.$remoteBranch);
	git('checkout '.$branch);

	echo 'Resetting repository '.$repository['identifier'].' to HEAD'.PHP_EOL;
	git('reset --hard '.$remoteBranch);

	echo 'Merging & squashing...'.PHP_EOL;
	if (git('merge --squash --strategy=subtree '.$remoteRevision, $output) !== 0) {
		do {
			echo 'There was an issue while applying the merge. Continue (c), Skip (s) or Abort (a)?'.PHP_EOL;
			var_dump($output).PHP_EOL;
			$choice = strtolower(trim(fgets(STDIN)));
		} while ( ! in_array($choice, ['c', 'a', 'r']));

		switch ($choice) {
			case 'c':
			case 's':
				break;
			case 'a':
				exit(1);
				break;
		}
	} else {
		do {
			echo 'Please commit the changes and Continue (c).'.PHP_EOL;
		} while (strtolower(trim(fgets(STDIN))) !== 'c');
	}
	echo PHP_EOL;
}

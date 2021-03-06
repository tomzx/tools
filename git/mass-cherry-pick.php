<?php

/**
 * Apply a list of revisions to a list of repositories
 * Copy the repository that contains the changes you want to cherry pick.
 * To this repository, each repository you want to apply the changes will
 * be added as a remote, fetched and then checked out.
 * Finally, the cherry picking will occur.
 *
 * Getting started:
 * Create a file called repo.txt in the git repository that contains
 * the revisions to be cherry-picked. This file should contain
 * the following (separated by a space)
 * identifier url branch
 * identifier: a name representing the remote
 * url: url to the repository to clone and apply the cherry picks
 * branch: the remote branch on which to apply the cherry picks
 * ex: tomzx/my-repo https://github.com/tomzx/my-repo.git master
 */

function git($command, &$output = null) {
	exec('git '.$command, $output, $exitCode);
	return $exitCode;
}

if ( ! isset($argv[1])) {
	echo 'Specify a revision (or list of revision, separated by a comma)';
	exit(1);
}

$revisions = explode(',', $argv[1]);

echo 'Cherry picking revisions:'.PHP_EOL;
foreach ($revisions as $revision) {
	echo "\t$revision".PHP_EOL;
}

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

	echo 'Apply revisions...'.PHP_EOL;
	foreach ($revisions as $revision) {
		echo $revision.PHP_EOL;
		if (git('cherry-pick '.$revision, $output) !== 0) {
			do {
				echo 'There was an issue while applying the cherry-pick. Continue (c), Skip (s) or Abort (a)?'.PHP_EOL;
				var_dump($output).PHP_EOL;
				$choice = strtolower(trim(fgets(STDIN)));
			} while ( ! in_array($choice, ['c', 'a', 'r']));

			switch ($choice) {
				case 'c':
					break;
				case 's':
					git('cherry-pick --abort');
				case 'a':
					exit(1);
					break;
			}
		}
	}
	echo PHP_EOL;
}
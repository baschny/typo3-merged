<?php
// configuration for TYPO3 system extensions:
// - extbase
// - fluid

$gitRoot = '/www/shared/TYPO3ext/';
$gitRootIsWorkingCopy = TRUE;
$htmlFile = 'extbase.html';

$reviewLinkPattern = "https://review.typo3.org/#/q/tr:%s,n,z";

$issueMapping = array(
);

$projectsToCheck = array(
	'TYPO3 Extbase' => array(
		'gitWebUrl' => 'http://git.typo3.org/TYPO3v4/CoreProjects/MVC/extbase.git',
		'releases' => array(
			# project, starting point, branch, working copy path
			array('1.3', 'refs/tags/1.3.0', 'origin/extbase_1-3', 'extbase/extbase_1-3'),
			array('4.7', 'refs/tags/1.3.0', 'origin/extbase_4-7', 'extbase/extbase_4-7'),
			array('6.0', 'refs/tags/1.3.0', 'origin/extbase_6-0', 'extbase/extbase_6-0'),
			array('6.1', 'refs/tags/1.3.0', 'origin/extbase_6-1', 'extbase/extbase_6-1'),
			array('6.2', 'refs/tags/1.3.0', 'origin/master', 'extbase/master'),
		),
		'mapBranchReleaseFunction' => 'mapBranchReleaseExtbase',
		'ignoreList' => array(
			'extbase_1-3' => array(
		 		'42195' => 'Decided to not backport this to older branches',
		 		'36008' => 'Decided to not backport',
		 		'35791' => 'The unit-tests are not available in 4.5',
		 		'39876' => 'Discussed in the team to remove this one in'
			),
			'extbase_4-7' => array(
		 		'42195' => 'Decided to not backport this to older branches',
		 		'46042' => 'Decided to not backport this to 4.7'
			),
			'extbase_6-0' => array(
				'6944' => 'Feature only merged for 6.1',
				'43928' => 'Not needed in 6.0, because shouldMap() always returns TRUE',
				'44806' => 'Not needed in 6.0, because of a small merging mistake in 6.1',
				'42724' => 'Merged under another ticket for 6.0'
			),
		),
	),
	'TYPO3 Fluid' => array(
		'gitWebUrl' => 'http://git.typo3.org/TYPO3v4/CoreProjects/MVC/fluid.git',
		'releases' => array(
			# project, starting point, branch, working copy path
			array('1.3', 'refs/tags/1.3.0', 'origin/fluid_1-3', 'fluid/fluid_1-3'),
			array('4.7', 'refs/tags/1.3.0', 'origin/fluid_4-7', 'fluid/fluid_4-7'),
			array('6.0', 'refs/tags/1.3.0', 'origin/fluid_6-0', 'fluid/fluid_6-0'),
			array('6.1', 'refs/tags/1.3.0', 'origin/fluid_6-1', 'fluid/fluid_6-1'),
			array('6.2', 'refs/tags/1.3.0', 'origin/master', 'fluid/master'),
		),
		'mapBranchReleaseFunction' => 'mapBranchReleaseExtbase',
	),
);

/**
 * Callback to detect if this commit is a "release" commit
 *
 * @param $commitInfos array The infos from the commit
 * @return mixed FALSE|string The released version name(s)
 */
function getDetectedReleaseCommitCallback($commitInfos) {
	if (preg_match('/TYPO3_([0-9-]{5}(?:-?(alpha|beta|rc)[0-9]+)?)/', $commitInfos['tags'], $matches)) {
		return $matches[1];
	}
	return NULL;
}

/**
 * Maps a branch name to a "release" version (as known from tags)
 *
 * @param $branch
 * @return string
 */
function mapBranchReleaseExtbase($branch) {
	switch ($branch) {
		case '1.3':
			return '4.5';
		case '1.4':
			return '4.6';
		default:
			return $branch;
	}
}

?>

<?php
// configuration for TYPO3 system extensions:
// - version
// - workspaces

$gitRoot = '/www/shared/TYPO3ext/';
$gitRootIsWorkingCopy = TRUE;
$htmlFile = 'workspaces.html';

$reviewLinkPattern = "https://review.typo3.org/#/q/tr:%s,n,z";

$issueMapping = array(
);

$projectsToCheck = array(
	'TYPO3 Version' => array(
		'gitWebUrl' => 'http://git.typo3.org/TYPO3v4/CoreProjects/workspaces/version.git',
		'releases' => array(
			# project, starting point, branch, working copy path
			array('4.5', 'refs/tags/4.5.0', 'origin/4.5', 'version/4.5'),
			array('4.7', 'refs/tags/4.5.0', 'origin/version_4-7', 'version/version_4-7'),
			array('6.0', 'refs/tags/4.5.0', 'origin/version_6-0', 'version/version_6-0'),
			array('6.1', 'refs/tags/4.5.0', 'origin/version_6-1', 'version/version_6-1'),
			array('6.2', 'refs/tags/4.5.0', 'origin/master', 'version/master'),
		),
		'ignoreList' => array(
			'4.5' => array(
				'43088' => 'Is already merged with #25434',
			),
			'version_6-0' => array(
				'43088' => 'Is already merged with #25434',
			),
		),
	),
	'TYPO3 Workspaces' => array(
		'gitWebUrl' => 'http://git.typo3.org/TYPO3v4/CoreProjects/workspaces/workspaces.git',
		'releases' => array(
			# project, starting point, branch, working copy path
			array('4.5', 'refs/tags/4.5.0', 'origin/4.5', 'workspaces/4.5'),
			array('4.7', 'refs/tags/4.5.0', 'origin/workspaces_4-7', 'workspaces/workspaces_4-7'),
			array('6.0', 'refs/tags/4.5.0', 'origin/workspaces_6-0', 'workspaces/workspaces_6-0'),
			array('6.1', 'refs/tags/4.5.0', 'origin/workspaces_6-1', 'workspaces/workspaces_6-1'),
			array('6.2', 'refs/tags/4.5.0', 'origin/master', 'workspaces/master'),
		),
		'ignoreList' => array(
			'4.5' => array(
				'32476' => 'Abandoned for 4.5, it is a feature of 4.7',
				'26287' => 'Is a feature for 4.6 and newer.',
			),
		),
	),
);

/**
 * Callback to detect if this commit is a "release" commit
 *
 * @param $commitInfos array The infos from the commit
 * @return mixed FALSE|string The released version name
 */
function getDetectedReleaseCommitCallback($commitInfos) {
	if (preg_match('/TYPO3_([0-9-]{5}(?:-?(alpha|beta|rc)[0-9]+)?)/', $commitInfos['tags'], $matches)) {
		return $matches[1];
	}
	return NULL;
}

?>

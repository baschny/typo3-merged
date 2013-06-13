<?php
// configuration for TYPO3 system extensions:
// - linkvalidator

$gitRoot = '/www/shared/TYPO3ext/';
$gitRootIsWorkingCopy = TRUE;
$htmlFile = 'linkvalidator.html';

$reviewLinkPattern = "https://review.typo3.org/#/q/tr:%s,n,z";

$issueMapping = array(
);

$projectsToCheck = array(
	'TYPO3 Linkvalidator' => array(
		'gitWebUrl' => 'http://git.typo3.org/TYPO3v4/Extensions/linkvalidator.git',
		'releases' => array(
			# project, starting point, branch, working copy path
			array('4.5', 'refs/tags/4.5', 'origin/TYPO3_4-5', 'linkvalidator/TYPO3_4-5'),
			array('4.7', 'refs/tags/4.5', 'origin/linkvalidator_4-7', 'linkvalidator/linkvalidator_4-7'),
			array('6.0', 'refs/tags/4.5', 'origin/linkvalidator_6-0', 'linkvalidator/linkvalidator_6-0'),
			array('6.1', 'refs/tags/4.5', 'origin/linkvalidator_6-1', 'linkvalidator/linkvalidator_6-1'),
			array('6.2', 'refs/tags/4.5', 'origin/master', 'linkvalidator/master'),
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

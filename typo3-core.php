<?php
// configuration for TYPO3 core

$gitRoot = getcwd() . '/data/TYPO3core/';
$gitRootIsWorkingCopy = TRUE;
$htmlFile = 'core.html';

$reviewLinkPattern = 'https://review.typo3.org/#/q/tr:%s,n,z';

$issueMapping = array(
#	'#34838' => '#6893',
);

$projectsToCheck = array(
	'TYPO3 CMS Core' => array(
		'gitWebUrl' => 'https://git.typo3.org/Packages/TYPO3.CMS.git',
		'perReleaseOutput' => 'core-%s.html',
		'extractComponentNameFromPathByBranchCallback' => 'extractComponentNameFromPathByBranchCMS',
		'releases' => array(
			# project, starting point, branch, working copy path
			array('7.6', 'refs/tags/TYPO3_7-6-0', 'origin/TYPO3_7-6', 'TYPO3_7-6'),
			array('8.7', 'refs/tags/TYPO3_7-6-0', 'origin/TYPO3_8-7', 'TYPO3_8-7'),
			array('master', 'refs/tags/TYPO3_7-6-0', 'origin/master', 'master'),
		),
		// list of issues to be ignored as TODOs from a certain branch.
		// Used to shorten the list of issues that are marked "TODO"
		// if e.g. the originally advertised backport (in the commit
		// message on the master branch) is not wanted anymore.
		// This is similar to an "ABANDONED"-state, but not for a
		// changeset, but instead for a whole issue+branch combination.
		'ignoreList' => array(
			'TYPO3_7-6' => array(
				'71094' => 'Merged in #47070 with a missing "Resolves" line',
				'76376' => 'Not relevant for 7.6 (found out after merging #48404)',
				'76642' => 'Not relevant for 7.6, see comment in Gerrit #48585',
				'77248' => 'Already fixed with #49185',
				'77676' => 'Will not be backported, see comment in Gerrit #49628',
				'78900' => 'Not relevant for 7.6, see comment in Gerrit #50931',
				'79006' => 'Not relevant for 7.6, see comment in Gerrit #50977',
				'79406' => 'Not relevant for 7.6 (found out after merging #51379)',
				'79705' => 'Feature not available on 7.6, see comment in Gerrit #51605',
				'79974' => 'Will not be backported, see comment in Gerrit #51808',
				'80014' => 'Not relevant for 7.6, see comment in Gerrit #51844',
				'82103' => 'Will not be backported, see comment in Gerrit #53707',
				'82279' => 'Not relevant for 7.6, see comment in Gerrit #53873',
			),
			'TYPO3_8-7' => array(
				'81097' => 'Merged in #52725 with a missing "Resolves" line',
				'81200' => 'Abandoned for 8.7, see comment in Gerrit #53194',
				'81654' => 'Feature will not be backported, see comment in Gerrit #53738',
				'80763' => 'Not relevant for 8.7, see comment in Gerrit #50803',
				'82325' => 'Not relevant for 8.7, see comment in Gerrit #53940',
			),
			'master' => array(
			)
		)
	)
);

/**
 * Callback to detect if this commit is a "release" commit
 *
 * @param array $commitInfos array The infos from the commit
 * @return mixed FALSE|string The released version name
 */
function getDetectedReleaseCommitCallback($commitInfos) {
	if (preg_match('/Release of TYPO3 (.*)/', $commitInfos['subject'], $matches)) {
		return $matches[1];
	}
	return NULL;
}

/**
 * Callback to return a "component name" for a path for a particular branch
 *
 * @param string $release
 * @param string $path
 * @return string
 */
function extractComponentNameFromPathByBranchCMS($release, $path) {
	if (($release === '7.6' || $release === '8.7') && preg_match('#^typo3/sysext/(.*?)/#', $path, $matches)) {
		$component = $matches[1];
		if ($component === 'extensionmanager') {
			// Shortify
			$component = 'em';
		} elseif (in_array($component, array('core', 'backend'), true) && preg_match('#^typo3/sysext/.*?/Classes/(.*?)/#', $path, $matches)) {
			$component = $component . ':' . $matches[1];
		}
		return $component;
	}
	return '';
}

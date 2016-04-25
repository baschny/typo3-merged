<?php
// configuration for TYPO3 core

$gitRoot = getcwd() . '/data/TYPO3core/';
$gitRootIsWorkingCopy = TRUE;
$htmlFile = 'core.html';

$reviewLinkPattern = 'https://review.typo3.org/#/q/tr:%s,n,z';

$issueMapping = array(
	'#M16726' => '#24324',
	'#M17868' => '#25258',
	'#M17924' => '#25305',
	'#M18051' => '#25406',
	'#M17916' => '#25301',
	'#24440' => '#23355',
	'#24410' => '#23355',
	'#23496' => '#23355',
	'#23860' => '#23355',
	'#25006' => '#33853',
	'#47782' => '#47211',
	'#48027' => '#32387',
	'#49461' => '#44983',
	'#34838' => '#6893',
);

$projectsToCheck = array(
	'TYPO3 CMS Core' => array(
		'gitWebUrl' => 'http://git.typo3.org/Packages/TYPO3.CMS.git',
		'perReleaseOutput' => 'core-%s.html',
		'extractComponentNameFromPathByBranchCallback' => 'extractComponentNameFromPathByBranchCMS',
		'releases' => array(
			# project, starting point, branch, working copy path
			array('6.2', 'refs/tags/TYPO3_4-5-0', 'origin/TYPO3_6-2', 'TYPO3_6-2'),
			array('7.6', 'refs/tags/TYPO3_4-5-0', 'origin/TYPO3_7-6', 'TYPO3_7-6'),
			array('master', 'refs/tags/TYPO3_4-5-0', 'origin/master', 'master'),
		),
		// list of issues to be ignored as TODOs from a certain branch.
		// Used to shorten the list of issues that are marked "TODO"
		// if e.g. the originally advertised backport (in the commit
		// message on the master branch) is not wanted anymore.
		// This is similar to an "ABANDONED"-state, but not for a
		// changeset, but instead for a whole issue+branch combination.
		'ignoreList' => array(
			'TYPO3_6-2' => array(
				'24556' => 'Abandoned for 6.2, see comment in Gerrit #44714',
				'34728' => 'Reverted backport. Is already merged with #37167',
				'35093' => 'Reverted backport. Is already merged with #37167',
				'53928' => 'Abandoned for 6.2, see comment in Gerrit #41642',
				'54410' => 'Will not be backported to 6.2, see comment in Gerrit #35750',
				'54730' => 'Abandoned for 6.2, see comment in Gerrit',
				'56426' => 'Was intended for master only.',
				'57379' => 'Abandoned for 6.2, see comment in Gerrit #38515',
				'59255' => 'Original patch was reverted.',
				'59947' => 'Abandoned for 6.2, see comment in Gerrit',
				'61812' => 'Abandoned for 6.2, see comment in Gerrit #44714',
				'63275' => 'Will not be backported to 6.2, see comment in Gerrit',
				'63648' => 'Abandoned for 6.2, see comment in Gerrit',
				'64618' => 'Patch is not relevant for 6.2 (found out after merging)',
				'64883' => 'Abandoned for 6.2, see comment in Gerrit #36674',
				'66312' => 'Abandoned for 6.2, see comment in Gerrit #44714',
				'66167' => 'No backport possible, code not present in 6.2',
				'66729' => 'No backport needed as the feature (Gerrit #30972) wasn\'t backported either.',
				'66895' => 'Abandoned for 6.2, see comment in Gerrit #39463',
				'67274' => 'Will not be backported to 6.2, see comment in Gerrit #40056',
				'69368' => 'Patch is not relevant for 6.2 (found out after merging #42891)',
				'71126' => 'Abandoned for 6.2, see comment in Gerrit #44232'
			)
		),
	),
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
	if (($release === '6.2' || $release === '7.6') && preg_match('#^typo3/sysext/(.*?)/#', $path, $matches)) {
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

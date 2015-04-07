<?php
// configuration for TYPO3 core

$gitRoot = getcwd() . '/data/TYPO3core/';
$gitRootIsWorkingCopy = TRUE;
$htmlFile = 'core.html';

$reviewLinkPattern = "https://review.typo3.org/#/q/tr:%s,n,z";

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
				array('4.5', 'refs/tags/TYPO3_4-5-0', 'origin/TYPO3_4-5', 'TYPO3_4-5'),
				array('6.2', 'refs/tags/TYPO3_4-5-0', 'origin/TYPO3_6-2', 'TYPO3_6-2'),
				array('7.0', 'refs/tags/TYPO3_4-5-0', 'origin/master', 'TYPO3_7-0'),
		),
		// list of issues to be ignored as TODOs from a certain branch.
		// Used to shorten the list of issues that are marked "TODO"
		// if e.g. the originally advertised backport (in the commit
		// message on the master branch) is not wanted anymore.
		// This is similar to an "ABANDONED"-state, but not for a
		// changeset, but instead for a whole issue+branch combination.
		'ignoreList' => array(
			'TYPO3_4-5' => array(
				'12664' => 'Abandoned for 4.5, it is a feature of 4.6',
				'21481' => 'This is already merged to 6.x versions. Only major changes go to 4.x, so 4.x is not getting this.',
				'26141' => 'Stanislas Rolland: The issue does not arise in releases 4.7 and 4.5.',
				'26287' => 'Is a feature for 4.6 and newer.',
				'28344' => 'Abandoned for 4.5, it is a feature of 4.6',
				'28523' => 'Abandoned for 4.5, it is a feature of 4.6',
				'28568' => 'Abandoned for 4.5, it is a feature of 4.6',
				'28594' => 'Backporting to 4.5 not needed, see comment in Gerrit.',
				'28616' => 'Backporting to 4.5 not desired, see comment in Gerrit.',
				'28794' => 'Does not apply to 4.5, Bug was introduced with accessibility refactoring',
				'28970' => 'Looks like the change from #28639 was not merged in 4.5.x, so the fix of the change doesn\'t need to be backported to 4.5.x, too.',
				'30244' => 'Will not be backported to 4.5 and 6.0',
				'30311' => 'IDNA converter is not in 4.5, adding would count as feature, will not be backported, see comment in Gerrit',
				'31184' => 'Does not apply to 4.5, no popup windows used in EM of 4.5',
				'31188' => 'Does not apply to 4.5, the code is already as it should be, WTF?',
				'31943' => 'Seems to not apply on 4.5, see comment in Gerrit.',
				'32109' => 'Does not apply to 4.5, see comment in Gerrit',
				'32476' => 'Abandoned for 4.5, it is a feature of 4.7',
				'32938' => 'Does not apply to 4.5, see comment in Gerrit.',
				'33697' => 'Not needed for 4.5 anymore, see comments in Gerrit',
				'33813' => 'Will not be backported to 4.5 anymore, see comment in Gerrit',
				'33853' => 'Will not be backported to 4.5, see comment in Gerrit',
				'33866' => 'Does not apply to 4.5 as it tries to correct code introduced into 4.6+ in #29774',
				'33895' => 'Does not apply to 4.5 as as there is no cache_phpcode in TYPO3_4-5, see comment in Gerrit',
				'34012' => 'Slider was introduced with 4.6 so this does not apply for 4.5, see comment in Gerrit',
				'34396' => 'Does not apply to current 4.5.x, change is already applied, WTF?',
				'34627' => 'Does not apply since IDNA converter is not in 4.5',
				'34698' => 'Was included into another patch (#30892), applied in 09ef6f690e7b9d9cedc6c85ac0d88009697ae79a',
				'35126' => 'Does not apply since this feature was introduced first in 4.6 (see #29586), applied in 913402a560d0778643b5dc6823619325e3eed360',
				'35296' => 'Affects tx_form which was introduced in 4.6, does not apply to 4.5, see comment in Gerrit',
				'35787' => 'tx_form was not part of 4.5, see comment in Gerrit',
				'35791' => 'The unit-tests are not available in 4.5',
				'36008' => 'Decided to not backport',
				'36937' => 'Does not apply to 4.5, see comment in Gerrit.',
				'39356' => 'Will not be backported to 4.5 and 4.6, see comment in Gerrit',
				'39662' => 'Stanislas Rolland: The issue does not arise in releases 4.6 and 4.5.',
				'39876' => 'Discussed in the team to remove this one in',
				'40409' => 'Will not be backported to 4.5, see comment in Gerrit',
				'41120' => 'Will not be backported to 4.5 and 4.6, see comment in Gerrit',
				'41344' => 'Will not be backported to 4.5, see comment in Gerrit',
				'42195' => 'Decided to not backport this to older branches',
				'43735' => 'Will not be backported to 4.5, see comment in Gerrit',
				'43088' => 'Is already merged with #25434',
				'43540' => 'Was reverted on 6.1',
				'61943' => 'Original patch was reverted, followup not for 4.5, see comment in Gerrit #33020',
			),
			'TYPO3_6-2' => array(
				'34728' => 'Reverted backport. Is already merged with #37167',
				'35093' => 'Reverted backport. Is already merged with #37167',
				'54410' => 'Will not be backported to 6.2, see comment in Gerrit #35750',
				'54730' => 'Abandoned for 6.2, see comment in Gerrit',
				'56426' => 'Was intended for master only.',
				'59255' => 'Original patch was reverted.',
				'59947' => 'Abandoned for 6.2, see comment in Gerrit',
				'63275' => 'Will not be backported to 6.2, see comment in Gerrit',
				'63648' => 'Abandoned for 6.2, see comment in Gerrit',
				'64618' => 'Patch is not relevant for 6.2 (found out after merging)',
				'66167' => 'No backport possible, code not present in 6.2'
			)
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
	if (preg_match('/Release of TYPO3 (.*)/', $commitInfos['subject'], $matches)) {
		return $matches[1];
	}
	return NULL;
}

/**
 * Callback to return a "component name" for a path for a particular branch
 *
 * @param $branch
 * @param $path
 */
function extractComponentNameFromPathByBranchCMS($release, $path) {
	if (($release == '6.2' || $release == '7.0') && preg_match('#^typo3/sysext/(.*?)/#', $path, $matches)) {
		$component = $matches[1];
		if ($component == 'extensionmanager') {
			// Shortify
			$component = 'em';
		} else if (in_array($component, array('core', 'backend')) && preg_match('#^typo3/sysext/.*?/Classes/(.*?)/#', $path, $matches)) {
			$component = $component . ':' . $matches[1];
		}
		return $component;
	}
	return '';
}

?>

<?php
// configuration for TYPO3 core

$gitRoot = '/www/shared/TYPO3core/';
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
);

$projectsToCheck = array(
	'TYPO3 CMS Core' => array(
		'gitWebUrl' => 'http://git.typo3.org/TYPO3v4/Core.git',
		'releases' => array(
				# project, starting point, branch, working copy path
			#	array('4.2', 'refs/tags/TYPO3_4-2-0', 'origin/TYPO3_4-2', 'TYPO3_4-2'),
			#	array('4.3', 'refs/tags/TYPO3_4-3-0', 'origin/TYPO3_4-3', 'TYPO3_4-3'),
			#	array('4.4', 'refs/tags/TYPO3_4-4-0', 'origin/TYPO3_4-4', 'TYPO3_4-4'),
				array('4.5', 'refs/tags/TYPO3_4-5-0', 'origin/TYPO3_4-5', 'TYPO3_4-5'),
			#	array('4.5-BP', 'refs/tags/TYPO3_4-5-0', 'backports/TYPO3_4-5', 'TYPO3_4-5_backports'),
				array('4.6', 'refs/tags/TYPO3_4-5-0', 'origin/TYPO3_4-6', 'TYPO3_4-6'),
			#	array('4.6-BP', 'refs/tags/TYPO3_4-5-0', 'backports/TYPO3_4-6', 'TYPO3_4-6_backports'),
				array('4.7', 'refs/tags/TYPO3_4-5-0', 'origin/TYPO3_4-7', 'TYPO3_4-7'),
				array('6.0', 'refs/tags/TYPO3_4-5-0', 'origin/TYPO3_6-0', 'TYPO3_6-0'),
				array('6.1', 'refs/tags/TYPO3_4-5-0', 'origin/master', 'TYPO3_6-1'),
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
				'28344' => 'Abandoned for 4.5, it is a feature of 4.6',
				'28523' => 'Abandoned for 4.5, it is a feature of 4.6',
				'28568' => 'Abandoned for 4.5, it is a feature of 4.6',
				'28594' => 'Backporting to 4.5 not needed, see comment in Gerrit.',
				'28616' => 'Backporting to 4.5 not desired, see comment in Gerrit.',
				'30311' => 'IDNA converter is not in 4.5, adding would count as feature, will not be backported, see comment in Gerrit',
				'30311' => 'IDNA converter is not in 4.5, adding would count as feature, will not be backported, see comment in Gerrit',
				'31188' => 'Does not apply to 4.5, the code is already as it should be, WTF?',
				'31943' => 'Seems to not apply on 4.5, see comment in Gerrit.',
				'32938' => 'Does not apply to 4.5, see comment in Gerrit.',
				'33853' => 'Will not be backported to 4.5, see comment in Gerrit',
				'33866' => 'Does not apply to 4.5 as it tries to correct code introduced into 4.6+ in #29774',
				'33895' => 'Does not apply to 4.5 as as there is no cache_phpcode in TYPO3_4-5, see comment in Gerrit',
				'34012' => 'Slider was introduced with 4.6 so this does not apply for 4.5, see comment in Gerrit',
				'34396' => 'Does not apply to current 4.5.x, change is already applied, WTF?',
				'34627' => 'Does not apply since IDNA converter is not in 4.5',
				'34698' => 'Was included into another patch (#30892), applied in 09ef6f690e7b9d9cedc6c85ac0d88009697ae79a',
				'35126' => 'Does not apply since this feature was introduced first in 4.6 (see #29586), applied in 913402a560d0778643b5dc6823619325e3eed360',
				'35296' => 'Affects tx_form which was introduced in 4.6, does not apply to 4.5, see comment in Gerrit',
				'36937' => 'Does not apply to 4.5, see comment in Gerrit.',
				'32109' => 'Does not apply to 4.5, see comment in Gerrit',
				'35787' => 'tx_form was not part of 4.5, see comment in Gerrit',
				'39662' => 'Stanislas Rolland: The issue does not arise in releases 4.6 and 4.5.',
			),
			'TYPO3_4-6' => array(
				'33853' => 'Abandoned for 4.6 as it does not apply cleanly, see comment in Gerrit.',
				'34396' => 'Does not apply to current 4.6.x, change is already applied, WTF?',
				'34627' => 'Does not apply since IDNA converter is not in 4.5',
				'36937' => 'Does not apply to 4.6, see comment in Gerrit.',
				'33165' => 'No change needed here, as authUser is only called when a login is triggered',
				'34601' => 'Does not apply, hook does not exists in 4.6',
				'33234' => 'Abandoned for 4.6, see comment in Gerrit.',
				'39662' => 'Stanislas Rolland: The issue does not arise in releases 4.6 and 4.5.',
			),
			'TYPO3_4-7' => array(
				'30969' => 'Since 4.7 the about module is build in ExtBase. This patch does not apply.',
				'10307' => 'Cannot go to 4.7 anymore.',
				'33749' => 'This feature is postponed to 6.0.',
				'34363' => 'Feature was too late to be merged in 4.7, merged in 6.0',
			),
			'TYPO3_6-0' => array(
				'30969' => 'Since 4.7 the about module is build in ExtBase. This patch does not apply.',
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
	if (preg_match('/Release of TYPO3 (.*)/', $commitInfos['subject'], $matches)) {
		return $matches[1];
	}
	return NULL;
}


?>

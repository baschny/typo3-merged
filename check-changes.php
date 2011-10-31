#!/usr/bin/php -q
<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Ernesto Baschny (ernst@cron-it.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This scripts checks which commits were merged into TYPO3 core git
 * and merges the information per-release into one big table.
 *
 * Requires the "git" command line tool.
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 *
 */

// Configuration

$releasesToCheck = array(
#	array('4.2', 'TYPO3_4-2-0', 'TYPO3_4-2', '/www/shared/TYPO3core/TYPO3_4-2/'),
#	array('4.3', 'TYPO3_4-3-0', 'TYPO3_4-3', '/www/shared/TYPO3core/TYPO3_4-3/'),
	array('4.4', 'TYPO3_4-4-0', 'TYPO3_4-4', '/www/shared/TYPO3core/TYPO3_4-4/'),
	array('4.5', 'TYPO3_4-5-0', 'TYPO3_4-5', '/www/shared/TYPO3core/TYPO3_4-5/'),
	array('4.6', 'TYPO3_4-5-0', 'TYPO3_4-6', '/www/shared/TYPO3core/TYPO3_4-6/'),
	array('4.7', 'TYPO3_4-5-0', 'master', '/www/shared/TYPO3core/TYPO3_4-7/'),
);
$gitRoot = '/www/shared/TYPO3core/';
$htmlFile = '/home/ernst/TYPO3-Release/index.html';

#$releasesToCheck = array(
#	array('1.0', '1.0.0', 'extbase_1-0'),
#	array('1.1', '1.1.0', 'extbase_1-1'),
#	array('1.2', '1.2.0', 'extbase_1-2'),
##	array('1.3', '1.3.0', 'extbase_1-3'),
#	array('1.4', '1.3.0', 'master'),
#);
#$gitRoot = '/www/shared/TYPO3ext/extbase';
#$htmlFile = '/home/ernst/TYPO3-Release/extbase.html';

// %s = 4-5-0 (first release)
// %s = 4-5 (current state)
$cmdGitLog = 'git log refs/tags/%s..origin/%s --submodule --pretty=format:hash:%%h%%x01date:%%cd%%x01subject:%%s%%x01body:%%b%%x0a--COMMIT-- --date=iso';

// Fetch per-release information

$commits = array();
chdir($gitRoot);
foreach ($releasesToCheck as $releaseRange) {
	$branch = $releaseRange[2];
	$tagPrefix = 'TYPO3_(' . str_replace('.', '-', $releaseRange[0]) . '.*)';
	$dir = $releaseRange[3];
	#echo "dir=$dir\n";
	chdir($dir);
	exec('git pull --all -t');
	exec('git submodule update --init');

	$lastHash[$branch] = '';
	$gitLogCmd = sprintf($cmdGitLog, $releaseRange[1], $branch);
	$output = '';
	exec($gitLogCmd, $output);
	$currentField = '';
	$commitInfos = array();
	$inRelease = '';
	foreach ($output as $line) {
		if ($line == '--COMMIT--') {
			// Last line
			foreach (explode("\n", $commitInfos['body']) as $bodyLine) {
				$bodyInfo = explode(':', $bodyLine, 2);
				switch (trim($bodyInfo[0])) {
					case 'Resolves':
					case 'Fixes':
						$issues = explode(',', $bodyInfo[1]);
						foreach ($issues as $issue) {
							$commitInfos['issues'][] = trim($issue);
						}
						break;
					case 'Reviewed-on':
						break;
					case 'Reviewed-by':
						break;
					case 'Tested-by':
						break;
					case 'Releases':
					case 'Release':
						foreach (explode(',', $bodyInfo[1]) as $release) {
							if (trim($release) != '') {
								$commitInfos['releases'][] = trim($release);
							}
						}
						break;
				}
			}
			if (preg_match('/Resolves (#\d+)/', $commitInfos['subject'], $match)) {
				$commitInfos['issues'][] = trim($match[1]);
			}
			if (!$lastHash[$branch]) {
				// Remember last hash of this branch
				$lastHash[$branch] = $commitInfos['hash'];
			}
			if (preg_match('/Release of TYPO3 (.*)/', $commitInfos['subject'], $matches)) {
				$inRelease = $matches[1];
			}
			$commitInfos['inRelease'] = ( $inRelease ? $inRelease : 'next' );
			$commits[$branch][] = $commitInfos;
			$commitInfos = array();
			continue;
		}
		$infos = explode("\x01", $line);
		if (count($infos) == 1) {
			// Continuation line
			$commitInfos[$currentField] .= "\n";
			$commitInfos[$currentField] .= $line;
		} else {
			// Multiple fields:
			foreach ($infos as $info) {
				list ($field, $value) = explode(":", $info, 2);
				$commitInfos[$field] = $value;
				$currentField = $field;
			}
		}
	}
}

// Merge information from the different releases into one array
$issueInfo = array();
foreach ($commits as $branch => $commitInfos) {
	foreach ($commitInfos as $commit) {
		if (!isset($commit['issues'])) {
			continue;
		}
		foreach ($commit['issues'] as $issue) {
			$thisDate = strtotime($commit['date']);
			if (isset($issueInfo[$issue]) && $issueInfo[$issue]['lastUpdate']) {
				if ($thisDate > $issueInfo[$issue]['lastUpdate'])  {
					$issueInfo[$issue]['lastUpdate'] = $thisDate;
				}
			} else {
				$issueInfo[$issue]['lastUpdate'] = $thisDate;
			}
			$issueInfo[$issue]['solved'][$branch] = array(
				'date' => $commit['date'],
				'hash' => $commit['hash'],
				'subject' => $commit['subject'],
				'inRelease' => $commit['inRelease'],
			);
			if (isset($commit['releases'])) {
				foreach ($commit['releases'] as $release) {
					$issueInfo[$issue]['planned'][$release] = TRUE;
				}
			}
		}
	}
}

// Sort the list
function compareIssues($a, $b) {
	$dateA = $a['lastUpdate'];
	$dateB = $b['lastUpdate'];
	if ($a == $b) {
		return 0;
	}
	return ($a > $b) ? -1 : 1;
}
uasort($issueInfo, 'compareIssues');

// Print out the overview

$out = '';
$out .= "<html><head><title>Merged issues in TYPO3 releases</title></head>\n";
$out .= '<link rel="stylesheet" type="text/css" href="styles.css" />';
$out .= "<body>\n";
$out .= "<h1>Merged into TYPO3v4 core, by TYPO3 release</h1>\n";
$out .= "<table>\n";
$out .= "<tr>\n";
$out .= "<th>Release</th>\n";
foreach ($releasesToCheck as $release) {
	$releaseName = $release[0];
	$out .= sprintf('<th class="release">%s</th>', $releaseName);
}
$out .= '<th class="desc">Description</th>';
$out .= "</tr>";

foreach ($issueInfo as $issueNumber => $issueData) {
	$out .= "<tr>";
	$issueLink = '';
	if (preg_match('/^#(\d+)/', $issueNumber, $match)) {
		$issueLink = sprintf('http://forge.typo3.org/issues/%s', $match[1]);
	} elseif (preg_match('/^#M(\d+)/', $issueNumber, $match)) {
		$issueLink = sprintf('http://bugs.typo3.org/view.php?id=%s', $match[1]);
	}
	$issueNumber = sprintf('<a href="%s" target="_blank">%s</a>', $issueLink, $issueNumber);
	$out .= sprintf('<td>%s</td>', $issueNumber);
	$subject = '';
	foreach ($releasesToCheck as $release) {
		$releaseName = $release[0];
		$releaseBranch = $release[2];
		$class = 'info-none';
		$text = '';
		if (isset($issueData['solved'][$releaseBranch])) {
			$subject = $issueData['solved'][$releaseBranch]['subject'];
			$class = 'info-solved';
			if ($issueData['solved'][$releaseBranch]['inRelease'] == 'next') {
				$versionName = 'for next release';
				$versionTag = 'next';
			} else if (preg_match('/^' . $releaseName . '/', $issueData['solved'][$releaseBranch]['inRelease'])) {
				$versionName = 'for ' . $issueData['solved'][$releaseBranch]['inRelease'];
				$versionTag = $issueData['solved'][$releaseBranch]['inRelease'];
			} else {
				$versionName = sprintf('in previous release (%s)', $issueData['solved'][$releaseBranch]['inRelease']);
				$versionTag = 'previous';
			}
			$releaseName = $issueData['solved'][$releaseBranch]['inRelease'];
			$text = sprintf('<a title="Merged on %s for %s" target="_blank" href="http://git.typo3.org/TYPO3v4/Core.git?a=commit;h=%s" target="_blank">%s</a>',
				$issueData['solved'][$releaseBranch]['date'],
				$versionName,
				$issueData['solved'][$releaseBranch]['hash'],
				$versionTag
			);
		} elseif (isset($issueData['planned']) && isset($issueData['planned'][$releaseName])) {
			$class = 'info-planned';
			$text = sprintf('<span title="Planned for %s, not merged yet">TODO</span>', $releaseName);
		}
		$out .= sprintf('<td class="%s">%s</td>', $class, $text);
	}
	$out .= sprintf('<td class="description">%s</td>', htmlspecialchars($subject));
	$out .= "</tr>\n";
}
$out .= "</table>";

$out .= '<p>Based on these GIT states:</p><ul>';
foreach ($lastHash as $release => $hash) {
	$out .= sprintf('<li>%s: <a target="_blank" href="http://git.typo3.org/TYPO3v4/Core.git?a=commit;h=%s" target="_blank">%s</a>', $release, $hash, $hash);
}
$out .= '</ul>';

$out .= sprintf('<p>Generated on %s by check-changes.php, maintained by <a href="mailto:ernst@cron-it.de">Ernesto Baschny</a>, <a href="http://www.typo3-anbieter.de" target="_blank">cron IT GmbH</a>.</p>', 
	strftime('%c', time())
);

$out .= "</body></html>";

$fh = fopen($htmlFile, 'w');
fwrite($fh, $out);
fclose($fh);

?>
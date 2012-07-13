#!/usr/bin/env php
<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011-2012 Ernesto Baschny (ernst@cron-it.de>
*  (c) 2011-2012 Karsten Dambekalns <karsten@typo3.org>
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This scripts checks which commits were merged into git
 * and merges the information per-release into one big table.
 *
 * Requires the "git" command line tool.
 */

// include configuration
if (isset($argv[1]) && file_exists($argv[1])) {
	require($argv[1]);
} else {
	echo 'Configuration file not found or given.' . PHP_EOL;
	exit(1);
}

// %s = GIT_DIR
// %s = 4-5-0 (first release)
// %s = 4-5 (current state)
$cmdGitLog = 'GIT_DIR="%s" git log %s..%s --submodule --pretty=format:hash:%%h%%x01date:%%cd%%x01tags:%%d%%x01subject:%%s%%x01body:%%b%%x0a--COMMIT-- --date=iso';

// Requires that ssh is allowed to gerrit (private key)
$cmdGerrit = 'ssh review.typo3.org -p 29418 gerrit query --format json status:open project:%s';

/**
 * Return information from gerrit issues
 *
 * @param string $project The gerrit Project name
 * @return array indexed by "branch" and then "issueNumber" containing then an array of open review items
 */
function fetchGerritReviewRequests($project) {
	$cmd = $GLOBALS['cmdGerrit'];
	$lastSortKey = '';
	$gerritFinished = FALSE;
	$issues = array();
	while (! $gerritFinished) {
		if ($lastSortKey) {
			$cmd .= ' resume_sortkey:' . $lastSortKey;
		}
		$cmd = sprintf($cmd, $project);
		$handle = popen($cmd, 'r');
		while (! feof($handle) ) {
			$line = fgets($handle);
			$item = json_decode($line, TRUE);
			if (!$item) {
				continue;
			}
			if (isset($item['type']) && $item['type'] == 'stats') {
				if (intval($item['rowCount']) < 500) {
					// This was the last call
					$gerritFinished = TRUE;
				}
			} else {
				$lastSortKey = $item['sortKey'];
				if (isset($item['topic'])) {
					$issueNumber = $item['topic'];
					if (preg_match('/^issue\/(\d+)/', $issueNumber, $matches)) {
						$issueNumber = $matches[1];
					}
					$issueNumbers = array($issueNumber);
				} else if (isset($item['trackingIds'])) {
					$issueNumbers = array();
					foreach ($item['trackingIds'] as $tracking) {
						if ($tracking['system'] == 'Forge') {
							$issueNumbers[] = $tracking['id'];
						}
					}
				} else {
					// no topic found
					continue;
				}
				$branch = $item['branch'];
				$url = $item['url'];
				foreach ($issueNumbers as $issueNumber) {
					// Collect information in big array
					$issues[$branch][$issueNumber][] = $item;
				}
			}
		}
		fclose($handle);
	}
	return $issues;
}


/**
 * Compare two issue numbers in string format, e.g. #123 and #M567.
 *
 * @param string $a
 * @param string $b
 * @return integer
 */
function compareIssues($a, $b) {
	$dateA = $a['lastUpdate'];
	$dateB = $b['lastUpdate'];
	if ($a == $b) {
		return 0;
	}
	return ($a > $b) ? -1 : 1;
}

$out = '<html><head><title>Merged issues in releases</title><link rel="stylesheet" type="text/css" href="styles.css" /></head>';
$out .= "<body>\n";
$out .= "<h1>Issues merged into releases</h1>\n";

foreach ($projectsToCheck as $project => $projectData) {
	$commits = array();
	$lastHash = array();
	$releasesToCheck = $projectData['releases'];
	echo 'Working on ' . $project . ' now.' . PHP_EOL;
	$gerritIssues = array();
	if (isset($projectData['gerritProject'])) {
		$gerritIssues = fetchGerritReviewRequests($projectData['gerritProject']);
	}
	foreach ($releasesToCheck as $releaseRange) {
		$startCommit = $releaseRange[1];
		$branch = $releaseRange[2];
		$GIT_DIR = $gitRoot . $releaseRange[3] . '/.git';
		if ($gitRootIsWorkingCopy) {
			// We have a working copy per branch in $gitRoot/$branchName
			// which we keep up-to-date (faster updates)
			$oldDir = getcwd();
			chdir($gitRoot . $releaseRange[3]);
		}
		$output = array();
		exec('GIT_DIR="' . $GIT_DIR . '" git fetch --all --tags', $output, $exitCode);
		if ($exitCode !== 0) {
			exit($exitCode);
		}
		exec('GIT_DIR="' . $GIT_DIR . '" git reset --hard ' . $branch, $output, $exitCode);
		if ($exitCode !== 0) {
			exit($exitCode);
		}
		$output = array();
		exec('GIT_DIR="' . $GIT_DIR . '" git submodule update --init');

		$lastHash[$branch] = '';
		$gitLogCmd = sprintf($cmdGitLog, $GIT_DIR, $startCommit, $branch);
		$output = array();
		exec($gitLogCmd, $output, $exitCode);
		if ($gitRootIsWorkingCopy) {
			chdir($oldDir);
		}
		if ($exitCode !== 0) {
			exit($exitCode);
		}

		$currentField = '';
		$commitInfos = array();
		$inRelease = '';
		foreach ($output as $line) {
			if ($line === '--COMMIT--') {
				// Last line
				foreach (explode("\n", $commitInfos['body']) as $bodyLine) {
					$bodyInfo = explode(':', $bodyLine, 2);
					switch (trim($bodyInfo[0])) {
						case 'Resolves':
						case 'Fixes':
							$issues = explode(',', $bodyInfo[1]);
							foreach ($issues as $issue) {
								$issue = trim($issue);
								if (isset($issueMapping[$issue])) {
									$issue = $issueMapping[$issue];
								}
								$commitInfos['issues'][] = trim($issue);
							}
							break;
						case 'Reviewed-on':
						case 'Reviewed-by':
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
				if ($lastHash[$branch] === '') {
					// Remember last hash of this branch
					$lastHash[$branch] = $commitInfos['hash'];
				}
				if ($releaseCommit = getDetectedReleaseCommitCallback($commitInfos)) {
					$inRelease = $releaseCommit;
				}
				$commitInfos['inRelease'] = ( $inRelease ? $inRelease : 'next' );
				$commits[$branch][] = $commitInfos;
				$commitInfos = array();
				continue;
			}

			$infos = explode("\x01", $line);
			if (count($infos) === 1) {
				// Continuation line
				$commitInfos[$currentField] .= "\n" . $line;
			} else {
				foreach ($infos as $info) {
					list($field, $value) = explode(':', $info, 2);
					$currentField = $field;
					$commitInfos[$field] = $value;
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
					if ($thisDate > $issueInfo[$issue]['lastUpdate']) {
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
	uasort($issueInfo, 'compareIssues');

	if ($issueInfo === array()) {
		$out .= "<h2>$project</h2>\n";
		$out .= "<p>No changes found so far.</p>\n";
		continue;
	}

		// Print out the overview
	$out .= "<h2>$project</h2>\n";
	$out .= "<table>\n";
	$out .= "<tr>\n";
	$out .= "<th>Release</th>\n";
	foreach ($releasesToCheck as $release) {
		$releaseName = $release[0];
		if (isset($projectData['mapBranchReleaseFunction'])) {
			$releaseName = $projectData['mapBranchReleaseFunction']($release[0]);
		}
		$out .= sprintf('<th class="release">%s</th>', $releaseName);
	}
	$out .= '<th class="review">Reviews</th>';
	$out .= '<th class="desc">Description</th>';
	$out .= "</tr>";

	foreach ($issueInfo as $issueNumber => $issueData) {
		$out .= "<tr>";
		$issueLink = '';
		$reviewLink = '';
		if (preg_match('/^#(\d+)/', $issueNumber, $match)) {
			$issueLink = sprintf('http://forge.typo3.org/issues/%s', $match[1]);
			$reviewLink = sprintf($reviewLinkPattern, $match[1]);
		} elseif (preg_match('/^#M(\d+)/', $issueNumber, $match)) {
			$issueLink = sprintf('http://bugs.typo3.org/view.php?id=%s', $match[1]);
		}
		$topic = substr($issueNumber, 1);
		$issueNumber = sprintf('<a href="%s" target="_blank">%s</a>', $issueLink, $issueNumber);
		$out .= sprintf('<td class="issue">%s</td>', $issueNumber);
		$subject = '';
		foreach ($releasesToCheck as $release) {
			$releaseName = str_replace('-BP', '', $release[0]);
			// e.g. origin/TYPO3_4-5:
			$releaseBranch = $release[2];
			// e.g. TYPO3_4-5:
			$branchName = substr($releaseBranch, 7);
			$class = 'info-none';
			$text = '';
			if (isset($issueData['solved'][$releaseBranch])) {
				$subject = $issueData['solved'][$releaseBranch]['subject'];
			}
			if (preg_match('/^backports\/(.*)/', $releaseBranch, $matches)) {
				// Specific output for backport branches
				$originalBranch = 'origin/' . $matches[1];
				if (isset($issueData['solved'][$releaseBranch]) && isset($issueData['solved'][$originalBranch])) {
					// merged in original *and* backports branch already
					$class = 'info-solved';
					$text = sprintf('<span title="Merged in both origin and backports of %s">ok</span>', $matches[1]);
				} else if (isset($issueData['solved'][$releaseBranch]) && !isset($issueData['solved'][$originalBranch])) {
					// merged only in the backports branch: This is a backport!!!
					$class = 'info-solved';
					$text = sprintf('<a title="Merged only on backports of %s (%s)" target="_blank" href="%s?a=commit;h=%s" target="_blank">BACKPORT</a>',
						$matches[1],
						$issueData['solved'][$releaseBranch]['date'],
						$projectData['gitWebUrl'],
						$issueData['solved'][$releaseBranch]['hash']
					);
				} elseif (isset($issueData['solved'][$originalBranch]) && !isset($issueData['solved'][$releaseBranch])) {
					// merged only in the original branch: needs to cherry-pick still
					$text = sprintf('<a title="Merged only on origin of %s (%s), needs cherry-pick" target="_blank" href="%s?a=commit;h=%s" target="_blank">TODO</a>',
						$matches[1],
						$issueData['solved'][$originalBranch]['date'],
						$projectData['gitWebUrl'],
						$issueData['solved'][$originalBranch]['hash']
					);
				}
			} else {
				if (isset($issueData['solved'][$releaseBranch])) {
					$class = 'info-solved';
					if (isset($projectData['mapBranchReleaseFunction'])) {
						$releaseName = $projectData['mapBranchReleaseFunction']($releaseName);
					}
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
					$text = sprintf('<a title="Merged on %s %s" target="_blank" href="%s?a=commit;h=%s" target="_blank">%s</a>',
						$issueData['solved'][$releaseBranch]['date'],
						$versionName,
						$projectData['gitWebUrl'],
						$issueData['solved'][$releaseBranch]['hash'],
						$versionTag
					);
				} elseif (isset($issueData['planned']) && isset($issueData['planned'][$releaseName])) {
					if (isset($gerritIssues[$branchName][$topic])) {
						$class = 'info-planned info-planned-review';
						$url = $gerritIssues[$branchName][$topic][0]['url'];
						$text = sprintf('<span title="Planned for %s, review in process, not ready yet"><a href="%s" target="_blank">Review</a></span>', $releaseName, $url);
					} else {
						$class = 'info-planned';
						$text = sprintf('<span title="Planned for %s, not merged yet">TODO</span>', $releaseName);
					}
				}
			}
			$out .= sprintf('<td class="%s" branch="%s" issue="%s">%s</td>', $class, $branchName, $topic, $text);
		}
		$out .= '<td class="review">';
		if ($reviewLink) {
			$out .= sprintf('<a href="%s" target="_blank" title="Check review system for patches concerning this issue">Reviews</a>', $reviewLink);
		}
		$out .= '</td>';
		$out .= sprintf('<td class="description">%s</td>', htmlspecialchars($subject));
		$out .= "</tr>\n";
	}
	$out .= "</table>";

	$out .= '<p>Based on these GIT states:</p><ul>';
	foreach ($lastHash as $release => $hash) {
		$out .= sprintf('<li>%s: <a target="_blank" href="%s?a=commit;h=%s" target="_blank">%s</a>', $release, $projectData['gitWebUrl'], $hash, $hash);
	}
	$out .= '</ul>';

}

date_default_timezone_set('Europe/Berlin');
$out .= sprintf('<p>Generated on %s. Based on check-changes.php by <a href="mailto:ernst@cron-it.de">Ernesto Baschny</a>.</p>', strftime('%c', time()));

// include JS stuff
$out .= '<script src="jquery-1.7.2.min.js"></script>';
$out .= '<script src="jquery.cookie.js"></script>';
$out .= '<script src="typo3-merged.js"></script>';

$out .= "</body></html>";

$fh = fopen($htmlFile, 'w');
fwrite($fh, $out);
fclose($fh);

?>
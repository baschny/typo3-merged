#!/usr/bin/env php
<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011-2013 Ernesto Baschny (ernst@cron-it.de>
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

// Check gerrit and pull from git before doing the work
$online = FALSE;

// %s = GIT_DIR
// %s = 4-5-0 (first release)
// %s = 4-5 (current state)
$cmdGitLog = 'GIT_DIR="%s" git log %s..%s --submodule --pretty=format:hash:%%h%%x01hashfull:%%H%%x01date:%%cd%%x01tags:%%d%%x01subject:%%s%%x01body:%%b%%x0a--COMMIT-- --date=iso';

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

function branchToRelease($projectName, $branchName) {
	global $projectsToCheck, $releasesToCheck;
	static $releaseMapping = array();
	if (empty($releaseMapping)) {
		foreach ($projectsToCheck as $project => $projectData) {
			foreach ($releasesToCheck as $releaseRange) {
				$release = $releaseRange[0];
				$branch = $releaseRange[2];
				$releaseMapping[$project][$branch] = $release;
			}
		}
	}
	return $releaseMapping[$projectName][$branchName];
}

$out = '<html><head><title>Merged issues in releases</title><link rel="stylesheet" type="text/css" href="styles.css" /></head>';
$out .= "<body>\n";
$out .= "<h1>Issues merged into releases</h1>\n";

foreach ($projectsToCheck as $project => $projectData) {
	$commits = array();
	$lastHash = array();
	$uniqueNewFeatures = array();
	$releasesToCheck = $projectData['releases'];
	echo 'Working on ' . $project . ' now.' . PHP_EOL;
	$gerritIssues = array();
	$revertedCommits = array();
	$urlParts = parse_url($projectData['gitWebUrl']);
	$gerritProject = ltrim(substr($urlParts['path'], 0, -4), '/');
	if ($online) {
		$gerritIssues = fetchGerritReviewRequests($gerritProject);
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
		if ($online) {
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
		}

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
				$revertCommit = FALSE;
				foreach (explode("\n", $commitInfos['body']) as $bodyLine) {
					if (preg_match('/This reverts commit (\w+)/', $bodyLine, $match)) {
						$revertCommit = $match[1];
					}
					$bodyInfo = explode(':', $bodyLine, 2);
					// Fix switched Resolves / Release entries
					$bodyInfo[0] = trim($bodyInfo[0]);
					if ($bodyInfo[0] == 'Resolves' && preg_match('/^\s*\d\.\d$/', $bodyInfo[1])) {
						$bodyInfo[0] = 'Releases';
					} elseif ($bodyInfo[0] == 'Releases' && preg_match('/^\s*#\d+$/', $bodyInfo[1])) {
						$bodyInfo[0] = 'Resolves';
					}
					switch ($bodyInfo[0]) {
						case 'Resolves':
						case 'Resolve':
						case 'Fixes':
						case 'Fixs':
							$issues = explode(',', $bodyInfo[1]);
							foreach ($issues as $issue) {
								$issue = trim($issue);
								// Only use the numbers after the "#" (in case of buggy lines)
								$issue = preg_replace('/^(#[0-9]+).*$/', '$1', $issue);
								if (intval($issue)) {
									$issue = '#' . $issue;
								}
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
						case 'ReleaseS':
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
				$commitInfos['reverted'] = FALSE;
				if (isset($revertedCommits[$commitInfos['hashfull']])) {
					// This commit was reverted, keep a pointer to the reversal
					$commitInfos['reverted'] = $revertedCommits[$commitInfos['hashfull']];
				}
				$commits[$branch][] = $commitInfos;
				if ($revertCommit != '') {
					// Remember this commit which revertes some commit that is yet to come
					$revertedCommits[$revertCommit] = $commitInfos;
				}
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
					'reverted' => $commit['reverted'],
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
		$subject = 'Unknown';
		foreach ($releasesToCheck as $release) {
			$releaseBranch = $release[2];
			if (isset($issueData['solved'][$releaseBranch])) {
				$subject = $issueData['solved'][$releaseBranch]['subject'];
				break;
			}
		}
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

		// Find out unique target releases (for new features):
		$targetReleases = array();
		if ($issueData['planned']) {
			foreach ($issueData['planned'] as $plannedRelease => $dummy) {
				if (!isset($projectData['ignoreList'][$plannedRelease][$topic])) {
					$targetReleases[$plannedRelease] = TRUE;
				}
			}
		}
		if ($issueData['solved']) {
			foreach ($issueData['solved'] as $solvedReleaseBranch => $solvedData) {
				$solvedRelease = branchToRelease($project, $solvedReleaseBranch);
				if (preg_match('/^' . $solvedRelease . '/', $solvedData['inRelease'])) {
					// Only considered unique if solved in the "current release"
					$targetReleases[$solvedRelease] = TRUE;
				}
			}
		}
		if (count($targetReleases) == 1) {
			// Unique to one release only
			$uniqueNewFeatures[$topic] = array_shift(array_keys($targetReleases));
		}

		foreach ($releasesToCheck as $release) {
			$releaseName = $release[0];
			if (isset($projectData['mapBranchReleaseFunction'])) {
				$releaseName = $projectData['mapBranchReleaseFunction']($releaseName);
			}
			// e.g. origin/TYPO3_4-5:
			$releaseBranch = $release[2];
			// e.g. TYPO3_4-5:
			$branchName = substr($releaseBranch, 7);
			$class = 'info-none';
			$text = '';
			if (isset($projectData['ignoreList'][$branchName][$topic])) {
				// in case this issue + branch combination are on the ignore list, mark it appropriately
				$class = 'info-not-needed';
				$text = sprintf('<span title="%s" href="" target="_blank">given up</span>',
					$projectData['ignoreList'][$branchName][$topic]
				);

			} else {
				if (isset($issueData['solved'][$releaseBranch])) {
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
					$text = sprintf('<a title="Merged on %s %s" target="_blank" href="%s/commit/%s" target="_blank">%s</a>',
						$issueData['solved'][$releaseBranch]['date'],
						$versionName,
						$projectData['gitWebUrl'],
						$issueData['solved'][$releaseBranch]['hash'],
						$versionTag
					);
					// Solved but later reverted?
					if (is_array($issueData['solved'][$releaseBranch]['reverted'])) {
						$class .= ' info-reverted';
						$revertedInfos = $issueData['solved'][$releaseBranch]['reverted'];
						if ($revertedInfos['inRelease'] == 'next') {
							$versionName = 'for next release';
							$versionTag = 'next';
						} else if (preg_match('/^' . $releaseName . '/', $revertedInfos['inRelease'])) {
							$versionName = 'for ' . $revertedInfos['inRelease'];
							$versionTag = $revertedInfos['inRelease'];
						} else {
							$versionName = sprintf('in previous release (%s)', $revertedInfos['inRelease']);
							$versionTag = 'previous';
						}
						$text .= '<br/>' . sprintf('<a title="Reverted on %s %s" target="_blank" href="%s/commit/%s" target="_blank">%s</a> (<abbr title="Reverted commit">rev</abbr>)',
							$revertedInfos['date'],
							$versionName,
							$projectData['gitWebUrl'],
							$revertedInfos['hash'],
							$versionTag
						);
					}
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
		$newFeature = '';
		if ($uniqueNewFeatures[$topic]) {
			$newFeature = sprintf('<strong>[%s]</strong> ', $uniqueNewFeatures[$topic]);
		}
		if (strlen($subject) > 80) {
			$subject = sprintf('<span title="%s">%s...</span>', htmlspecialchars($subject), htmlspecialchars(substr($subject, 0, 76)));
		} else {
			$subject = htmlspecialchars($subject);
		}
		$out .= sprintf('<td class="description">%s%s</td>', $newFeature, htmlspecialchars($subject));
		$out .= "</tr>\n";
	}
	$out .= "</table>";

	$out .= '<p>Based on these GIT states:</p><ul>';
	foreach ($lastHash as $release => $hash) {
		$out .= sprintf('<li>%s: <a target="_blank" href="%s/commit/%s" target="_blank">%s</a>', $release, $projectData['gitWebUrl'], $hash, $hash);
	}
	$out .= '</ul>';

}

date_default_timezone_set('Europe/Berlin');
$out .= sprintf('<p>Generated on %s. Based on check-changes.php by <a href="mailto:ernst@cron-it.de">Ernesto Baschny</a>, extended by <a href="mailto:karsten.dambekalns@typo3.org">Karsten Dambekalns</a> and <a href="mailto:mario.rimann@typo3.org">Mario Rimann</a></p>', strftime('%c', time()));

// include JS stuff
$out .= '<script src="jquery-1.7.2.min.js"></script>';
$out .= '<script src="jquery.cookie.js"></script>';
$out .= '<script src="typo3-merged.js"></script>';

$out .= "</body></html>";

$fh = fopen($htmlFile, 'w');
fwrite($fh, $out);
fclose($fh);

?>
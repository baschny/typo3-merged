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
$online = TRUE;

// %s = GIT_DIR
// %s = 4-5-0 (first release)
// %s = 4-5 (current state)
// %s = additional git params, if desired (i.e. --dirstat)
$cmdGitLog = 'GIT_DIR="%s" git log %s..%s%s --submodule --pretty=format:hash:%%h%%x01hashfull:%%H%%x01date:%%cd%%x01tags:%%d%%x01subject:%%s%%x01body:%%b%%x0a--COMMIT-- --date=iso';

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
			if (isset($item['type']) && $item['type'] === 'stats') {
				if (intval($item['rowCount']) < 500) {
					// This was the last call
					$gerritFinished = TRUE;
				}
			} else {
				$lastSortKey = isset($item['sortKey']) ? $item['sortKey'] : '';
				if (isset($item['topic'])) {
					$issueNumber = $item['topic'];
					if (preg_match('/^issue\/(\d+)/', $issueNumber, $matches)) {
						$issueNumber = $matches[1];
					}
					$issueNumbers = array($issueNumber);
				} elseif (isset($item['trackingIds'])) {
					$issueNumbers = array();
					foreach ($item['trackingIds'] as $tracking) {
						if ($tracking['system'] === 'Forge') {
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
	global $projectsToCheck;
	static $releaseMapping = array();
	if (empty($releaseMapping)) {
		foreach ($projectsToCheck as $project => $projectData) {
			foreach ($projectData['releases'] as $releaseRange) {
				$release = $releaseRange[0];
				$branch = $releaseRange[2];
				$releaseMapping[$project][$branch] = $release;
			}
		}
	}
	return $releaseMapping[$projectName][$branchName];
}

function isValidRelease($project, $releaseName) {
	global $projectsToCheck;
	static $validReleases = array();
	if (empty($validReleases)) {
		foreach ($projectsToCheck[$project]['releases'] as $releaseRange) {
			$release = $releaseRange[0];
			$validReleases[$release] = TRUE;
		}
	}
	return isset($validReleases[$releaseName]);
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
	$revertedCommits = array();
	$urlParts = parse_url($projectData['gitWebUrl']);
	$gerritProject = ltrim(substr($urlParts['path'], 0, -4), '/');
	if ($online) {
		$gerritIssues = fetchGerritReviewRequests($gerritProject);
	}
	$additionalGitParams = '';
	if (isset($projectData['extractComponentNameFromPathByBranchCallback'])) {
		$additionalGitParams = ' --dirstat';
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
			exec('GIT_DIR="' . $GIT_DIR . '" git fetch --quiet --all --tags', $output, $exitCode);
			if ($exitCode !== 0) {
				exit($exitCode);
			}
			exec('GIT_DIR="' . $GIT_DIR . '" git reset --quiet --hard ' . $branch, $output, $exitCode);
			if ($exitCode !== 0) {
				exit($exitCode);
			}
			$output = array();
			exec('GIT_DIR="' . $GIT_DIR . '" git submodule --quiet update --init');
		}

		$lastHash[$branch] = '';
		$gitLogCmd = sprintf($cmdGitLog, $GIT_DIR, $startCommit, $branch, $additionalGitParams);
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
					if ($bodyInfo[0] === 'Resolves' && preg_match('/^\s*\d\.\d$/', $bodyInfo[1])) {
						$bodyInfo[0] = 'Releases';
					} elseif ($bodyInfo[0] === 'Releases' && preg_match('/^\s*#\d+$/', $bodyInfo[1])) {
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
								if ((int)$issue) {
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
								if (trim($release) !== '') {
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
				if ($revertCommit !== '') {
					// Remember this commit which revertes some commit that is yet to come
					$revertedCommits[$revertCommit] = $commitInfos;
				}
				$commitInfos = array();
				continue;
			}

			if (preg_match('/^\s+([\d\.]+)%\s+(.*)/', $line, $matches)) {
				$percent = $matches[1];
				$path = $matches[2];
				// Is a --dirstat line, format:
				//  100.0% typo3/sysext/dbal/
				//   42.5% typo3/sysext/extbase/Classes/MVC/Controller/
				// ....
				// Unfortulately these refer to the *previous* log entry
				$component = '';
				if ($projectData['extractComponentNameFromPathByBranchCallback']) {
					$component = $projectData['extractComponentNameFromPathByBranchCallback'](branchToRelease($project, $branch), $path);
				}
				if ($component) {
					if (!isset($commits[$branch][count($commits[$branch])-1]['components'][$component])) {
						$commits[$branch][count($commits[$branch])-1]['components'][$component] = 0;
					}
					$commits[$branch][count($commits[$branch])-1]['components'][$component] += $percent;
				}
			} elseif ($line !== '') {
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
				if (isset($commit['components'])) {
					$issueInfo[$issue]['solved'][$branch]['components'] = $commit['components'];
				}
				if (isset($commit['releases'])) {
					foreach ($commit['releases'] as $release) {
						if (isValidRelease($project, $release)) {
							$issueInfo[$issue]['planned'][$release] = TRUE;
						}
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

	// Prepare the per-release outputs
	if ($projectData['perReleaseOutput']) {
		foreach ($releasesToCheck as $releaseRange) {
			$releaseName = $releaseRange[0];
			if (isset($projectData['mapBranchReleaseFunction'])) {
				$releaseName = $projectData['mapBranchReleaseFunction']($release[0]);
			}
			$outRelease[$releaseName] = '<html><head><title>Merged issues in releases</title><link rel="stylesheet" type="text/css" href="styles.css" /></head>';
			$outRelease[$releaseName] .= "<body>\n";
			$outRelease[$releaseName] .= "<h1>Issues merged into releases</h1>\n";
			$outRelease[$releaseName] .= "<h2>$project, Release $releaseName</h2>\n";
			$outRelease[$releaseName] .= "<table>\n";
			$outRelease[$releaseName] .= "<tr>\n";
			$outRelease[$releaseName] .= "<th>Issue</th>\n";
			$outRelease[$releaseName] .= '<th class="release">' . $releaseName . "</th>\n";
			$outRelease[$releaseName] .= '<th class="review">Reviews</th>';
			if (isset($projectData['extractComponentNameFromPathByBranchCallback'])) {
				$outRelease[$releaseName] .= '<th class="components">Components</th>';
			}
			$outRelease[$releaseName] .= '<th class="desc">Description</th>';
			$outRelease[$releaseName] .= "</tr>";
		}
	}

	foreach ($releasesToCheck as $release) {
		$releaseName = $release[0];
		if (isset($projectData['mapBranchReleaseFunction'])) {
			$releaseName = $projectData['mapBranchReleaseFunction']($release[0]);
		}
		$out .= sprintf('<th class="release">%s</th>', $releaseName);
	}
	$out .= '<th class="review">Reviews</th>';
	if (isset($projectData['extractComponentNameFromPathByBranchCallback'])) {
		$out .= '<th class="components">Components</th>';
	}
	$out .= '<th class="desc">Description</th>';
	$out .= "</tr>";

	foreach ($issueInfo as $issueNumber => $issueData) {
		$components = array();
		$subject = 'Unknown';
		foreach ($releasesToCheck as $release) {
			$releaseBranch = $release[2];
			if (isset($issueData['solved'][$releaseBranch])) {
				$subject = $issueData['solved'][$releaseBranch]['subject'];
				if (isset($issueData['solved'][$releaseBranch]['components'])) {
					$components = $issueData['solved'][$releaseBranch]['components'];
					arsort($components);
				}
			}
		}
		$issueLink = '';
		$reviewLink = '';
		if (preg_match('/^#(\d+)/', $issueNumber, $match)) {
			$issueLink = sprintf('https://forge.typo3.org/issues/%s', $match[1]);
			$reviewLink = sprintf($reviewLinkPattern, $match[1]);
		} elseif (preg_match('/^#M(\d+)/', $issueNumber, $match)) {
			$issueLink = sprintf('http://bugs.typo3.org/view.php?id=%s', $match[1]);
		}
		$topic = substr($issueNumber, 1);
		$issueNumber = sprintf('<a href="%s" target="_blank">%s</a>', $issueLink, $issueNumber);

		// Find out unique target releases (for new features):
		$targetReleases = array();
		if (isset($issueData['planned'])) {
			foreach ($issueData['planned'] as $plannedRelease => $dummy) {
				if (!isset($projectData['ignoreList'][$plannedRelease][$topic])) {
					$targetReleases[$plannedRelease] = TRUE;
				}
			}
		}
		if (isset($issueData['solved'])) {
			foreach ($issueData['solved'] as $solvedReleaseBranch => $solvedData) {
				$solvedRelease = branchToRelease($project, $solvedReleaseBranch);
				if (preg_match('/^' . $solvedRelease . '/', $solvedData['inRelease'])) {
					// Only considered unique if solved in the "current release"
					$targetReleases[$solvedRelease] = TRUE;
				}
			}
		}
		$uniqueNewFeatureRelease = FALSE;
		if (count($targetReleases) === 1) {
			// Unique to one release only
			$targetReleasesKeys = array_keys($targetReleases);
			$uniqueNewFeatureRelease = array_shift($targetReleasesKeys);
		}

		$outReleasesCells = '';
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
					if ($issueData['solved'][$releaseBranch]['inRelease'] === 'next') {
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
						if ($revertedInfos['inRelease'] === 'next') {
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
			if ($uniqueNewFeatureRelease == $releaseName) {
				$outReleasesCellUnique = sprintf('<td class="%s" branch="%s" issue="%s">%s</td>', $class, $branchName, $topic, $text);
			}
			$outReleasesCells .= sprintf('<td class="%s" branch="%s" issue="%s">%s</td>', $class, $branchName, $topic, $text);
		}
		if ($reviewLink) {
			$reviewLinkHtml = sprintf('<a href="%s" target="_blank" title="Check review system for patches concerning this issue">Reviews</a>', $reviewLink);
		} else {
			$reviewLinkHtml = '';
		}
		$newFeatureReleaseHtml = '';
		if ($uniqueNewFeatureRelease) {
			$newFeatureReleaseHtml = sprintf('<strong>[%s]</strong> ', $uniqueNewFeatureRelease);
		}
		if (strlen($subject) > 80) {
			$subject = sprintf('<span title="%s">%s...</span>', htmlspecialchars($subject), htmlspecialchars(substr($subject, 0, 76)));
		} else {
			$subject = htmlspecialchars($subject);
		}

		$componentsHtml = '';
		$componentsOthersHtml = '';
		if (!empty($components)) {
			$others = array();
			foreach ($components as $componentName => $percent) {
				if ($percent <= 10) {
					$others[$componentName] = $percent;
				} else {
					$componentsHtml .= sprintf(' <span title="%s%%" class="component">%s</span>', $percent, $componentName);
				}
			}
			if (!empty($others)) {
				$othersList = '';
				foreach ($others as $componentName => $percent) {
					$othersList .= sprintf('%s (%s%%), ', $componentName, $percent);
				}
				$componentsHtml .= sprintf(' <span title="%s" class="component">others</span>', trim($othersList, ', '));
			}
		}

		if ($projectData['perReleaseOutput']
			&& $uniqueNewFeatureRelease
		) {
			$outRelease[$uniqueNewFeatureRelease] .= "<tr>";
			$outRelease[$uniqueNewFeatureRelease] .= sprintf('<td class="issue">%s</td>', $issueNumber);
			$outRelease[$uniqueNewFeatureRelease] .= $outReleasesCellUnique;
			$outRelease[$uniqueNewFeatureRelease] .= sprintf('<td class="review">%s</td>', $reviewLinkHtml);
			if (isset($projectData['extractComponentNameFromPathByBranchCallback'])) {
				$outRelease[$uniqueNewFeatureRelease] .= sprintf('<td class="components">%s</td>', $componentsHtml);
			}
			$outRelease[$uniqueNewFeatureRelease] .= sprintf('<td class="description">%s</td>', $subject);
			$outRelease[$uniqueNewFeatureRelease] .= "</tr>\n";
		}
		$out .= "<tr>";
		$out .= sprintf('<td class="issue">%s</td>', $issueNumber);
		$out .= $outReleasesCells;
		$out .= sprintf('<td class="review">%s</td>', $reviewLinkHtml);
		if (isset($projectData['extractComponentNameFromPathByBranchCallback'])) {
			$out .= sprintf('<td class="components">%s</td>', $componentsHtml);
		}
		$out .= sprintf('<td class="description">%s%s</td>', $newFeatureReleaseHtml, $subject);
		$out .= "</tr>\n";
	}
	$out .= "</table>";

	$out .= '<p>Based on these GIT states:</p><ul>';
	foreach ($lastHash as $release => $hash) {
		$out .= sprintf('<li>%s: <a target="_blank" href="%s/commit/%s" target="_blank">%s</a>', $release, $projectData['gitWebUrl'], $hash, $hash);
	}
	$out .= '</ul>';

	// Prepare the per-release outputs
	if ($projectData['perReleaseOutput']) {
		foreach ($outRelease as $releaseName => $outLines) {
			$outLines .= "</body></html>";
			$fh = fopen(sprintf($projectData['perReleaseOutput'], $releaseName), 'w');
			fwrite($fh, $outLines);
			fclose($fh);
		}
	}

}

date_default_timezone_set('Europe/Berlin');
$out .= sprintf('<p>Generated on %s. Based on check-changes.php by <a href="mailto:ernst@cron-it.de">Ernesto Baschny</a>, extended by <a href="mailto:karsten.dambekalns@typo3.org">Karsten Dambekalns</a> and <a href="mailto:mario.rimann@typo3.org">Mario Rimann</a></p>', strftime('%c', time()));

// include JS stuff
$out .= '<script src="jquery-2.2.3.min.js"></script>';
$out .= '<script src="jquery.cookie.js"></script>';
$out .= '<script src="typo3-merged.js"></script>';

$out .= "</body></html>";

$fh = fopen($htmlFile, 'w');
fwrite($fh, $out);
fclose($fh);

<?php
// configuration for FLOW3 base distribution in Jenkins CI job

$gitRoot = getenv('WORKSPACE') . '/';
$gitRootIsWorkingCopy = TRUE;
$htmlFile = getenv('WORKSPACE') . '/index.html';

$reviewLinkPattern = "https://review.typo3.org/#/q/tr:%s,n,z";

$issueMapping = array();

$projectsToCheck = array(
	'FLOW3 Base Dist' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Distributions/Base.git',
		'releases' => array(
				# project, starting point, branch, working copy path
			array('1.0', 'refs/tags/1.0.0', 'origin/FLOW3-1.0', 'FLOW3-master'),
			array('1.1', 'refs/tags/1.0.0', 'origin/FLOW3-1.1', 'FLOW3-master'),
			array('1.2', 'refs/tags/1.0.0', 'origin/master', 'FLOW3-master'),
		),
	),
	'TYPO3.FLOW3' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Packages/TYPO3.FLOW3.git',
		'releases' => array(
			array('1.0', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.0', 'FLOW3-master/Packages/Framework/TYPO3.FLOW3'),
			array('1.1', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.1', 'FLOW3-master/Packages/Framework/TYPO3.FLOW3'),
			array('1.2', 'refs/tags/FLOW3-1.0.0', 'origin/master', 'FLOW3-master/Packages/Framework/TYPO3.FLOW3'),
		),
	),
	'TYPO3.Fluid' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Packages/TYPO3.Fluid.git',
		'releases' => array(
			array('1.0', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.0', 'FLOW3-master/Packages/Framework/TYPO3.Fluid'),
			array('1.1', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.1', 'FLOW3-master/Packages/Framework/TYPO3.Fluid'),
			array('1.2', 'refs/tags/FLOW3-1.0.0', 'origin/master', 'FLOW3-master/Packages/Framework/TYPO3.Fluid'),
		),
	),
	'TYPO3.Party' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Packages/TYPO3.Party.git',
		'releases' => array(
			array('1.0', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.0', 'FLOW3-master/Packages/Framework/TYPO3.Party'),
			array('1.1', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.1', 'FLOW3-master/Packages/Framework/TYPO3.Party'),
			array('1.2', 'refs/tags/FLOW3-1.0.0', 'origin/master', 'FLOW3-master/Packages/Framework/TYPO3.Party'),
		),
	),
	'TYPO3.Kickstart' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Packages/TYPO3.Kickstart.git',
		'releases' => array(
			array('1.0', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.0', 'FLOW3-master/Packages/Framework/TYPO3.Kickstart'),
			array('1.1', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.1', 'FLOW3-master/Packages/Framework/TYPO3.Kickstart'),
			array('1.2', 'refs/tags/FLOW3-1.0.0', 'origin/master', 'FLOW3-master/Packages/Framework/TYPO3.Kickstart'),
		),
	),
	'TYPO3.Welcome' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Packages/TYPO3.Welcome.git',
		'releases' => array(
			array('1.0', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.0', 'FLOW3-master/Packages/Framework/TYPO3.Welcome'),
			array('1.1', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.1', 'FLOW3-master/Packages/Framework/TYPO3.Welcome'),
			array('1.2', 'refs/tags/FLOW3-1.0.0', 'origin/master', 'FLOW3-master/Packages/Framework/TYPO3.Welcome'),
		),
	),
	'Doctrine' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Packages/Doctrine.git',
		'releases' => array(
			array('1.0', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.0', 'Doctrine'),
			array('1.1', 'refs/tags/FLOW3-1.0.0', 'origin/master', 'Doctrine'),
		),
	),
	'Doctrine.Common' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Packages/Doctrine.Common.git',
		'releases' => array(
			array('1.0', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.0', 'FLOW3-master/Packages/Framework/Doctrine.Common'),
			array('1.1', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.1', 'FLOW3-master/Packages/Framework/Doctrine.Common'),
			array('1.2', 'refs/tags/FLOW3-1.0.0', 'origin/master', 'FLOW3-master/Packages/Framework/Doctrine.Common'),
		),
	),
	'Doctrine.DBAL' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Packages/Doctrine.DBAL.git',
		'releases' => array(
			array('1.0', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.0', 'FLOW3-master/Packages/Framework/Doctrine.DBAL'),
			array('1.1', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.1', 'FLOW3-master/Packages/Framework/Doctrine.DBAL'),
			array('1.2', 'refs/tags/FLOW3-1.0.0', 'origin/master', 'FLOW3-master/Packages/Framework/Doctrine.DBAL'),
		),
	),
	'Doctrine.ORM' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Packages/Doctrine.ORM.git',
		'releases' => array(
			array('1.0', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.0', 'FLOW3-master/Packages/Framework/Doctrine.ORM'),
			array('1.1', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.1', 'FLOW3-master/Packages/Framework/Doctrine.ORM'),
			array('1.2', 'refs/tags/FLOW3-1.0.0', 'origin/master', 'FLOW3-master/Packages/Framework/Doctrine.ORM'),
		),
	),
	'Symfony.Component.Yaml' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Packages/Symfony.Component.Yaml.git',
		'releases' => array(
			array('1.0', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.0', 'FLOW3-master/Packages/Framework/Symfony.Component.Yaml'),
			array('1.1', 'refs/tags/FLOW3-1.0.0', 'origin/FLOW3-1.1', 'FLOW3-master/Packages/Framework/Symfony.Component.Yaml'),
			array('1.2', 'refs/tags/FLOW3-1.0.0', 'origin/master', 'FLOW3-master/Packages/Framework/Symfony.Component.Yaml'),
		),
	),
	'Symfony.Component.DomCrawler' => array(
		'gitWebUrl' => 'http://git.typo3.org/FLOW3/Packages/Symfony.Component.DomCrawler.git',
		'releases' => array(
			array('1.1', 'refs/tags/FLOW3-1.1.0-beta1', 'origin/FLOW3-1.1', 'FLOW3-master/Packages/Framework/Symfony.Component.DomCrawler'),
			array('1.2', 'refs/tags/FLOW3-1.1.0-beta1', 'origin/master', 'FLOW3-master/Packages/Framework/Symfony.Component.DomCrawler'),
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
	if (preg_match('/FLOW3-([0-9.]{5}(?:-(alpha|beta|rc)[0-9]+)?)/', $commitInfos['tags'], $matches)) {
		return $matches[1];
	}
	return NULL;
}

?>
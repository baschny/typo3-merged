typo3-merged
============

TYPO3 Merged Issues Checker.

This is a pretty ugly script which "simply" checks the git logs for various
TYPO3 branches, parses information like "Resolves:" and "Branches:" lines
and generates a HTML file with an overview of which fix has been backported
and which is still open.

See it in action here:

	http://www.typo3-anbieter.de/typo3-merges/
	http://bit.ly/flow3-merged-changes

Usage
-----

You need to prepare git clones and a matching configuration, then run the
check-changes.php script with the configuration file as first parameter.

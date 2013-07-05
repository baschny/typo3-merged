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


Initial setup
-------------

The following steps will get you up and running to generate a list of changes
in the TYPO3-Core. You can also set up this script to check other projects, see
examples in the different configuration files (e.g. `typo3-extbase.php` )

	cd <path/to/your/target/directory>
	git clone git://github.com/baschny/typo3-merged.git .

Then  clone the TYPO3-Core once for each branch (add those listed in `typo3-core.php`).
For speed improvements, we're keeping a full clone for each branch at
`/www/shared/TYPO3core` - if you want it to be at another place, change the path in
`typo3-core.php`.

	mkdir /www/shared/TYPO3core
	cd /www/shared/TYPO3core/
	git clone git://git.typo3.org/Packages/TYPO3.CMS.git TYPO3_6-2
	git clone git://git.typo3.org/Packages/TYPO3.CMS.git TYPO3_6-1
	git clone git://git.typo3.org/Packages/TYPO3.CMS.git TYPO3_6-0
	git clone git://git.typo3.org/Packages/TYPO3.CMS.git TYPO3_4-7
	git clone git://git.typo3.org/Packages/TYPO3.CMS.git TYPO3_4-5

Gerrit access
-------------

Make sure you have SSH access to gerrit. Configure your username in `$HOME/.ssh/config`:

	Host review.typo3.org
		User baschny
		Port 29418

Your public key must be known to gerrit and your private key accessible in the 
environment where check-changes.php runs. You might want to use the `keychain` tool
to keep your private key with a passphrase but accessible through a long runing
ssh-agent.

Test the ssh with:

	$ ssh review.typo3.org -p 29418 gerrit version
	gerrit version 2.2.2.1-3-gb2ba1a2

Running
-------

Then run the script `check-changes.php` with one single parameter that points to the
TYPO3-Core config file.

	./check-changes.php typo3-core.php

This will generate you a core.html file that contains the report.

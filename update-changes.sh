#!/bin/bash

base=/home/ernst/TYPO3-Release
php $base/check-changes.php
rsync -a $base/index.html $base/styles.css spinat.serverdienst.net:/www/sites/web77000/html/typo3-merges/

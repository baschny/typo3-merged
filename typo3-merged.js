$(document).ready(function(){
	// add button to control filtering
	$('h2').after('<div id="controls"></div><div class="count"></div>');

	// normalize the branch name to not contain dots (otherwise the CSS/JS-Selectors
	// will not work...
	$('td.info-planned').each(function() {
		$(this).attr('branch', normalizeName($(this).attr('branch')));
	});


	$('#controls').append('<button id="showAllEntries">show all</button>');
	$('#showAllEntries').click(function() {
        // mark this (and only this) button as active
        $('button').removeClass('activeFilter');
        $(this).addClass('activeFilter');

		$('tr').show();

		// set cookie for this filter
		$.cookie('lastActiveFilter', 'showAllEntries');

		updateCount();
	});

	$('#controls').append('<button id="hideResolved">hide done</button>');
	$('#hideResolved').click(function() {
        // mark this (and only this) button as active
        $('button').removeClass('activeFilter');
        $(this).addClass('activeFilter');

		hideResolved();
		// set cookie for this filter
		$.cookie('lastActiveFilter', 'hideResolved');

		updateCount();
	});

	$('.info-planned').each(function() {
		$(this).parent().addClass('todo_' + $(this).attr('branch'));
		addButton($(this).attr('branch'));
	});

	// read the cookie and restore the last filter
	if ($.cookie('lastActiveFilter') != null) {
		$('#' + $.cookie('lastActiveFilter')).click();
	}


	/**
	 * Called multiple times per target branch, this adds a button to filter the list
	 * by open changes per target branch.
	 *
	 * @param string the branch name, e.g. "TYPO3_4-7"
	 */
	function addButton(branch) {
		officialBranchName = branch;
		branch=normalizeName(branch);

		if ($('#'+branch).length) {

		} else {
			$('#controls').append('<button id="' + branch + '">Show only ' + officialBranchName + '</button>');
			$('#' + branch).click({branch: branch}, function() {
                // mark this (and only this) button as active
                $('button').removeClass('activeFilter');
                $(this).addClass('activeFilter');

				$('tr').hide();
				$('tbody tr:first').show();
				$('.todo_' + branch).show();

				// set cookie for this filter
				$.cookie('lastActiveFilter', branch);

				updateCount();
			});
		}
	}

	/**
	 * Normalizes names of e.g. branches with dots in it (each dot gets replaced with "_")
	 *
	 * @param name string the name to normalize
	 * @return string the normalized name
	 */
	function normalizeName(name) {
		name=name.replace(".","_");
		return name;
	}

	/**
	 * Hides all rows that have not a single TODO (e.g. those that need no
	 * action at the moment)
	 */
	function hideResolved() {
		// hide all those entries where nothing has to be done
		$('tr').addClass('nothingToDo').show();

		$('.info-planned').each(function() {
			$(this).parent().removeClass('nothingToDo');
		});
		$('tr.nothingToDo').hide();
		$('tbody tr:first').show();
	}

	/**
	 * Shows the number of rows on top of the table.
	 */
	function updateCount() {
		count = $('table tr:visible').length - 1;
		$('div.count').html('Listing ' + count + ' entries.');
	}
});
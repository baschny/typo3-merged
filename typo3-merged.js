$(document).ready(function(){
	// add button to control filtering
	$('h2').after('<div id="controls"></div>');


	$('#controls').append('<button id="showAllEntries">show all</button>');
	$('#showAllEntries').click(function() {
		$('tr').show();
	});

	$('#controls').append('<button id="hideResolved">hide done</button>');
	$('#hideResolved').click(function() {
		hideResolved();
	});

	/**
	 * Hides all rows that have not a single TODO (e.g. those that need no
	 * action at the moment)
	 */
	function hideResolved() {
		// hide all those entries where nothing has to be done
		$('tr').addClass('nothingToDo');

		$('.info-planned').each(function() {
			$(this).parent().removeClass('nothingToDo');
		});
		$('tr.nothingToDo').hide();
		$('tbody tr:first').show();
	}

});
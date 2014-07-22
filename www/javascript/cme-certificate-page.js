YAHOO.util.Event.onDOMReady(function() {
	var checkbox_list_els = YAHOO.util.Dom.getElementsByClassName(
		'swat-checkbox-list',
		'div'
	);

	for (var i = 0; i < checkbox_list_els.length; i++) {
		var checkboxes = YAHOO.util.Dom.getElementsBy(
			function (el) { return (el.type === 'checkbox'); },
			'input',
			checkbox_list_els[i]
		);

		if (checkboxes.length > 0) {
			for (var j = 0; j < checkboxes.length; j++) {
				(function () {
					var item = checkboxes[j].parentNode.parentNode;
					var checkbox = checkboxes[j];

					var the_checkboxes = checkboxes;
					YAHOO.util.Event.on(
						checkbox,
						'click',
						function(e) {
							updateListSelection(the_checkboxes);
						}
					);

					// passthrough click on list item to radio button
					YAHOO.util.Event.on(
						item,
						'click',
						function(e) {
							var target = YAHOO.util.Event.getTarget(e);
							if (target === item) {
								checkbox.checked = !checkbox.checked;
								updateListSelection(the_checkboxes);
							}
						}
					);
				})();
			}

			updateListSelection(checkboxes);
		}
	}

	function updateListSelection(list)
	{
		var li;
		for (var i = 0; i < list.length; i++) {
			li = list[i].parentNode.parentNode;
			if (list[i].checked) {
				YAHOO.util.Dom.addClass(li, 'selected');
			} else {
				YAHOO.util.Dom.removeClass(li, 'selected');
			}
		}
	}
});

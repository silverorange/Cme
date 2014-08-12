YAHOO.util.Event.onDOMReady(function() {
	var radio_list_els = YAHOO.util.Dom.getElementsByClassName(
		'swat-radio-list',
		'ul'
	);

	for (var i = 0; i < radio_list_els.length; i++) {
		var radio_buttons = YAHOO.util.Dom.getElementsBy(
			function (el) { return (el.type === 'radio'); },
			'input',
			radio_list_els[i]
		);

		if (radio_buttons.length > 0) {
			for (var j = 0; j < radio_buttons.length; j++) {
				(function () {
					var item = YAHOO.util.Dom.getAncestorByTagName(
						radio_buttons[j],
						'li'
					);
					var radio = radio_buttons[j];

					var the_radio_buttons = radio_buttons;
					YAHOO.util.Event.on(
						radio,
						'click',
						function(e) {
							updateListSelection(the_radio_buttons);
						}
					);

					// passthrough click on list item to radio button
					YAHOO.util.Event.on(
						item,
						'click',
						function(e) {
							var target = YAHOO.util.Event.getTarget(e);
							if (target === item) {
								if (!radio.disabled) {
									radio.checked = true;
									updateListSelection(the_radio_buttons);
								}
							}
						}
					);
				})();
			}

			updateListSelection(radio_buttons);
		}
	}

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
					var item = YAHOO.util.Dom.getAncestorByTagName(
						checkboxes[j],
						'li'
					);
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
		for (var i = 0; i < list.length; i++) {
			var li = YAHOO.util.Dom.getAncestorByTagName(list[i], 'li');
			if (list[i].checked) {
				YAHOO.util.Dom.addClass(li, 'selected');
			} else {
				YAHOO.util.Dom.removeClass(li, 'selected');
			}
		}
	}
});

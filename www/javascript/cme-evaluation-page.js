YAHOO.util.Event.onDOMReady(function() {
	var list_els = YAHOO.util.Dom.getElementsByClassName(
		'swat-radio-list', 'ul');

	var list, name;
	for (var i = 0; i < list_els.length; i++) {
		list = YAHOO.util.Dom.getElementsBy(
			function (el) { return (el.type === 'radio'); },
			'input', list_els[i]);

		if (list.length > 0) {
			name = list[0].name;

			for (var j = 0; j < list.length; j++) {
				(function () {
					var item = list[j].parentNode.parentNode;
					var radio = list[j];
					var the_list = list;
					YAHOO.util.Event.on(list[j], 'click', function(e) {
						updateList(the_list);
					});
					// passthrough click on list item to radio button
					YAHOO.util.Event.on(item, 'click', function(e) {
						var target = YAHOO.util.Event.getTarget(e);
						if (target === item) {
							radio.checked = true;
							updateList(the_list);
						}
					});
				})();
			}

			updateList(list);
		}
	}

	function updateList(list)
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

function CMEDisclosure(id, class_name, server, title, content, cancel_uri)
{
	this.id         = id;
	this.class_name = class_name;
	this.server     = server;
	this.content    = content;
	this.title      = title;
	this.cancel_uri = cancel_uri;

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

CMEDisclosure.accept_text =
	'I Have Read the CME Information / Continue';

CMEDisclosure.cancel_text =
	'Cancel and Return';

CMEDisclosure.confirm_text =
	'Before you view %s, please attest to reading the following:';

CMEDisclosure.prototype.init = function()
{
	var header = document.createElement('div');
	header.className = 'cme-disclosure-header';
	header.appendChild(
		document.createTextNode(
			CMEDisclosure.confirm_text.replace(/%s/, this.title)
		)
	);

	var continue_button = document.createElement('input');
	continue_button.type = 'button';
	continue_button.value = CMEDisclosure.accept_text;
	continue_button.className = 'button swat-primary-button';
	YAHOO.util.Event.on(continue_button, 'click', function(e) {
		continue_button.disabled = true;
		this.submitCMEPiece();
	}, this, true);

	var cancel_button = document.createElement('input');
	cancel_button.type = 'button';
	cancel_button.value = CMEDisclosure.cancel_text;
	cancel_button.className = 'button cancel-button';
	YAHOO.util.Event.on(cancel_button, 'click', function(e) {
		var base = document.getElementsByTagName('base')[0];
		window.location = base.href + this.cancel_uri;
	}, this, true);

	var footer = document.createElement('div');
	footer.className = 'cme-disclosure-footer';
	footer.appendChild(continue_button);
	footer.appendChild(cancel_button);

	var content = document.createElement('div');
	content.className = 'cme-disclosure-content';
	content.innerHTML = this.content;

	this.scroll_content = document.createElement('div');
	this.scroll_content.className = 'cme-disclosure-scroll-content';
	this.scroll_content.appendChild(content);
	this.scroll_content.style.height =
		(YAHOO.util.Dom.getViewportHeight() - 200) + 'px';

	this.container = document.createElement('div');
	this.container.id = this.id;
	this.container.className = this.class_name;
	this.container.appendChild(header);
	this.container.appendChild(this.scroll_content);
	this.container.appendChild(footer);

	this.overlay = document.createElement('div');
	this.overlay.className = 'cme-piece-overlay';
	this.overlay.style.height = YAHOO.util.Dom.getDocumentHeight() + 'px';

	// Bit of a hack to get the initial height of the overlay correctly. The
	// page draws slowly and doesn't have the final height when the DOM is
	// ready.
	var that = this;
	setTimeout(function() {
		that.overlay.style.height = YAHOO.util.Dom.getDocumentHeight() + 'px';
	}, 500);

	YAHOO.util.Event.on(window, 'resize', this.handleResize, this, true);

	document.body.appendChild(this.overlay);
	document.body.appendChild(this.container);

	SwatZIndexManager.raiseElement(this.overlay);
	SwatZIndexManager.raiseElement(this.container);
};

CMEDisclosure.prototype.handleResize = function(e)
{
	this.scroll_content.style.height =
		(YAHOO.util.Dom.getViewportHeight() - 200) + 'px';

	this.overlay.style.height = YAHOO.util.Dom.getDocumentHeight() + 'px';
};

CMEDisclosure.prototype.close = function()
{
	var animation = new YAHOO.util.Anim(
		this.container,
		{ opacity: { to: 0 }, top: { to: -100 } },
		0.25,
		YAHOO.util.Easing.easeIn);

	animation.onComplete.subscribe(function() {

		this.container.style.display = 'none';
		var animation = new YAHOO.util.Anim(
			this.overlay,
			{ opacity: { to: 0 } },
			0.25,
			YAHOO.util.Easing.easeOut);

		animation.onComplete.subscribe(function() {
			this.overlay.style.display = 'none';
		}, this, true);

		animation.animate();

	}, this, true);

	animation.animate();
};

CMEDisclosure.prototype.submitCMEPiece = function()
{
	var callback = {
		success: function(o) {},
		failure: function(o) {}
	};

	YAHOO.util.Connect.asyncRequest('POST', this.server, callback);

	this.close();
};

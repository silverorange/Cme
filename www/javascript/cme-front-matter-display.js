function CMEFrontMatterDisplay(id, class_name, server, title, content,
	cancel_uri)
{
	this.id         = id;
	this.class_name = class_name;
	this.server     = server;
	this.content    = content;
	this.title      = title;
	this.cancel_uri = cancel_uri;

	YAHOO.util.Event.onDOMReady(this.init, this, true);
}

CMEFrontMatterDisplay.accept_text =
	'I Have Read the CME Information / Continue';

CMEFrontMatterDisplay.cancel_text =
	'Cancel and Return';

CMEFrontMatterDisplay.confirm_text =
	'Before you view %s, please attest to reading the following:';

CMEFrontMatterDisplay.prototype.init = function()
{
	var header = document.createElement('div');
	header.className = 'cme-front-matter-display-header';
	header.appendChild(
		document.createTextNode(
			CMEFrontMatterDisplay.confirm_text.replace(/%s/, this.title)
		)
	);

	var continue_button = document.createElement('input');
	continue_button.type = 'button';
	continue_button.value = CMEFrontMatterDisplay.accept_text;
	continue_button.className = 'button swat-primary-button';
	YAHOO.util.Event.on(continue_button, 'click', function(e) {
		continue_button.disabled = true;
		this.submitCMEPiece();
	}, this, true);

	var cancel_button = document.createElement('input');
	cancel_button.type = 'button';
	cancel_button.value = CMEFrontMatterDisplay.cancel_text;
	cancel_button.className = 'button cancel-button';
	YAHOO.util.Event.on(cancel_button, 'click', function(e) {
		var base = document.getElementsByTagName('base')[0];
		window.location = base.href + this.cancel_uri;
	}, this, true);

	var footer = document.createElement('div');
	footer.className = 'cme-front-matter-display-footer';
	footer.appendChild(continue_button);
	footer.appendChild(cancel_button);

	var content = document.createElement('div');
	content.className = 'cme-front-matter-display-content';
	content.innerHTML = this.content;

	this.scroll_content = document.createElement('div');
	this.scroll_content.className = 'cme-front-matter-display-scroll-content';
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
	this.overlay.className = 'cme-front-matter-display-overlay';
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

CMEFrontMatterDisplay.prototype.handleResize = function(e)
{
	this.scroll_content.style.height =
		(YAHOO.util.Dom.getViewportHeight() - 200) + 'px';

	this.overlay.style.height = YAHOO.util.Dom.getDocumentHeight() + 'px';
};

CMEFrontMatterDisplay.prototype.close = function()
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

CMEFrontMatterDisplay.prototype.submitCMEPiece = function()
{
	var callback = {
		success: function(o) {},
		failure: function(o) {}
	};

	YAHOO.util.Connect.asyncRequest('POST', this.server, callback);

	this.close();
};

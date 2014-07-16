function CMEFrontMatterDisplay(id, class_name, server, title, content,
	cancel_uri)
{
	this.id          = id;
	this.class_name  = class_name;
	this.server      = server;
	this.content     = content;
	this.title       = title;
	this.cancel_uri  = cancel_uri;
	this.scroll_top  = null;
	this.body_hidden = false;

	this.media_query = (window.matchMedia)
		? window.matchMedia('(min-width: 768px)')
		: { matches: false };

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
	this.header = document.createElement('div');
	this.header.className = 'cme-front-matter-display-header';
	this.header.appendChild(
		document.createTextNode(
			CMEFrontMatterDisplay.confirm_text.replace(/%s/, this.title)
		)
	);

	var continue_button = document.createElement('button');
	continue_button.type = 'button';
	continue_button.appendChild(
		document.createTextNode(CMEFrontMatterDisplay.accept_text)
	);
	continue_button.className =
		'btn btn-primary cme-front-matter-display-accept-button';

	YAHOO.util.Event.on(continue_button, 'click', function(e) {
		continue_button.disabled = true;
		this.submitCMEPiece();
	}, this, true);

	var cancel_button = document.createElement('button');
	cancel_button.type = 'button';
	cancel_button.appendChild(
		document.createTextNode(CMEFrontMatterDisplay.cancel_text)
	);
	cancel_button.className =
		'btn btn-default cme-front-matter-display-cancel-button';

	YAHOO.util.Event.on(cancel_button, 'click', function(e) {
		var base = document.getElementsByTagName('base')[0];
		window.location = base.href + this.cancel_uri;
	}, this, true);

	this.footer = document.createElement('div');
	this.footer.className = 'cme-front-matter-display-footer';
	this.footer.appendChild(continue_button);
	this.footer.appendChild(cancel_button);

	var content = document.createElement('div');
	content.className = 'cme-front-matter-display-content';
	content.innerHTML = this.content;

	this.scroll_content = document.createElement('div');
	this.scroll_content.className = 'cme-front-matter-display-scroll-content';
	this.scroll_content.appendChild(content);
	this.scroll_content.style.height =
		(YAHOO.util.Dom.getViewportHeight() - 200) + 'px';

	var wrapper = document.createElement('div');
	wrapper.className = 'cme-front-matter-display-wrapper';
	wrapper.appendChild(this.header);
	wrapper.appendChild(this.scroll_content);
	wrapper.appendChild(this.footer);

	this.container = document.createElement('div');
	this.container.id = this.id;
	this.container.className = this.class_name;
	this.container.appendChild(wrapper);

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

	this.handleResize();

	SwatZIndexManager.raiseElement(this.overlay);
	SwatZIndexManager.raiseElement(this.container);
};

CMEFrontMatterDisplay.prototype.handleResize = function(e)
{
	var Dom = YAHOO.util.Dom;
	var header_region = Dom.getRegion(this.header);
	var footer_region = Dom.getRegion(this.footer);

	var padding = parseInt(Dom.getStyle(this.container, 'paddingTop')) +
		parseInt(Dom.getStyle(this.container, 'paddingBottom'));

	this.scroll_content.style.height = (
		Dom.getViewportHeight() -
		header_region.height -
		footer_region.height -
		padding
	) + 'px';

	if (this.media_query.matches) {
		if (this.body_hidden) {
			// show all body children again
			var children = Dom.getChildren(document.body);
			for (var i = 0; i < children.length; i++) {
				Dom.removeClass(
					children[i],
					'cme-front-matter-display-hidden'
				);
			}

			// restore scroll position
			window.scrollTo(0, this.scroll_top);

			this.body_hidden = false;
		}
	} else {
		if (!this.body_hidden) {
			// save scroll position for when we close the display
			this.scroll_top = Dom.getDocumentScrollTop();

			// hide all body children
			var children = Dom.getChildren(document.body);
			for (var i = 0; i < children.length; i++) {
				if (children[i] !== this.container) {
					Dom.addClass(
						children[i],
						'cme-front-matter-display-hidden'
					);
				}
			}

			window.scrollTo(0, 0);

			this.body_hidden = true;
		}
	}

	this.overlay.style.height = Dom.getDocumentHeight() + 'px';
};

CMEFrontMatterDisplay.prototype.close = function()
{
	if (this.media_query.matches) {
		var animation = new YAHOO.util.Anim(
			this.container,
			{ opacity: { to: 0 }, top: { to: -100 } },
			0.25,
			YAHOO.util.Easing.easeIn
		);

		animation.onComplete.subscribe(function() {

			YAHOO.util.Dom.addClass(
				this.container,
				'cme-front-matter-display-hidden'
			);

			var animation = new YAHOO.util.Anim(
				this.overlay,
				{ opacity: { to: 0 } },
				0.25,
				YAHOO.util.Easing.easeOut
			);

			animation.onComplete.subscribe(function() {
				YAHOO.util.Dom.addClass(
					this.overlay,
					'cme-front-matter-display-hidden'
				);
			}, this, true);

			animation.animate();

		}, this, true);

		animation.animate();
	} else {
		if (this.body_hidden) {
			// show all body children again
			var children = YAHOO.util.Dom.getChildren(document.body);
			for (var i = 0; i < children.length; i++) {
				YAHOO.util.Dom.removeClass(
					children[i],
					'cme-front-matter-display-hidden'
				);
			}

			// restore scroll position
			window.scrollTo(0, this.scroll_top);

			this.body_hidden = false;
		}

		YAHOO.util.Dom.addClass(
			this.overlay,
			'cme-front-matter-display-hidden'
		);

		YAHOO.util.Dom.addClass(
			this.container,
			'cme-front-matter-display-hidden'
		);
	}

	YAHOO.util.Event.removeListener(window, 'resize', this.handleResize);
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

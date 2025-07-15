function CMEFrontMatterDisplay(
  id,
  class_name,
  server,
  title,
  content,
  cancel_uri
) {
  this.id = id;
  this.class_name = class_name;
  this.server = server;
  this.content = content;
  this.title = title;
  this.cancel_uri = cancel_uri;

  YAHOO.util.Event.onDOMReady(this.init, this, true);
}

CMEFrontMatterDisplay.accept_text =
  'I Have Read the CME Information / Continue';

CMEFrontMatterDisplay.cancel_text = 'Cancel and Return';

CMEFrontMatterDisplay.confirm_text =
  'Before you view %s, please attest to reading the following:';

CMEFrontMatterDisplay.prototype.init = function () {
  this.dialog = new SiteDialog(this.id, {
    dismissable: false,
    class_name: 'cme-front-matter-display'
  });

  // build dialog header
  this.dialog.appendToHeader(
    document.createTextNode(
      CMEFrontMatterDisplay.confirm_text.replace(/%s/, this.title)
    )
  );

  // build dialog body
  var content = document.createElement('div');
  content.className = 'cme-front-matter-display-content';
  content.innerHTML = this.content;
  this.dialog.appendToBody(content);

  // build dialog footer
  var continue_button = document.createElement('button');
  continue_button.setAttribute('type', 'button');
  continue_button.appendChild(
    document.createTextNode(CMEFrontMatterDisplay.accept_text)
  );
  continue_button.className =
    'btn btn-primary cme-front-matter-display-accept-button';

  YAHOO.util.Event.on(
    continue_button,
    'click',
    function (e) {
      continue_button.disabled = true;
      this.submitCMEPiece();
    },
    this,
    true
  );

  var cancel_button = document.createElement('button');
  cancel_button.setAttribute('type', 'button');
  cancel_button.appendChild(
    document.createTextNode(CMEFrontMatterDisplay.cancel_text)
  );
  cancel_button.className =
    'btn btn-default cme-front-matter-display-cancel-button';

  YAHOO.util.Event.on(
    cancel_button,
    'click',
    function (e) {
      var base = document.getElementsByTagName('base')[0];
      window.location = base.href + this.cancel_uri;
    },
    this,
    true
  );

  this.dialog.appendToFooter(continue_button);
  this.dialog.appendToFooter(cancel_button);

  // open dialog by default
  this.dialog.open();
};

CMEFrontMatterDisplay.prototype.submitCMEPiece = function () {
  var callback = {
    success: function (o) {},
    failure: function (o) {}
  };

  YAHOO.util.Connect.asyncRequest('POST', this.server, callback);

  this.dialog.closeWithAnimation();
};

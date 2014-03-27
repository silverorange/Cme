<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatYUI.php';
require_once 'CME/CME.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEDisclosure extends SwatControl
{
	// {{{ public properties

	/**
	 * @var string
	 */
	public $content;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $server;

	/**
	 * @var string
	 */
	public $cancel_uri;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$yui = new SwatYUI(array('dom', 'event', 'animation', 'connection'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript(
			'packages/swat/javascript/swat-z-index-manager.js'
		);

		$this->addJavaScript('javascript/cme-disclosure.js');
		$this->html_head_entry_set->addEntry('styles/cme-disclosure.css');
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible) {
			return;
		}

		parent::display();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	protected function getCSSClassNames()
	{
		return array_merge(
			array('cme-disclosure'),
			parent::getCSSClassNames()
		);
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		static $shown = false;

		if (!$shown) {
			$javascript = $this->getInlineJavaScriptTranslations();
			$shown = true;
		} else {
			$javascript = '';
		}

		$javascript.= sprintf(
			'%s_obj = new %s(%s, %s, %s, %s, %s, %s);',
			$this->id,
			$this->getJavaScriptClassName(),
			SwatString::quoteJavaScriptString($this->id),
			SwatString::quoteJavaScriptString($this->getCSSClassString()),
			SwatString::quoteJavaScriptString($this->server),
			SwatString::quoteJavaScriptString($this->title),
			SwatString::quoteJavaScriptString($this->content),
			SwatString::quoteJavaScriptString($this->cancel_uri)
		);

		return $javascript;
	}

	// }}}
	// {{{ protected function getInlineJavaScriptTranslations()

	protected function getInlineJavaScriptTranslations()
	{
		$accept_text = CME::_('I Have Read the CME Information / Continue');
		$cancel_text = CME::_('Cancel and Return');
		$confirm_text = CME::_(
			'Before you view %s, please attest to reading the following:'
		);

		return sprintf(
			"CMEDisclosure.accept_text = %s;\n".
			"CMEDisclosure.cancel_text = %s;\n".
			"CMEDisclosure.confirm_text = %s;\n",
			SwatString::quoteJavaScriptString($acept_text),
			SwatString::quoteJavaScriptString($cancel_text),
			SwatString::quoteJavaScriptString($confirm_text)
		);
	}

	// }}}
	// {{{ protected function getJavaScriptClassName()

	protected function getJavaScriptClassName()
	{
		return 'CMEDisclosure';
	}

	// }}}
}

?>

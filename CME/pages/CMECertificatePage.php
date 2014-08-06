<?php

require_once 'Site/pages/SiteUiPage.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMEAccountEarnedCMECreditWrapper.php';
require_once 'CME/dataobjects/CMEProviderWrapper.php';

/**
 * Page for generating and viewing certificates
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMECertificatePage extends SiteUiPage
{
	// {{{ protected properties

	/**
	 * @var CMEAccountEarnedCMECreditWrapper
	 */
	protected $credits;

	/**
	 * @var array
	 */
	protected $credits_by_provider;

	/**
	 * @var CMEAccountEarnedCMECreditWrapper
	 */
	protected $selected_credits;

	/**
	 * @var array
	 */
	protected $selected_credits_by_provider;

	/**
	 * @var boolean
	 */
	protected $has_pre_selection = false;

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'CME/pages/cme-certificate.xml';
	}

	// }}}
	// {{{ protected function getCreditsByProvider()

	protected function getCreditsByProvider($shortname)
	{
		if (isset($this->credits_by_provider[$shortname])) {
			$credits = $this->credits_by_provider[$shortname];
		} else {
			$wrapper = SwatDBClassMap::get('CMEAccountEarnedCMECreditWrapper');
			$credits = new $wrapper();
			$credits->setDatabase($this->app->db);
		}

		return $credits;
	}

	// }}}
	// {{{ protected function getSelectedCreditsByProvider()

	protected function getSelectedCreditsByProvider($shortname)
	{
		if (isset($this->selected_credits_by_provider[$shortname])) {
			$credits = $this->selected_credits_by_provider[$shortname];
		} else {
			$wrapper = SwatDBClassMap::get('CMEAccountEarnedCMECreditWrapper');
			$credits = new $wrapper();
			$credits->setDatabase($this->app->db);
		}

		return $credits;
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		if (!$this->app->session->isLoggedIn()) {
			$uri = sprintf(
				'%s?relocate=%s',
				$this->app->config->uri->account_login,
				$this->source
			);

			$this->app->relocate($uri);
		}
		$account = $this->app->session->account;

		$key = 'cme-hours-'.$account->id;

		$hours = $this->app->getCacheValue($key, 'cme-hours');
		if ($hours === false) {
			$hours = $account->getEarnedCMECreditHours();
			$this->app->addCacheValue($hours, $key, 'cme-hours');
		}

		// If no hours are earned and no CME access is available, go to account
		// details. Not using strict equality because $hours can be a float
		// value.
		if (!$account->hasCMEAccess() && $hours == 0) {
			$this->app->relocate('account');
		}

		parent::init();
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->initCredits();
		$this->initList();
	}

	// }}}
	// {{{ protected function initCredits()

	protected function initCredits()
	{
		$account = $this->app->session->account;
		$this->credits = $account->earned_cme_credits;
		$this->credits_by_provider = array();

		$wrapper = SwatDBClassMap::get('CMEAccountEarnedCMECreditWrapper');

		foreach ($this->credits as $credit) {
			$provider = $credit->credit->front_matter->provider->shortname;
			if (!isset($this->credits_by_provider[$provider])) {
				$this->credits_by_provider[$provider] = new $wrapper();
				$this->credits_by_provider[$provider]->setDatabase(
					$this->app->db
				);
			}
			$this->credits_by_provider[$provider]->add($credit);
		}
	}

	// }}}
	// {{{ protected function initList()

	protected function initList()
	{
		$values = array();
		$list = $this->ui->getWidget('credits');

		foreach ($this->credits as $credit) {
			$list->addOption(
				$this->getListOption($credit),
				$this->getListOptionMetaData($credit)
			);

			if ($this->isPreSelected($credit)) {
				$this->has_pre_selection = true;
				$values[] = $credit->id;
			}
		}

		$list->values = $values;
	}

	// }}}
	// {{{ protected function getListOption()

	protected function getListOption(CMEAccountEarnedCMECredit $credit)
	{
		return new SwatOption(
			$credit->id,
			$this->getListOptionTitle($credit),
			'text/xml'
		);
	}

	// }}}
	// {{{ protected function getListOptionMetaData()

	protected function getListOptionMetaData(CMEAccountEarnedCMECredit $credit)
	{
		return array();
	}

	// }}}
	// {{{ protected function getListOptionTitle()

	protected function getListOptionTitle(CMEAccountEarnedCMECredit $credit)
	{
		ob_start();

		$locale = SwatI18NLocale::get();

		$title_span = new SwatHtmlTag('span');
		$title_span->class = 'title';
		$title_span->setContent($this->getCreditTitle($credit));
		$title_span->display();

		$formatted_provider_credit_title = sprintf(
			'<em>%s</em>',
			SwatString::minimizeEntities(
				$credit->credit->front_matter->provider->credit_title
			)
		);

		$hours_span = new SwatHtmlTag('span');
		$hours_span->class = 'hours';
		$hours_span->setContent(
			sprintf(
				CME::_('%s %s from %s'),
				SwatString::minimizeEntities(
					$locale->formatNumber($credit->credit->hours)
				),
				$formatted_provider_credit_title,
				SwatString::minimizeEntities(
					$credit->credit->front_matter->provider->title
				)
			),
			'text/xml'
		);
		$hours_span->display();

		$details = $this->getCreditDetails($credit);
		if ($details != '') {
			$details_span = new SwatHtmlTag('span');
			$details_span->class = 'details';
			$details_span->setContent($details);
			$details_span->display();
		}

		return ob_get_clean();
	}

	// }}}
	// {{{ abstract protected function getCreditTitle()

	abstract protected function getCreditTitle(
		CMEAccountEarnedCMECredit $credit);

	// }}}
	// {{{ protected function getCreditDetails()

	protected function getCreditDetails(CMEAccountEarnedCMECredit $credit)
	{
		return '';
	}

	// }}}
	// {{{ protected function isPreSelected()

	protected function isPreSelected(CMEAccountEarnedCMECredit $credit)
	{
		return false;
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		$list = $this->ui->getWidget('credits')->values;

		$this->selected_credits_by_provider = array();

		$wrapper = SwatDBClassMap::get('CMEAccountEarnedCMECreditWrapper');

		$this->selected_credits = new $wrapper();
		$this->selected_credits->setDatabase($this->app->db);

		$has_credit = false;

		foreach ($this->credits as $credit) {
			if (in_array($credit->id, $list)) {
				$has_credit = true;
				$this->selected_credits->add($credit);
				$provider = $credit->credit->front_matter->provider->shortname;
				if (!isset($this->selected_credits_by_provider[$provider])) {
					$this->selected_credits_by_provider[$provider] =
						new $wrapper();

					$this->selected_credits_by_provider[$provider]->setDatabase(
						$this->app->db
					);
				}
				$this->selected_credits_by_provider[$provider]->add($credit);
			}
		}

		$form = $this->ui->getWidget('certificate_form');
		if ($form->isProcessed() && !$has_credit) {
			$this->ui->getWidget('message_display')->add(
				new SwatMessage(
					CME::_('No credits were selected to print.')
				),
				SwatMessageDisplay::DISMISS_OFF
			);
		}
	}

	// }}}
	// {{{ protected function isProcessed()

	protected function isProcessed()
	{
		$form = $this->ui->getWidget('certificate_form');
		return ($this->has_pre_selection || $form->isProcessed());
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('certificate_form');
		$form->action = $this->getSource();

		if ($this->isProcessed()) {
			$this->buildCertificates();
			ob_start();
			Swat::displayInlineJavaScript($this->getInlineJavaScript());
			$this->ui->getWidget('certificate')->content = ob_get_clean();
		}
	}

	// }}}
	// {{{ protected function buildContent()

	protected function buildContent()
	{
		$content = $this->layout->data->content;
		$this->layout->data->content = '';

		$this->ui->getWidget('article_bodytext')->content = $content;
		$this->ui->getWidget('article_bodytext')->content_type = 'text/xml';

		parent::buildContent();
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		parent::buildTitle();
		$this->layout->data->title = CME::_('Print CME Certificates');
	}

	// }}}
	// {{{ abstract protected function buildCertificates()

	abstract protected function buildCertificates();

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		return <<<JAVASCRIPT
		YAHOO.util.Event.on(window, 'load', function() {
			var certificates = YAHOO.util.Dom.getElementsByClassName(
				'cme-certificate',
				'div'
			);

			if (certificates.length > 0) {
				window.print();
			}
		});
JAVASCRIPT;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addBodyClass('cme-certificate-page');

		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(
			'packages/cme/javascript/cme-certificate-page.js'
		);
	}

	// }}}
}

?>

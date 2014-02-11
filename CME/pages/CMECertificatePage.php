<?php

require_once 'Site/pages/SiteUiPage.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMECreditWrapper.php';
require_once 'CME/dataobjects/CMECreditTypeWrapper.php';

/**
 * Page for generating and viewing certificates
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMECertificatePage extends SiteUiPage
{
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'CME/pages/cme-certificate.xml';
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		if (!$this->app->session->isLoggedIn()) {
			$uri = sprintf('account/login?relocate=%s', $this->source);
			$this->app->relocate($uri);
		}
		$account = $this->app->session->account;

		$key = 'cme-hours-'.$account->id;

		$hours = $this->app->getCacheValue($key, 'cme-hours');
		if ($hours === false) {
			$hours = $account->getCMECreditHours();
			$this->app->addCacheValue($hours, $key, 'cme-hours');
		}

		// If no hours are earned and no CME access is available, go to account
		// details
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

		$wrapper = SwatDBClassMap::get('CMECreditWrapper');
		$this->credits = new $wrapper();

		foreach ($account->cme_credits as $credit) {
			if ($account->isCertificateEarned($credit)) {
				$this->credits->add($credit);
			}
		}

		$this->credits = $earned_credits;
	}

	// }}}
	// {{{ protected function initList()

	protected function initList()
	{
		$values = array();
		$list = $this->ui->getWidget('credits');

		foreach ($this->credits as $credit) {
			$option = new SwatOption(
				$credit->id,
				$this->getListOptionTitle($credit),
				'text/xml'
			);

			$list->addOption($option);
		}

		$list->values = $values;
	}

	// }}}
	// {{{ protected function getListOptionTitle()

	protected function getListOptionTitle(CMECredit $credit)
	{
		ob_start();

		$locale = SwatI18NLocale::get();

		$title_span = new SwatHtmlTag('span');
		$title_span->class = 'title';
		$title_span->setContent($this->getCreditTitle($credit));
		$title_span->display();

		$hours_span = new SwatHtmlTag('span');
		$hours_span->class = 'hours';
		$hours_span->setContent(
			sprintf(
				CME::_('%s <em>AMA PRA Category 1 Credit(s)</em>™ from %s'),
				SwatString::minimizeEntities(
					$locale->formatNumber($credit->hours)
				),
				SwatString::minimizeEntities(
					$credit->credit_type->title
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

	abstract protected function getCreditTitle(CMECredit $credit)

	// }}}
	// {{{ protected function getCreditDetails()

	protected function getCreditDetails(CMECredit $credit)
	{
		return '';
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('certificate_form');
		$form->action = $this->getSource();

		if ($form->isProcessed()) {
			ob_start();
			$this->displayCertificates();
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
		$this->layout->data->html_title = sprintf(
			CME::_('Print CME Certificates - %s'),
			$this->app->getHtmlTitle()
		);
	}

	// }}}
	// {{{ abstract protected function displayCertificates()

	abstract protected function displayCertificates();

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		return <<<JAVASCRIPT
		YAHOO.util.Event.on(window, 'load', function() {
			var certificates = YAHOO.util.Dom.getElementsByClassName(
				'cme-certificate', 'div');

			if (certificates.length > 0) {
				window.print();
			}
		});
JAVASCRIPT;
	}

	// }}}
	// {{{ protected function getCompleteDate()

	protected function getCompleteDate(CMECredit $credit)
	{
		$account = $this->app->session->account;

		// assume evaluation taken after quiz
		if ($credit->evaluation instanceof InquisitionInquisition) {
			$response = $credit->evaluation->getResponseByAccount($account);
			$date = clone $response->complete_date;
		} elseif ($credit->quiz instanceof InquisitionInquisition) {
			$response = $credit->quiz->getResponseByAccount($account);
			$date = clone $response->complete_date;
		} else {
			$date = new SwatDate();
		}

		return $date;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addBodyClass('cme-account-certificate');

		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());
	}

	// }}}
}

?>

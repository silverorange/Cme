<?php

require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Site/SiteReplacementMarkerMailMessage.php';
require_once 'Site/dataobjects/SiteAccount.php';
require_once 'CME/CME.php';
require_once 'CME/dataobjects/CMEFrontMatter.php';

/**
 * @package   CME
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEFrontMatterCompleteMailMessage extends
	SiteReplacementMarkerMailMessage
{
	// {{{ protected properties

	/**
	 * The account this message is intended for
	 *
	 * @var SiteAccount
	 */
	protected $account;

	/**
	 * @var CMEFrontMatter
	 */
	protected $front_matter;

	/**
	 * @var CMEQuizResponse
	 */
	protected $response;

	// }}}
	// {{{ public function __construct()

	/**
	 * @param SiteApplication $app
	 * @param SiteAccount $account the account to create the email for.
	 * @param CMEFrontMatter $front_matter
	 * @param InquisitionResponse $response
	 */
	public function __construct(SiteApplication $app, SiteAccount $account,
		CMEFrontMatter $front_matter, InquisitionResponse $response)
	{
		$this->account      = $account;
		$this->front_matter = $front_matter;
		$this->response     = $response;

		if ($this->response->complete_date === null) {
			throw new SiteMailException(
				sprintf(
					'Quiz is not completed for "%s".',
					$account->email
				)
			);
		}

		parent::__construct($app, $account);

		$this->from_name    = $this->getFromName();
		$this->from_address = $this->getFromAddress();
		$this->to_name      = $account->getFullName();
		$this->to_address   = $account->email;
	}

	// }}}
	// {{{ abstract protected function getCertificateLinkURI()

	abstract protected function getCertificateLinkURI();

	// }}}
	// {{{ protected function getFromName()

	protected function getFromName()
	{
		return $this->app->config->site->title;
	}

	// }}}
	// {{{ protected function getFromAddress()

	protected function getFromAddress()
	{
		return $this->app->config->email->service_address;
	}

	// }}}
	// {{{ protected function getSubject()

	protected function getSubject()
	{
		return sprintf(
			CME::_('%s Quiz Completed'),
			$this->front_matter->getProviderTitleList()
		);
	}

	// }}}
	// {{{ protected function getBodyText()

	protected function getBodyText()
	{
		if ($this->response->isPassed()) {
			$bodytext = $this->front_matter->email_content_pass;
		} else {
			$bodytext = $this->front_matter->email_content_fail;
		}

		return $bodytext;
	}

	// }}}
	// {{{ protected function getReplacementMarkerText()

	protected function getReplacementMarkerText($marker_id)
	{
		$locale = SwatI18NLocale::get();

		switch ($marker_id) {
		case 'account-full-name':
			return $this->account->getFullName();

		case 'cme-certificate-link':
			return $this->getCertificateLinkURI();

		case 'quiz-passing-grade':
			return $locale->formatNumber(
				$this->front_matter->passing_grade * 100
			).'%';

		case 'quiz-grade':
			$grade = $this->response->getGrade();
			return $locale->formatNumber(round($grade * 1000) / 10).'%';

		default:
			return parent::getReplacementMarkerText($marker_id);
		}
	}

	// }}}
}

?>

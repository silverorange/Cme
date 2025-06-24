<?php

/**
 * @package   CME
 * @coypright 2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMECertificate extends SwatControl
{


	/**
	 * @var CMEAccountEarnedCMECreditWrapper
	 */
	protected $credits = null;

	/**
	 * @var SiteApplication
	 */
	protected $app = null;

	/**
	 * @var CMEAccount
	 */
	protected $account = null;




	public function setApplication(SiteApplication $app)
	{
		$this->app = $app;
	}




	public function setAccount(CMEAccount $account)
	{
		$this->account = $account;
	}




	public function setEarnedCredits(CMEAccountEarnedCMECreditWrapper $credits)
	{
		$this->credits = $credits;
	}




	public function display()
	{
		if (!$this->visible) {
			return;
		}

		if (!$this->app instanceof SiteApplication) {
			throw new SwatException(
				'Application must be set to display certificate.'
			);
		}

		if (!$this->account instanceof CMEAccount) {
			throw new SwatException(
				'Account must be set to display certificate.'
			);
		}

		if (!$this->credits instanceof CMEAccountEarnedCMECreditWrapper) {
			throw new SwatException(
				'Earned credits must be set to display certificate.'
			);
		}

		parent::display();

		$certificate_div = new SwatHtmlTag('div');
		$certificate_div->id = $this->id;
		$certificate_div->class = $this->getCSSClassString();
		$certificate_div->open();

		$this->displayCertificateContent();

		$certificate_div->close();
	}




	abstract protected function displayCertificateContent();




	abstract protected function isPhysician();




	protected function getCSSClassNames()
	{
		return array_merge(
			array('cme-certificate'),
			parent::getCSSClassNames()
		);
	}


}

?>

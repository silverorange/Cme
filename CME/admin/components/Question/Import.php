<?php

/**
 * @package   CME
 * @copyright 2014-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuestionImport extends InquisitionQuestionImport
{


	/**
	 * @var CMEQuestionHelper
	 */
	protected $helper;



	// init phase


	protected function initInternal()
	{
		parent::initInternal();

		$this->helper = $this->getQuestionHelper();
		$this->helper->initInternal();
	}




	protected function getQuestionHelper()
	{
		return new CMEQuestionHelper($this->app, $this->inquisition);
	}



	// build phase


	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->helper->buildNavBar($this->navbar);
	}


}

?>

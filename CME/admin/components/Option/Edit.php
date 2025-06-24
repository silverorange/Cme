<?php

/**
 * @package   CME
 * @copyright 2014-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEOptionEdit extends InquisitionOptionEdit
{


	/**
	 * @var CMEOptionHelper
	 */
	protected $helper;



	// init phase


	protected function initInternal()
	{
		parent::initInternal();

		$this->helper = $this->getOptionHelper();
		$this->helper->initInternal();
	}




	protected function getOptionHelper()
	{
		$question_helper = new CMEQuestionHelper(
			$this->app,
			$this->inquisition
		);

		return new CMEOptionHelper(
			$this->app,
			$question_helper,
			$this->question
		);
	}



	// build phase


	protected function buildNavBar()
	{
		parent::buildNavBar();

		// put add/edit title entry at the end
		$title = $this->navbar->popEntry();

		// Add dummy entry. The CMEOptionHelper will remove this. All other
		// option admin components have a details component in the nav bar.
		if ($this->isNew()) {
			$this->navbar->createEntry('');
		}

		$this->helper->buildNavBar($this->navbar);

		// remove dummy entry.
		if ($this->isNew()) {
			$this->navbar->popEntry();
		}

		$this->navbar->addEntry($title);
	}


}

?>

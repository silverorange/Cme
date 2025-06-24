<?php

/**
 * Question hint edit page for inquisitions
 *
 * @package   CME
 * @copyright 2013-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuestionHintEdit extends InquisitionQuestionHintEdit
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




	protected function initInquisition()
	{
		parent::initInquisition();

		if (!$this->inquisition instanceof InquisitionInquisition) {
			// if we got here from the question index, load the inquisition
			// from the binding as we only have one inquisition per question
			$sql = sprintf(
				'select inquisition from InquisitionInquisitionQuestionBinding
				where question = %s',
				$this->app->db->quote($this->question->id)
			);

			$inquisition_id = SwatDB::queryOne($this->app->db, $sql);

			$this->inquisition = $this->loadInquisition($inquisition_id);
		}
	}




	protected function getQuestionHelper()
	{
		return new CMEQuestionHelper($this->app, $this->inquisition);
	}



	// build phase


	protected function buildNavBar()
	{
		parent::buildNavBar();

		// put edit entry at the end
		$title = $this->navbar->popEntry();

		$this->helper->buildNavBar($this->navbar);

		$this->navbar->addEntry($title);
	}


}

?>

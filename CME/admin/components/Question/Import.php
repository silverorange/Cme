<?php

require_once 'Inquisition/admin/components/Question/Import.php';
require_once 'CME/admin/components/Question/include/CMEQuestionHelper.php';

/**
 * @package   CME
 * @copyright 2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuestionImport extends InquisitionQuestionImport
{
	// {{{ protected properties

	/**
	 * @var CMEQuestionHelper
	 */
	protected $helper;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->helper = $this->getQuestionHelper();
		$this->helper->initInternal();
	}

	// }}}
	// {{{ protected function getQuestionHelper()

	protected function getQuestionHelper()
	{
		return new CMEQuestionHelper($this->app, $this->inquisition);
	}

	// }}}

	// build phase
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->helper->buildNavBar($this->navbar);
	}

	// }}}
}

?>

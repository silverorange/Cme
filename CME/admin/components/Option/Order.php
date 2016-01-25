<?php

require_once 'Inquisition/admin/components/Option/Order.php';
require_once 'CME/admin/components/Option/include/CMEOptionHelper.php';

/**
 * @package   CME
 * @copyright 2014-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEOptionOrder extends InquisitionOptionOrder
{
	// {{{ protected properties

	/**
	 * @var CMEOptionHelper
	 */
	protected $helper;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->helper = $this->getOptionHelper();
		$this->helper->initInternal();
	}

	// }}}
	// {{{ protected function getOptionHelper()

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

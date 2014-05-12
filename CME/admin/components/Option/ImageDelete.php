<?php

require_once 'Inquisition/admin/components/Option/ImageDelete.php';
require_once 'CME/admin/components/Option/include/CMEOptionHelper.php';

/**
 * Delete confirmation page for question images
 *
 * @package   CME
 * @copyright 2012-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEOptionImageDelete extends InquisitionOptionImageDelete
{
	// {{{ protected properties

	/**
	 * @var CMEOptionHelper
	 */
	protected $helper;

	// }}}

	// helper methods
	// {{{ public function setInquisition()

	public function setInquisition(InquisitionInquisition $inquisition)
	{
		parent::setInquisition($inquisition);

		$this->helper = $this->getOptionHelper();
		$this->helper->initInternal();
	}

	// }}}
	// {{{ abstract protected function getOptionHelper()

	abstract protected function getOptionHelper();

	// }}}

	// build phase
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		// put edit entry at the end
		$title = $this->navbar->popEntry();

		// remove inquisition defined in inquisition package
		$question = $this->navbar->popEntry();
		$this->navbar->popEntry();
		$this->navbar->addEntry($question);

		$this->helper->buildNavBar($this->navbar);

		$this->navbar->addEntry($title);
	}

	// }}}
}

?>

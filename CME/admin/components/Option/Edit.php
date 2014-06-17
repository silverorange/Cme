<?php

require_once 'Inquisition/admin/components/Option/Edit.php';
require_once 'CME/admin/components/Option/include/CMEOptionHelper.php';

/**
 * @package   CME
 * @copyright 2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
abstract class CMEOptionEdit extends InquisitionOptionEdit
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
	// {{{ abstract protected function getOptionHelper()

	abstract protected function getOptionHelper();

	// }}}

	// build phase
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		// put add/edit title entry at the end
		$title = $this->navbar->popEntry();

		// Add dummy entry. The CMEOptionHelper will remove this. All other
		// option admin components have a details component in the nav bar.
		$this->navbar->createEntry('');

		$this->helper->buildNavBar($this->navbar);

		// remove dummy entry.
		$this->navbar->popEntry();

		$this->navbar->addEntry($title);
	}

	// }}}
}

?>

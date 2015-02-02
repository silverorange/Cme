<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'CME/dataobjects/CMEAccountCMEProgress.php';

/**
 * @package   CME
 * @copyright 2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @see       CMEAccountCMEProgress
 */
class CMEAccountCMEProgressWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get(
			'CMEAccountCMEProgress'
		);
	}

	// }}}
}

?>

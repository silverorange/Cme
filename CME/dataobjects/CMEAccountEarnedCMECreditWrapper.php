<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'CME/dataobjects/CMEAccountEarnedCMECredit.php';

/**
 * @package   CME
 * @copyright 2012-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @see       CMEAccountEarnedCMECredit
 */
class CMEAccountEarnedCMECreditWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get(
			'CMEAccountEarnedCMECredit'
		);
	}

	// }}}
}

?>

<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'CME/dataobjects/CMECreditType.php';

/**
 * A recordset wrapper class for CMECreditType objects
 *
 * @package   CME
 * @copyright 2013-2014 silverorange
 * @see       CMECreditType
 */
class CMECreditTypeWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('CMECreditType');
		$this->index_field = 'id';
	}

	// }}}
}

?>

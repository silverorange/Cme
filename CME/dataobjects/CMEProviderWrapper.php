<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'CME/dataobjects/CMEProvider.php';

/**
 * A recordset wrapper class for CMEProvider objects
 *
 * @package   CME
 * @copyright 2013-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @see       CMEProvider
 */
class CMEProviderWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('CMEProvider');
		$this->index_field = 'id';
	}

	// }}}
}

?>

<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'CME/dataobjects/CMEFrontMatter.php';

/**
 * A recordset wrapper class for CMEFrontMatter objects
 *
 * @package   CME
 * @copyright 2013-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @see       CMEFrontMatter
 */
class CMEFrontMatterWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('CMEFrontMatter');
		$this->index_field = 'id';
	}

	// }}}
}

?>

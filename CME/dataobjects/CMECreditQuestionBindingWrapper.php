<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'CME/dataobjects/CMECreditQuestionBinding.php';

/**
 * A recordset wrapper class for CMECreditQuestionBinding objects
 *
 * @package   CME
 * @copyright 2015 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       CMECreditQuestionBinding
 */
class CMECreditQuestionBindingWrapper
	extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('CMECreditQuestionBinding');

		$this->index_field = 'id';
	}

	// }}}
}

?>

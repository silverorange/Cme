<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'CME/dataobjects/CMEEvaluationReport.php';

/**
 * A recordset wrapper class for CMEEvaluationReport objects
 *
 * @package   CME
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @see       CMEEvaluationReport
 */
class CMEEvaluationReportWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('CMEEvaluationReport');
		$this->index_field = 'id';
	}

	// }}}
}

?>

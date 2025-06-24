<?php

/**
 * Special wrapper needed because {@link SwatDBClassMap} is not used for
 * evaluations
 *
 * This is because quiz and evaluation responses share the same database table.
 *
 * @package   CME
 * @copyright 2012-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @see       CMEEvaluationResponse
 */
class CMEEvaluationResponseWrapper extends InquisitionResponseWrapper
{


	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('CMEEvaluationResponse');
	}


}

?>

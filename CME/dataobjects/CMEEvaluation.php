<?php

require_once 'Inquisition/dataobjects/InquisitionInquisition.php';
require_once 'CME/dataobjects/CMEEvaluationResponseWrapper.php';

/**
 * An evaluation
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEEvaluation extends InquisitionInquisition
{
	// {{{ protected function getResponseWrapperClass()

	protected function getResponseWrapperClass()
	{
		return 'CMEEvaluationResponseWrapper';
	}

	// }}}
}

?>

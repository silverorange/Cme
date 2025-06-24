<?php

/**
 * An evaluation
 *
 * @package   CME
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEEvaluation extends InquisitionInquisition
{


	protected function getResponseWrapperClass()
	{
		return 'CMEEvaluationResponseWrapper';
	}


}

?>

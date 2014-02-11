<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Inquisition/dataobjects/InquisitionInquisitionWrapper.php';
require_once 'CME/dataobjects/CMEQuiz.php';

/**
 * A recordset wrapper class for CMEQuiz objects
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @see       CMEQuiz
 */
class CMEQuizWrapper extends InquisitionInquisitionWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = 'CMEQuiz';
		$this->index_field = 'id';
	}

	// }}}
}

?>

<?php

require_once 'Inquisition/dataobjects/InquisitionInquisition.php';

/**
 * An evaluation
 *
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEEvaluation extends InquisitionInquisition
{
	// {{{ public properties

	/**
	 * @var string
	 */
	public $description;

	/**
	 * @var integer
	 */
	public $passing_grade;

	/**
	 * @var string
	 */
	public $email_content_pass;

	/**
	 * @var string
	 */
	public $email_content_fail;

	/**
	 * @var string
	 */
	public $enabled;

	// }}}
}

?>

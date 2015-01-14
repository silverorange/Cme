<?php

require_once 'CME/dataobjects/CMEAccount.php';
require_once 'CME/dataobjects/CMECredit.php';

/**
 * CME specific Account object
 *
 * @package   CME
 * @copyright 2011-2015 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEAccountEarnedCMECredit extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var SwatDate
	 */
	public $earned_date;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'AccountEarnedCMECredit';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty(
			'account',
			SwatDBClassMap::get('CMEAccount')
		);

		$this->registerInternalProperty(
			'credit',
			SwatDBClassMap::get('CMECredit')
		);

		$this->registerDateProperty('earned_date');
	}

	// }}}
}

?>

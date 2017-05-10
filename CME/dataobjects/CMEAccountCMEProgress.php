<?php


/**
 * CME progress for an account
 *
 * @package   CME
 * @copyright 2015-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEAccountCMEProgress extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'AccountCMEProgress';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty(
			'account',
			SwatDBClassMap::get('CMEAccount')
		);

		$this->registerInternalProperty(
			'quiz',
			SwatDBClassMap::get('CMEQuiz')
		);

		$this->registerInternalProperty(
			'evaluation',
			SwatDBClassMap::get('CMEEvaluation')
		);
	}

	// }}}
}

?>

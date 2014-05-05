<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'CME/dataobjects/CMEQuiz.php';
require_once 'CME/dataobjects/CMEFrontMatter.php';

/**
 * @package   CME
 * @copyright 2013-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMECredit extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var float
	 */
	public $hours;

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
	 * @var boolean
	 */
	public $resettable;

	// }}}
	// {{{ abstract protected function getQuizLink()

	abstract protected function getQuizLink();

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'CMECredit';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty(
			'front_matter',
			SwatDBClassMap::get('CMEFrontMatter')
		);

		$this->registerInternalProperty(
			'quiz',
			SwatDBClassMap::get('CMEQuiz')
		);
	}

	// }}}
}

?>

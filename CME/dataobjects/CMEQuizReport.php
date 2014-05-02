<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'CME/dataobjects/CMEProvider.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuizReport extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var string
	 */
	public $filename;

	/**
	 * @var SwatDate
	 */
	public $quarter;

	/**
	 * @var SwatDate
	 */
	public $createdate;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $file_base;

	// }}}
	// {{{ public function setFileBase()

	public function setFileBase($file_base)
	{
		$this->file_base = $file_base;
	}

	// }}}
	// {{{ public function getFileDirectory()

	public function getFileDirectory()
	{
		$path = array(
			$this->file_base,
			'reports'
		);

		return implode(DIRECTORY_SEPARATOR, $path);
	}

	// }}}
	// {{{ public function getFilePath()

	public function getFilePath()
	{
		$path = array(
			$this->getFileDirectory(),
			$this->filename
		);

		return implode(DIRECTORY_SEPARATOR, $path);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->table = 'QuizReport';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty(
			'provider',
			SwatDBClassMap::get('CMEProvider')
		);

		$this->registerDateProperty('quarter');
		$this->registerDateProperty('createdate');
	}

	// }}}
}

?>

<?php

/**
 * @package   CME
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuizReport extends SwatDBDataObject
{


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




	/**
	 * @var string
	 */
	protected $file_base;




	public function setFileBase($file_base)
	{
		$this->file_base = $file_base;
	}




	public function getFileDirectory()
	{
		$path = array(
			$this->file_base,
			'reports'
		);

		return implode(DIRECTORY_SEPARATOR, $path);
	}




	public function getFilePath()
	{
		$path = array(
			$this->getFileDirectory(),
			$this->filename
		);

		return implode(DIRECTORY_SEPARATOR, $path);
	}




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


}

?>

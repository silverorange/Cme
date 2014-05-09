<?php

require_once 'Inquisition/admin/components/Inquisition/Details.php';
require_once 'CME/dataobjects/CMEFrontMatterWrapper.php';

/**
 * @package   CME
 * @copyright 2014 silverorange
 */
abstract class CMEEvaluationDetails extends InquisitionInquisitionDetails
{
	// {{{ protected properties

	/**
	 * @var CMEFrontMatter
	 */
	protected $front_matter;

	// }}}
	// {{{ abstract protected function getTitle()

	protected function getTitle()
	{
		return sprintf(
			CME::_('%s Evaluation'),
			$this->front_matter->provider->title
		);
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->initFrontMatter();
	}

	// }}}
	// {{{ protected function initFrontMatter()

	protected function initFrontMatter()
	{
		$sql = sprintf(
			'select * from CMEFrontMatter where evaluation = %s',
			$this->app->db->quote($this->inquisition->id, 'integer')
		);

		$this->front_matter = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMEFrontMatterWrapper')
		)->getFirst();

		if (!$this->front_matter instanceof CMEFrontMatter) {
			throw new AdminNotFoundException(
				'Evaluation with id of %s not found.',
				$this->id
			);
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('details_frame')->title = $this->getTitle();

		$view = $this->ui->getWidget('details_view');
		$view->getField('title')->visible = false;
		$view->getField('createdate')->visible = false;
	}

	// }}}
	// {{{ protected function buildToolbars()

	protected function buildToolbars()
	{
		parent::buildToolbars();

		$this->ui->getWidget('details_toolbar')->visible = false;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntry();
		$this->navbar->createEntry($this->getTitle());
	}

	// }}}
	// {{{ protected function getQuestionDetailsStore()

	protected function getQuestionDetailsStore(
		InquisitionInquisitionQuestionBinding $question_binding)
	{
		$ds = parent::getQuestionDetailsStore($question_binding);
		$ds->credit = $this->credit;
		return $ds;
	}

	// }}}
}

?>

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
				sprintf(
					'Evaluation with id of %s not found.',
					$this->id
				)
			);
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = $this->getTitle();

		// Hide details view. All details are displayed on previous screen with
		// front matter.
		$view = $this->ui->getWidget('details_view');
		$view->visible = false;

		// move question frame to top-level
		$question_frame = $this->ui->getWidget('question_frame');
		$question_frame->visible = false;
		foreach ($question_frame->getChildren() as $child) {
			$question_frame->remove($child);
			$details_frame->packEnd($child);
		}
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
}

?>

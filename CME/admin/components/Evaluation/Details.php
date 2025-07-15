<?php

/**
 * @copyright 2014-2016 silverorange
 */
class CMEEvaluationDetails extends InquisitionInquisitionDetails
{
    /**
     * @var CMEFrontMatter
     */
    protected $front_matter;

    protected function getTitle()
    {
        return sprintf(
            CME::_('%s Evaluation'),
            $this->front_matter->getProviderTitleList()
        );
    }

    // init phase

    protected function initInternal()
    {
        parent::initInternal();
        $this->initFrontMatter();

        // Hide question import link as question importer only works with
        // Inquisitions that have correct answers.
        $this->ui->getWidget('question_import')->visible = false;
    }

    protected function initFrontMatter()
    {
        $sql = sprintf(
            'select * from CMEFrontMatter where evaluation = %s',
            $this->app->db->quote($this->inquisition->id, 'integer')
        );

        $this->front_matter = SwatDB::query(
            $this->app->db,
            $sql,
            SwatDBClassMap::get(CMEFrontMatterWrapper::class)
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

    // build phase

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

    protected function buildToolbars()
    {
        parent::buildToolbars();

        $this->ui->getWidget('details_toolbar')->visible = false;
    }

    protected function buildNavBar()
    {
        parent::buildNavBar();

        $this->navbar->popEntry();
        $this->navbar->createEntry($this->getTitle());
    }
}

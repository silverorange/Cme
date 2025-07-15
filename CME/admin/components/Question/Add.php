<?php

/**
 * Question edit page for inquisitions.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEQuestionAdd extends InquisitionQuestionAdd
{
    /**
     * @var CMEQuestionHelper
     */
    protected $helper;

    // init phase

    protected function initInternal()
    {
        parent::initInternal();

        $this->helper = $this->getQuestionHelper($this->inquisition);
        $this->helper->initInternal();

        // for evaluations, hide correct option column
        if ($this->helper->isEvaluation()) {
            $view = $this->ui->getWidget('question_option_table_view');
            $correct_column = $view->getColumn('correct_option');
            $correct_column->visible = false;
        }
    }

    protected function getQuestionHelper()
    {
        return new CMEQuestionHelper($this->app, $this->inquisition);
    }

    // process phase

    protected function relocate()
    {
        $uri = $this->helper->getRelocateURI();

        if ($uri == '') {
            parent::relocate();
        } else {
            $this->app->relocate($uri);
        }
    }

    // build phase

    protected function buildNavBar()
    {
        parent::buildNavBar();

        $this->helper->buildNavBar($this->navbar);
    }
}

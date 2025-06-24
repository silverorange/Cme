<?php

/**
 * Delete confirmation page for question images.
 *
 * @copyright 2012-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEOptionImageDelete extends InquisitionOptionImageDelete
{
    /**
     * @var CMEOptionHelper
     */
    protected $helper;

    // helper methods

    public function setInquisition(?InquisitionInquisition $inquisition = null)
    {
        parent::setInquisition($inquisition);

        $this->helper = $this->getOptionHelper();
        $this->helper->initInternal();
    }

    protected function getOptionHelper()
    {
        $question_helper = new CMEQuestionHelper(
            $this->app,
            $this->inquisition
        );

        return new CMEOptionHelper(
            $this->app,
            $question_helper,
            $this->question
        );
    }

    // build phase

    protected function buildNavBar()
    {
        parent::buildNavBar();

        // put edit entry at the end
        $title = $this->navbar->popEntry();

        $this->helper->buildNavBar($this->navbar);

        $this->navbar->addEntry($title);
    }
}

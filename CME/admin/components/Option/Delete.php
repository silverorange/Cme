<?php

/**
 * @copyright 2014-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEOptionDelete extends InquisitionOptionDelete
{
    /**
     * @var CMEOptionHelper
     */
    protected $helper;

    // init phase

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

        $this->helper->buildNavBar($this->navbar);
    }
}

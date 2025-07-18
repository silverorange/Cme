<?php

/**
 * A recordset wrapper class for CMEQuiz objects.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @see       CMEQuiz
 */
class CMEQuizWrapper extends InquisitionInquisitionWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(CMEQuiz::class);
        $this->index_field = 'id';
    }
}

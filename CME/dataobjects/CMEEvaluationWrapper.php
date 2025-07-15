<?php

/**
 * A recordset wrapper class for CMEEEvaluation objects.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @see       CMEEvaluation
 */
class CMEEvaluationWrapper extends InquisitionInquisitionWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(CMEEvaluation::class);
        $this->index_field = 'id';
    }
}

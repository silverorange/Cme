<?php

/**
 * A recordset wrapper class for CMEQuizReport objects.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @see       CMEQuizReport
 */
class CMEQuizReportWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(CMEQuizReport::class);
        $this->index_field = 'id';
    }
}

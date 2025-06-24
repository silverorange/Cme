<?php

/**
 * @copyright 2015-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @see       CMEAccountCMEProgress
 */
class CMEAccountCMEProgressWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(CMEAccountCMEProgress::class);
    }
}

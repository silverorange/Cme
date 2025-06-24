<?php

/**
 * @copyright 2012-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @see       CMEAccountEarnedCMECredit
 */
class CMEAccountEarnedCMECreditWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(
            'CMEAccountEarnedCMECredit'
        );
    }
}

<?php

/**
 * A recordset wrapper class for CMEProvider objects.
 *
 * @copyright 2013-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @see       CMEProvider
 */
class CMEProviderWrapper extends SwatDBRecordsetWrapper
{
    protected function init()
    {
        parent::init();
        $this->row_wrapper_class = SwatDBClassMap::get(CMEProvider::class);
        $this->index_field = 'id';
    }
}

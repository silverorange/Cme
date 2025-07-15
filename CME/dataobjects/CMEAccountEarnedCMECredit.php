<?php

/**
 * CME specific Account object.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEAccountEarnedCMECredit extends SwatDBDataObject
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var SwatDate
     */
    public $earned_date;

    protected function init()
    {
        $this->table = 'AccountEarnedCMECredit';
        $this->id_field = 'integer:id';

        $this->registerInternalProperty(
            'account',
            SwatDBClassMap::get(CMEAccount::class)
        );

        $this->registerInternalProperty(
            'credit',
            SwatDBClassMap::get(CMECredit::class)
        );

        $this->registerDateProperty('earned_date');
    }
}

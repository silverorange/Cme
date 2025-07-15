<?php

/**
 * CME progress for an account.
 *
 * @copyright 2015-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEAccountCMEProgress extends SwatDBDataObject
{
    /**
     * @var int
     */
    public $id;

    protected function init()
    {
        $this->table = 'AccountCMEProgress';
        $this->id_field = 'integer:id';

        $this->registerInternalProperty(
            'account',
            SwatDBClassMap::get(CMEAccount::class)
        );

        $this->registerInternalProperty(
            'quiz',
            SwatDBClassMap::get(CMEQuiz::class)
        );

        $this->registerInternalProperty(
            'evaluation',
            SwatDBClassMap::get(CMEEvaluation::class)
        );
    }
}

<?php

/**
 * CME progress for an account.
 *
 * @copyright 2015-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @property int            $id
 * @property CMEAccount     $account
 * @property ?CMEQuiz       $quiz
 * @property ?CMEEvaluation $evaluation
 */
class CMEAccountCMEProgress extends SwatDBDataObject
{
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

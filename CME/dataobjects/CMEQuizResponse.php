<?php

/**
 * A quiz response.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @property ?SwatDate        $reset_date
 * @property CMEAccount       $account
 * @property CMECreditWrapper $credits
 */
class CMEQuizResponse extends InquisitionResponse
{
    public $reset_date;

    public function getCorrectCount()
    {
        $correct = 0;

        foreach ($this->values as $value) {
            $question = $value->question_binding->question;
            $correct_option_id = $question->getInternalValue('correct_option');
            $response_option_id = $value->getInternalValue('question_option');
            if ($response_option_id == $correct_option_id) {
                $correct++;
            }
        }

        return $correct;
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function isPassed()
    {
        return
            $this->getGrade() >=
            $this->credits->getFirst()->front_matter->passing_grade;
    }

    public function getCredits()
    {
        return $this->credits;
    }

    protected function init()
    {
        parent::init();
        $this->registerDateProperty('reset_date');
        $this->registerInternalProperty(
            'account',
            SwatDBClassMap::get(CMEAccount::class)
        );
    }

    public function loadCredits()
    {
        $this->checkDB();

        $inquisition_id = $this->getInternalValue('inquisition');
        $account_id = $this->getInternalValue('account');

        $sql = sprintf(
            'select CMECredit.* from CMECredit
				inner join AccountCMEProgressCreditBinding on
					AccountCMEProgressCreditBinding.credit = CMECredit.id
				inner join AccountCMEProgress on
					AccountCMEProgress.id =
					AccountCMEProgressCreditBinding.progress
			where AccountCMEProgress.quiz = %s
				and AccountCMEProgress.account = %s',
            $this->db->quote($inquisition_id, 'integer'),
            $this->db->quote($account_id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(CMECreditWrapper::class)
        );
    }
}

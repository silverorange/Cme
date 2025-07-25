<?php

/**
 * An evaluation response.
 *
 * @copyright 2011-2016 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 *
 * @property CMEAccount $account
 */
class CMEEvaluationResponse extends InquisitionResponse
{
    public function getFrontMatter()
    {
        $this->checkDB();

        $inquisition_id = $this->getInternalValue('inquisition');

        $sql = sprintf(
            'select * from CMEFrontMatter where evaluation = %s',
            $this->db->quote($inquisition_id, 'integer')
        );

        return SwatDB::query(
            $this->db,
            $sql,
            SwatDBClassMap::get(CMEFrontMatterWrapper::class)
        )->getFirst();
    }

    protected function init()
    {
        parent::init();
        $this->registerInternalProperty(
            'account',
            SwatDBClassMap::get(CMEAccount::class)
        );
    }
}

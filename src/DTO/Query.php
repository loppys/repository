<?php

namespace Vengine\Libraries\Repository\DTO;

use Vengine\Libraries\Repository\Schema\Table;
use Vengine\Libraries\Repository\Storage\CriteriaOperatorStorage;
use Vengine\Libraries\Repository\Storage\JoinTypeStorage;

class Query
{
    /**
     * @var Criteria[]
     */
    protected array $criteries = [];

    /**
     * @var Table[]
     */
    protected array $joinTableList = [];

    protected int $limit = 0;

    /**
     * @return Criteria[]
     */
    public function getCriteries(): array
    {
        return $this->criteries;
    }

    /**
     * @return Table[]
     */
    public function getJoinTableList(): array
    {
        return $this->joinTableList;
    }

    public function setLimit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function leftJoin(Table $table): static
    {
        return $this->addJoinTable($table, JoinTypeStorage::LEFT);
    }

    public function innerJoin(Table $table): static
    {
        return $this->addJoinTable($table, JoinTypeStorage::INNER);
    }

    public function rightJoin(Table $table): static
    {
        return $this->addJoinTable($table, JoinTypeStorage::RIGHT);
    }

    protected function addJoinTable(Table $table, ?string $type = JoinTypeStorage::NONE): static
    {
        if (!$type) {
            return $this;
        }

        $table->setJoinMethod($type);

        $this->joinTableList[] = $table;

        return $this;
    }

    public function addCriteria(Criteria $criteria): static
    {
        $this->criteries[] = $criteria;

        return $this;
    }

    public function addAndCriteria(Criteria $criteria): static
    {
        if ($criteria->getSqlOperator() !== 'AND') {
            $criteria->setSqlOperator('AND');
        }

        $this->criteries[] = $criteria;

        return $this;
    }

    public function addOrCriteria(Criteria $criteria): static
    {
        if ($criteria->getSqlOperator() !== 'OR') {
            $criteria->setSqlOperator('OR');
        }

        $this->criteries[] = $criteria;

        return $this;
    }
}

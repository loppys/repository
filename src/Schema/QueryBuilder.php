<?php

namespace Vengine\Libraries\Repository\Schema;

use Vengine\Libraries\Repository\DTO\Criteria;
use Vengine\Libraries\Repository\DTO\Query;
use Vengine\Libraries\Repository\Storage\JoinTypeStorage;

class QueryBuilder
{
    protected Query $query;

    protected Table $mainTable;

    public function __construct(Table $mainTable)
    {
        $this->mainTable = $mainTable;
        $this->query = new Query();
    }

    public static function new(Table $mainTable): static
    {
        return new static($mainTable);
    }

    public function leftJoin(Table $table): static
    {
        $this->query->leftJoin($table);

        return $this;
    }

    public function innerJoin(Table $table): static
    {
        $this->query->innerJoin($table);

        return $this;
    }

    public function rightJoin(Table $table): static
    {
        $this->query->rightJoin($table);

        return $this;
    }

    public function andWhere(string $column, string $value, string $operator = ''): static
    {
        $this->query->addAndCriteria(
            new Criteria($column, $value, $operator ?: '=')
        );
    }

    public function orWhere(string $column, string $value, string $operator = ''): static
    {
        $this->query->addOrCriteria(
            new Criteria($column, $value, $operator ?: '=')
        );

        return $this;
    }

    public function flushQuery(): Query
    {
        $query = $this->query;

        $this->query = new Query();

        return $query;
    }
}

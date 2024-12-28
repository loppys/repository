<?php

namespace Vengine\Libraries\Repository\DTO;

use Vengine\Libraries\Repository\Storage\JoinTypeStorage;
use Vengine\Libraries\Repository\Schema\Table;

/**
 * @see Table
 */
class DependencyTable
{
    protected string $fromTable = '';

    protected string $aliasTable = '';

    protected string $fromAlias = 't1';

    protected array $selectColumns = [];

    protected array $conditionFromColumns = [];

    protected string $joinType = JoinTypeStorage::INNER;

    protected DependencyTable|array $dependencyIsDependent = [];

    public static function create(array $data): static
    {
        $obj = new static();

        foreach ($data as $name => $value) {
            if (($name === 'dependencyIsDependent') && is_array($value)) {
                $value = static::create($value);
            }

            if (property_exists($obj, $name)) {
                $obj->{$name} = $value;
            }
        }

        return $obj;
    }

    public function isValid(): bool
    {
        return !empty($this->fromTable) && !empty($this->aliasTable) && !empty($this->conditionFromColumns);
    }

    public function getDependencyIsDependent(): DependencyTable|array
    {
        return $this->dependencyIsDependent;
    }

    public function getFromTable(): string
    {
        return $this->fromTable;
    }

    public function setFromTable(string $fromTable): static
    {
        $this->fromTable = $fromTable;

        return $this;
    }

    public function getSelectColumns(): array
    {
        return $this->selectColumns;
    }

    public function setSelectColumns(array $selectColumns): static
    {
        $this->selectColumns = $selectColumns;

        return $this;
    }

    public function setConditionFromColumns(array $conditionFromColumns): static
    {
        $this->conditionFromColumns = $conditionFromColumns;

        return $this;
    }

    public function getConditionFromColumns(): array
    {
        return $this->conditionFromColumns;
    }

    public function setAliasTable(string $aliasTable): static
    {
        $this->aliasTable = $aliasTable;

        return $this;
    }

    public function getAliasTable(): string
    {
        return $this->aliasTable;
    }

    public function getJoinType(): string
    {
        return $this->joinType;
    }

    public function setJoinType(string $joinType): static
    {
        $this->joinType = $joinType;

        return $this;
    }

    public function setFromAlias(string $fromAlias): static
    {
        $this->fromAlias = $fromAlias;

        return $this;
    }

    public function getFromAlias(): string
    {
        return $this->fromAlias;
    }
}

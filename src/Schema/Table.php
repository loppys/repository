<?php

namespace Vengine\Libraries\Repository\Schema;

use Vengine\Libraries\Repository\DTO\Criteria;
use Vengine\Libraries\Repository\Storage\JoinTypeStorage;

class Table
{
    protected string $tableName = '';

    protected string $alias = '';

    /**
     * @var TableField[]
     */
    protected array $fields = [];

    /**
     * @var Table[]
     */
    protected array $dependecies = [];

    protected ?string $joinMethod = JoinTypeStorage::NONE;

    /**
     * @var Criteria[]
     */
    protected array $joinCriteries = [];

    /**
     * @param string $tableName
     * @param TableField[] $fieldMap
     */
    public function __construct(string $tableName, array $fieldMap = [])
    {
        $this->tableName = $tableName;
        $this->alias = $tableName;

        foreach ($fieldMap as $field) {
            if (!$field instanceof TableField) {
                continue;
            }

            $this->fields[$field->getEntityField()] = $field;
        }
    }

    public function setAliasTable(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    public function getAliasTable(): string
    {
        return $this->alias;
    }

    public function setJoinMethod(?string $joinMethod): static
    {
        $this->joinMethod = $joinMethod;

        return $this;
    }

    public function getJoinMethod(): ?string
    {
        return $this->joinMethod;
    }

    /**
     * @return Criteria[]
     */
    public function getJoinCriteries(): array
    {
        return $this->joinCriteries;
    }

    public function addJoinCriteria(Criteria $criteria): static
    {
        $this->joinCriteries[] = $criteria;

        return $this;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function addTableDependecy(Table $table): static
    {
        $this->dependecies[] = $table;

        return $this;
    }

    public function getDependecies(): array
    {
        return $this->dependecies;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function addField(TableField $field): static
    {
        $this->fields[$field->getEntityField()] = $field;

        return $this;
    }

    public function getField(string $entityField): ?TableField
    {
        if ($field = ($this->fields[$entityField] ?? false)) {
            return $field;
        }

        return null;
    }
}

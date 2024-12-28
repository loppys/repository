<?php

namespace Vengine\Libraries\Repository\Schema;

class TableField
{
    protected string $dbField = '';

    protected string $entityField = '';

    public function __construct(string $entityField, string $dbField)
    {
        $this->dbField = $dbField;
        $this->entityField = $entityField;
    }

    public function setDbField(string $dbField): static
    {
        $this->dbField = $dbField;

        return $this;
    }

    public function setEntityField(string $entityField): static
    {
        $this->entityField = $entityField;

        return $this;
    }

    public function getDbField(): string
    {
        return $this->dbField;
    }

    public function getEntityField(): string
    {
        return $this->entityField;
    }
}

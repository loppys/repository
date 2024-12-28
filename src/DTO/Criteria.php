<?php

namespace Vengine\Libraries\Repository\DTO;

class Criteria
{
    protected string $key = '';

    protected string|array $value = '';

    protected string $operator = '';

    protected string $sqlOperator = 'AND';

    public function __construct(string $key, string|array $value, string $operator = '')
    {
        $this->key = $key;
        $this->value = $value;

        if (empty($this->operator)) {
            $this->setOperator($operator);
        }
    }

    public function setSqlOperator(string $sqlOperator): static
    {
        $this->sqlOperator = $sqlOperator;

        return $this;
    }

    public function getSqlOperator(): string
    {
        return $this->sqlOperator;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function setValue(string|array $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string|array
    {
        return $this->value;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): static
    {
        $this->operator = $operator;

        return $this;
    }
}

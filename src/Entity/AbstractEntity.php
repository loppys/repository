<?php

namespace Vengine\Libraries\Repository\Entity;

use ArrayAccess;
use Vengine\Libraries\Repository\Helpers\ConvertCaseHelper;
use Vengine\Libraries\Repository\AbstractRepository;
use RuntimeException;
use Iterator;

abstract class AbstractEntity implements ArrayAccess, Iterator
{
    protected array $columns = [];

    protected array $otherColumns = [];

    protected array $entityData = [];

    private int $iterationKey = 0;

    public function __construct(array $entityData = [])
    {
        $this->entityData = $entityData;

        $callSource = null;
        $trace = debug_backtrace(limit: 2);

        if (!empty($trace[1]) && is_array($trace[1])) {
            $callSource = $trace[1]['object'] ?? null;
        }

        if (
            (empty($callSource) || !is_object($callSource))
            || (is_object($callSource) && !$callSource instanceof AbstractRepository)
        ) {
            if (!$callSource instanceof AbstractEntity) {
                throw new RuntimeException('Creating entities manually is prohibited, use repository');
            }
        }
    }

    public function toArray(): array
    {
        return $this->getEntityData();
    }

    public function getDataByName(string $name): mixed
    {
        return $this->entityData[$name] ?? null;
    }

    public function setDataByName(string $name, mixed $data): static
    {
        if (!$this->columnExists($name)) {
            return $this;
        }

        $this->entityData[$name] = $data;

        return $this;
    }

    public function setEntityData(array $entityData): static
    {
        foreach ($entityData as $column => $data) {
            if (!$this->columnExists($column)) {
                continue;
            }

            $this->{$column} = $data;
        }

        $this->rewind();

        return $this;
    }

    public function setColumns(array $columns): static
    {
        $this->columns = array_merge($columns, $this->otherColumns);

        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function __get(string $name): mixed
    {
        if (!$this->columnExists($name)) {
            return null;
        }

        $method = 'get' . ConvertCaseHelper::snakeCaseToCamelCase($name);
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        if (!empty($this->getDataByName($name))) {
            return $this->getDataByName($name);
        }

        return null;
    }

    public function __set(string $name, $value): void
    {
        if (!$this->columnExists($name)) {
            return;
        }

        $this->setDataByName($name, $value);

        $method = 'set' . ConvertCaseHelper::snakeCaseToCamelCase($name);
        if (method_exists($this, $method)) {
            $this->{$method}($value);

            return;
        }

        if (property_exists($this, $name)) {
            $this->{$name} = $value;
        }
    }

    public function __isset(string $name): bool
    {
        return property_exists($this, $name) || !empty($this->entityData[$name]);
    }

    public function getEntityData(): array
    {
        return $this->entityData;
    }

    public function offsetExists(mixed $offset): bool
    {
        return !empty($this->entityData[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->entityData[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->entityData[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->entityData[$offset]);
    }

    public function current(): mixed
    {
        return $this->entityData[$this->iterationKey];
    }

    public function next(): void
    {
        ++$this->iterationKey;
    }

    public function key(): int
    {
        return $this->iterationKey;
    }

    public function valid(): bool
    {
        return isset($this->entityData[$this->iterationKey]);
    }

    public function rewind(): void
    {
        $this->iterationKey = 0;
    }

    private function columnExists(string $column): bool
    {
        return in_array($column, $this->otherColumns, true) || in_array($column, $this->columns, true);
    }
}

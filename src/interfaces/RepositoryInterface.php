<?php

namespace Vengine\Libraries\Repository\interfaces;

use Vengine\Libraries\Repository\Entity\AbstractEntity;
use Doctrine\DBAL\Exception;

interface RepositoryInterface
{
    public function createEntity(array $criteria): mixed;

    /**
     * @throws Exception
     */
    public function save(array $data): bool;

    /**
     * @throws Exception
     */
    public function getRow(mixed $id): array|bool;

    /**
     * @throws Exception
     */
    public function getLastInsertId(): string|int;

    /**
     * @throws Exception
     */
    public function updateByPrimaryKey(int $id, array $data = []): bool;

    /**
     * @throws Exception
     */
    public function update(array $data = [], ?array $criteria = null): bool;

    /**
     * @throws Exception
     */
    public function updateByEntity(AbstractEntity $entity, array $updateKeys = []): bool;

    /**
     * @throws Exception
     */
    public function has(array $criteria = []): bool;

    /**
     * @throws Exception
     */
    public function dropTable(): void;

    /**
     * @throws Exception
     */
    public function getAll(array $criteria = []): array;

    /**
     * @throws Exception
     */
    public function delete(array $criteria = []): bool;
}
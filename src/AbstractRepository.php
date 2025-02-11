<?php

namespace Vengine\Libraries\Repository;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Vengine\Libraries\DBAL\Adapter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Vengine\Libraries\Repository\Helpers\ConvertCaseHelper;
use Vengine\Libraries\Repository\interfaces\QueryCreatorInterface;
use Vengine\Libraries\Repository\DTO\Criteria;
use Vengine\Libraries\Repository\DTO\DependencyTable;
use Vengine\Libraries\Repository\DTO\Query;
use Vengine\Libraries\Repository\Entity\AbstractEntity;
use Vengine\Libraries\Repository\interfaces\RepositoryInterface;
use Vengine\Libraries\Repository\Schema\Table;
use Vengine\Libraries\Repository\Schema\TableField;
use Vengine\Libraries\Repository\Server\Source;
use Vengine\Libraries\Repository\Schema\QueryBuilder as RepositoryQueryBuilder;
use Doctrine\DBAL\Schema\Table as DBALTable;

abstract class AbstractRepository implements RepositoryInterface, QueryCreatorInterface
{
    protected string $table = '';
    
    protected bool $createTableIfNotExists = false;

    protected Table $_table;

    protected bool $usePrimaryKeyWhenSave = false;

    protected string $primaryKey = 'id';

    protected array $columnMap = [
        'undefined'
    ];

    protected array $columnMapForCreateTable = [];

    protected string $entityClass = '';

    /**
     * @var array<DependencyTable|array>
     */
    protected array $dependencyTableList = [];

    protected Connection $connection;

    protected Adapter $db;

    protected CriteriaComparator $criteriaComparator;

    private RepositoryQueryBuilder $repositoryQueryBuilder;

    /**
     * @throws Exception
     */
    public function __construct(Adapter $db, CriteriaComparator $criteriaComparator)
    {
        $this->connection = $db->getConnection();
        $this->db = $db;
        $this->criteriaComparator = $criteriaComparator;

        array_unshift($this->columnMap, $this->primaryKey);

        $fields = [];
        foreach ($this->columnMap as $entityField => $dbField) {
            if (!is_string($entityField)) {
                $entityField = ConvertCaseHelper::snakeCaseToCamelCase($dbField);
            }

            $fields[] = new TableField($entityField, $dbField);
        }

        $this->_table = new Table($this->table, $fields);

        if (property_exists($this, 'tableAlias')) {
            if (is_string($this->tableAlias)) {
                $this->_table->setAliasTable($this->tableAlias);
            }
        }
        
        if ($this->createTableIfNotExists) {
            $this->createTable();
        }
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function newEntity(): ?AbstractEntity
    {
        if (!class_exists($this->entityClass)) {
            return null;
        }

        $entity = new $this->entityClass;

        if (!$entity instanceof AbstractEntity) {
            return null;
        }

        return $entity->setColumns($this->columnMap);
    }

    /**
     * @throws Exception
     */
    public function createEntity(array $criteria = []): mixed
    {
        $data = $this->get($criteria, 1);

        if (empty($data)) {
            return null;
        }

        return $this->createEntityByArray($data);
    }

    public function createEntityByArray(array $data): ?AbstractEntity
    {
        $entity = $this->newEntity();

        if ($entity !== null) {
            return $entity->setEntityData($data);
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function saveByEntity(AbstractEntity $entity): bool
    {
        $data = $entity->getEntityData();

        if (empty($data)) {
            return false;
        }

        return $this->save($data);
    }

    /**
     * @throws Exception
     */
    public function save(array $data): bool
    {
        $this->prepareData($data);

        if (array_key_exists($this->primaryKey, $data) && !$this->usePrimaryKeyWhenSave) {
            $id = $data[$this->primaryKey];

            unset($data[$this->primaryKey]);

            return $this->updateByPrimaryKey($id, $data);
        }

        return (bool)$this->connection->createQueryBuilder()
            ->insert($this->table)
            ->values($this->db->escapeValue($data))
            ->executeStatement()
            ;
    }

    /**
     * @throws Exception
     */
    public function getRow(mixed $id): array|bool
    {
        return $this->get([$this->primaryKey => $id]);
    }

    /**
     * @throws Exception
     */
    public function getRowByCriteria(array $criteria = []): array|bool
    {
        return $this->get($criteria, 1);
    }

    /**
     * @throws Exception
     */
    public function getLastInsertId(): string|int
    {
        return $this->connection->lastInsertId();
    }

    /**
     * @throws Exception
     */
    public function updateByPrimaryKey(int $id, array $data = []): bool
    {
        return $this->update($data, [$this->primaryKey => $id]);
    }

    /**
     * @throws Exception
     */
    public function update(array $data = [], ?array $criteria = null): bool
    {
        if (empty($data)) {
            return false;
        }

        return (bool)$this->connection->update($this->table, $this->db->escapeValue($data), $criteria);
    }

    /**
     * @throws Exception
     */
    public function updateByEntity(AbstractEntity $entity, array $updateKeys = []): bool
    {
        if (!in_array($this->primaryKey, $entity->getColumns(), true)) {
            return false;
        }

        $id = $entity->getDataByName($this->primaryKey);

        if (empty($id)) {
            return false;
        }

        $data = [];
        if (!empty($updateKeys)) {
            foreach ($updateKeys as $key) {
                $data[$key] = $entity->getDataByName($key);
            }
        } else {
            $data = $entity->toArray();
        }

        return $this->updateByPrimaryKey($id, $data);
    }

    /**
     * @throws Exception
     */
    public function has(array $criteria = []): bool
    {
        if (empty($criteria)) {
            return false;
        }

        return !empty($this->get($criteria));
    }

    /**
     * @throws Exception
     */
    public function dropTable(): void
    {
        $this->connection->createSchemaManager()->dropTable($this->table);
    }

    /**
     * @throws Exception
     */
    public function createTable(?DBALTable $table = null): bool
    {
        if ($this->hasTable()) {
            return true;
        }

        $schemaManager = $this->connection->createSchemaManager();

        if ($table !== null && $table->getName() === $this->_table->getTableName()) {
            $schemaManager->createTable($table);

            return $this->hasTable();
        }

        if (empty($this->columnMapForCreateTable)) {
            return false;
        }

        $columns = [];

        $pkColumn = new Column($this->primaryKey, Type::getType(Types::INTEGER));
        $pkColumn
            ->setNotnull(true)
            ->setUnsigned(true)
            ->setAutoincrement(true)
            ->setLength(11)
        ;

        $columns[] = $pkColumn;

        foreach ($this->columnMapForCreateTable as $columnName => $columnInfo) {
            if ($columnName === $this->primaryKey) {
                continue;
            }

            if (!empty($columnInfo['type'])) {
                $type = Type::getType($columnInfo['type']);
            } else {
                $type = Type::getType(Types::STRING);
            }

            $columns[] = new Column($columnName, $type, $columnInfo['options'] ?? []);
        }

        $indexPk = new Index(
            'pk_' . $this->primaryKey,
            [$this->primaryKey],
            isPrimary: true
        );

        $dbalTable = new DBALTable(
            $this->_table->getTableName(),
            $columns,
            [$indexPk]
        );

        $schemaManager->createTable($dbalTable);

        return $this->hasTable();
    }

    /**
     * @throws Exception
     */
    public function hasTable(): bool
    {
        return $this->connection->createSchemaManager()->tableExists($this->table);
    }

    /**
     * @throws Exception
     * @return AbstractEntity[]
     */
    public function getAll(array $criteria = []): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->table)
        ;

        foreach ($criteria as $k => $v) {
            if (array_key_first($criteria) === $k) {
                $qb->where($qb->expr()->eq($k, $this->db->escapeValue($v)));

                continue;
            }

            $qb->andWhere($qb->expr()->eq($k, $this->db->escapeValue($v)));
        }

        $data = $qb->executeQuery()->fetchAllAssociative();

        $result = [];
        foreach ($data as $item) {
            $entity = $this->createEntityByArray($item);

            if ($entity !== null) {
                $result[] = $entity;
            }
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function delete(array $criteria = []): bool
    {
        if (empty($criteria)) {
            return false;
        }

        return (bool)$this->connection->delete($this->table, $criteria);
    }

    public function getByQuery(?Query $query = null): array|bool
    {
        if ($query === null && empty($this->repositoryQueryBuilder)) {
            return $this->getAll();
        }

        if (!empty($this->repositoryQueryBuilder) && $query !== null) {
            $this->destructBuilder();
        }

        if (!empty($this->repositoryQueryBuilder)) {
            $query = $this->repositoryQueryBuilder->flushQuery();
            
            $this->destructBuilder();
        }

        $qb = $this->connection->createQueryBuilder();

        foreach ($query->getJoinTableList() as $table) {
            $this->joinTable($table, $qb);
        }

        $i = 0;
        foreach ($query->getCriteries() as $criteria) {
            $criteriaString = $this->criteriaComparator->compare($criteria);

            if ($i < 1) {
                $qb->where($criteriaString);
            } else {
                if ($criteria->getSqlOperator() === 'OR') {
                    $qb->orWhere($criteriaString);
                } else {
                    $qb->andWhere($criteriaString);
                }
            }

            $i++;
        }

        $limit = $query->getLimit();
        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }

        if ($limit === 1) {
            return $qb->executeQuery()->fetchAssociative();
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function joinTable(Table $table, QueryBuilder $queryBuilder): void
    {
        $i = 0;
        $condition = '';
        foreach ($table->getJoinCriteries() as $joinCriteria) {
            $criteriaString = $this->criteriaComparator->compare($joinCriteria);

            if ($i < 1) {
                $condition .= $criteriaString;
            } else {
                $condition .= ' ' . $joinCriteria->getSqlOperator() . ' ' . $criteriaString;
            }

            $i++;
        }

        $queryBuilder->{$table->getJoinMethod()}(
            $this->_table->getAliasTable(),
            $table->getTableName(),
            $table->getAliasTable(),
            $condition
        );

        if (!empty($table->getDependencies())) {
            foreach ($table->getDependencies() as $dependecy) {
                $this->joinTable($dependecy, $queryBuilder);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function get(array $criteria = [], ?int $limit = null): array|bool
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('t1.*')
            ->from($this->table, 't1')
        ;

        foreach ($criteria as $k => $v) {
            if ($v instanceof Criteria) {
                if (array_key_first($criteria) === $k) {
                    $qb->where($this->criteriaComparator->compare($v));

                    continue;
                }

                if ($v->getSqlOperator() === 'OR') {
                    $qb->orWhere($this->criteriaComparator->compare($v));
                } else {
                    $qb->andWhere($this->criteriaComparator->compare($v));
                }
            } else {
                if (array_key_first($criteria) === $k) {
                    $qb->where($qb->expr()->eq($k, $this->db->escapeValue($v)));

                    continue;
                }

                $qb->andWhere($qb->expr()->eq($k, $this->db->escapeValue($v)));
            }
        }

        $qb->setMaxResults($limit);
        
        if (!empty($this->dependencyTableList)) {
            $qb = $this->dependencyRecursiveCreate($qb, $this->dependencyTableList);
        }

        if ($limit === 1) {
            return $qb->executeQuery()->fetchAssociative();
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function dependencyRecursiveCreate(
        QueryBuilder $queryBuilder,
        null|array|DependencyTable $dependency = null
    ): QueryBuilder {
        if ($dependency === null) {
            return $queryBuilder;
        }

        if (is_array($dependency)) {
            foreach ($dependency as $dependencyTable) {
                if (is_array($dependencyTable)) {
                    $dependencyTable = DependencyTable::create($dependencyTable);
                }

                if (!$dependencyTable->isValid()) {
                    continue;
                }

                $queryBuilder = $this->createDependency($queryBuilder, $dependencyTable);

                if (!empty($dependencyTable->getDependencyIsDependent())) {
                    $queryBuilder = $this->dependencyRecursiveCreate($queryBuilder, $dependencyTable->getDependencyIsDependent());
                }
            }
        } else {
            if (!$dependency->isValid()) {
                return $queryBuilder;
            }

            $queryBuilder = $this->createDependency($queryBuilder, $dependency);

            if (!empty($dependency->getDependencyIsDependent())) {
                $queryBuilder = $this->dependencyRecursiveCreate($queryBuilder, $dependency->getDependencyIsDependent());
            }
        }

        return $queryBuilder;
    }

    private function createDependency(QueryBuilder $queryBuilder, DependencyTable $dependencyTable): QueryBuilder
    {
        $joinMethod = $dependencyTable->getJoinType() . 'Join';
        if (!method_exists($queryBuilder, $joinMethod)) {
            return $queryBuilder;
        }

        if (empty($dependencyTable->getConditionFromColumns())) {
            return $queryBuilder;
        }

        if (!empty($dependencyTable->getSelectColumns())) {
            foreach ($dependencyTable->getSelectColumns() as $selectColumn) {
                $queryBuilder->addSelect(
                    $dependencyTable->getAliasTable() . '.' . $selectColumn
                );
            }
        } else {
            $queryBuilder->addSelect($dependencyTable->getAliasTable() . '.*');
        }

        $conditionColumns = $dependencyTable->getConditionFromColumns();

        $condition = '';

        $firstKey = array_key_first($conditionColumns);
        foreach ($conditionColumns as $key => $column) {
            $clm = $dependencyTable->getAliasTable() . '.' . $this->db->escapeValue($key, true);
            $val = $dependencyTable->getFromAlias() . '.' . $this->db->escapeValue($column, true);

            if ($firstKey === $column) {
                $condition = $queryBuilder->expr()->eq($clm, $val);
            } else {
                $condition .= $queryBuilder->expr()->eq($clm, $val);
            }
        }

        $queryBuilder->{$joinMethod}(
            $dependencyTable->getFromAlias(),
            $dependencyTable->getFromTable(),
            $dependencyTable->getAliasTable(),
            $condition
        );

        return $queryBuilder;
    }

    public function queryBuilder(): RepositoryQueryBuilder
    {
        return $this->repositoryQueryBuilder = RepositoryQueryBuilder::new($this->_table);
    }

    public function destructBuilder(): void
    {
        unset($this->repositoryQueryBuilder);
    }

    protected function prepareData(array &$data): void
    {
        foreach ($data as $col => $val) {
            if (!in_array($col, $this->columnMap, true)) {
                unset($data[$col]);
            }

            if (empty($val) && $val !== null) {
                unset($data[$col]);
            }

            if ($col === $this->primaryKey && !$this->usePrimaryKeyWhenSave) {
                unset($data[$col]);
            }
        }
    }

    public function setServerSource(Source $source): void
    {
        $this->connection = $source->getConnection();
    }
}

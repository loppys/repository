<?php

namespace Vengine\Libraries\Repository;

use Vengine\Libraries\DBAL\Adapter;
use Vengine\Libraries\Repository\DTO\Criteria;
use Vengine\Libraries\Repository\Storage\CriteriaOperatorStorage;

class CriteriaComparator
{
    protected Adapter $db;

    public function __construct(Adapter $db)
    {
        $this->db = $db;
    }

    public function compare(Criteria $criteria): string
    {
        $key = $this->db->escapeValue($criteria->getKey(), true);

        $value = $criteria->getValue();

        if (is_array($value)) {
            $criteria->setOperator(CriteriaOperatorStorage::IN);
        }

        $value = $this->db->escapeValue($value);

        if ($criteria->getOperator() === CriteriaOperatorStorage::IN) {
            $value = implode(',', $value);

            $value = "({$value})";
        }

        return "{$key} {$criteria->getOperator()} {$value}";
    }
}

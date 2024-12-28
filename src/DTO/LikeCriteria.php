<?php

namespace Vengine\Libraries\Repository\DTO;

use Vengine\Libraries\Repository\Storage\CriteriaOperatorStorage;

class LikeCriteria extends Criteria
{
    protected string $operator = CriteriaOperatorStorage::LIKE;
}

<?php

namespace Vengine\Libraries\Repository\interfaces;

use Vengine\Libraries\Repository\Schema\QueryBuilder;

interface QueryCreatorInterface
{
    public function queryBuilder(): QueryBuilder;

    public function destructBuilder(): void;
}

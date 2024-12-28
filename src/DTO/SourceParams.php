<?php

namespace Vengine\Libraries\Repository\DTO;

class SourceParams
{
    public string $dbType = 'pdo_mysql';

    public ?string $dbHost = null;

    public ?string $dbName = null;

    public ?string $dbLogin = null;

    public ?string $dbPassword = null;

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

<?php

namespace Vengine\Libraries\Repository\Server;

use Discord\Bot\Core;
use Vengine\Libraries\Repository\DTO\SourceParams;
use Doctrine\DBAL\Connection;

class Source
{
    protected SourceParams $params;

    public function __construct(SourceParams $params)
    {
        $this->params = $params;
    }

    public function getConnection(): Connection
    {
        $db = Core::getInstance()->db;

        if ($this->params->dbHost === null) {
            return $db->getConnection();
        }

        $connectionName = "repository_server_{$this->params->dbHost}";

        $db->createNewConnection(
            $connectionName,
            $this->getParams()->toArray(),
            false
        );

        return $db->getConnection($connectionName);
    }

    public function getParams(): SourceParams
    {
        return $this->params;
    }
}

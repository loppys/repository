<?php

namespace Vengine\Libraries\Repository\Server;

use Vengine\Libraries\DBAL\Adapter;
use Vengine\Libraries\Repository\DTO\SourceParams;
use Doctrine\DBAL\Connection;

class Source
{
    protected Adapter $db;

    protected SourceParams $params;

    public function __construct(Adapter $adapter, SourceParams $params)
    {
        $this->params = $params;
        $this->db = $adapter;
    }

    public function getConnection(): Connection
    {
        if ($this->params->dbHost === null) {
            return $this->db->getConnection();
        }

        $connectionName = "repository_server_{$this->params->dbHost}";

        $this->db->createNewConnection(
            $connectionName,
            $this->getParams()->toArray(),
            false
        );

        return $this->db->getConnection($connectionName);
    }

    public function getParams(): SourceParams
    {
        return $this->params;
    }
}

<?php

namespace lx;

/**
 * Interface DbConnectorInterface
 * @package lx
 */
interface DbConnectorInterface
{
    /**
     * @param string $db
     * @return bool
     */
    public function hasConnection($db);

    /**
     * @param string $db
     * @return DB|null
     */
    public function getConnection($db = null);

    /**
     * @return DB|null
     */
    public function getMainConnection();

    /**
     * @return DB|null
     */
    public function getReplicaConnection();

    /**
     * @param string $db
     */
    public function closeConnection($db = null);
}

<?php

namespace lx;

class DbQueryBuilder
{
    private DbConnectionInterface $db;
    private string $query;

    public function __construct(DbConnectionInterface $db)
    {
        $this->db = $db;
    }

    public function query(string $query): void
    {
        $this->query = $query;
        
        
        
    }

    

}

<?php

namespace lx;

/**
 * Class FlightRecorder
 * @package lx
 */
class FlightRecorder
{
    /** @var array */
    private $records = [];

    /**
     * @param string|array $record
     */
    public function addRecord($record)
    {
        $this->records[] = $record;
    }

    /**
     * @param array $records
     */
    public function addRecords($records)
    {
        $this->records += $records;
    }

    /**
     * @return array
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->records);
    }
}

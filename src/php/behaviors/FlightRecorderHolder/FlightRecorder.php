<?php

namespace lx;

class FlightRecorder implements FlightRecorderInterface
{
    private ?FlightRecorderHolderInterface $owner = null;
    private array $records = [];

    public function setOwner(FlightRecorderHolderInterface $owner): void
    {
        $this->owner = $owner;
    }
    
    public function getOwner(): FlightRecorderHolderInterface
    {
        return $this->owner;
    }
    
    /**
     * @param mixed $record
     */
    public function addRecord($record): void
    {
        $this->records[] = $record;
    }

    /**
     * @param array $records
     */
    public function addRecords(array $records): void
    {
        $this->records += $records;
    }

    /**
     * @return array
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * @return mixed
     */
    public function getFirstRecord()
    {
        return $this->records[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return empty($this->records);
    }

    public function reset(): void
    {
        $this->records = [];
    }
}

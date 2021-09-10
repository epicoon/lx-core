<?php

namespace lx;

interface FlightRecorderInterface
{
    public function setOwner(FlightRecorderHolderInterface $owner): void;
    public function getOwner(): FlightRecorderHolderInterface;
    /**
     * @param mixed $record
     */
    public function addRecord($record): void;
    public function addRecords(array $records): void;
    public function getRecords(): array;
    /**
     * @return mixed
     */
    public function getFirstRecord();
    public function isEmpty(): bool;
}

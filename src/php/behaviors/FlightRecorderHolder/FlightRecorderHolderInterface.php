<?php

namespace lx;

/**
 * @see FlightRecorderHolderTrait
 */
interface FlightRecorderHolderInterface
{
    public function setFlightRecorder(FlightRecorderInterface $recorder): void;
    public function getFlightRecorder(): FlightRecorderInterface;
    public function hasFlightRecords(): bool;
    public function getFlightRecords(): array;
    /**
     * @return mixed
     */
    public function getFirstFlightRecord();
    /**
     * @param mixed $record
     */
    public function addFlightRecord($record): void;
    public function addFlightRecords(array $records): void;
    public function mergeFlightRecords(FlightRecorderHolderInterface $holder);
}

<?php

namespace lx;

/**
 * Trait FlightRecorderHolderTrait
 * @package lx
 */
trait FlightRecorderHolderTrait
{
    /** @var FlightRecorder */
    private $flightRecorder;

    /**
     * @param FlightRecorder $recorder
     */
    public function setFlightRecorder($recorder)
    {
        $this->flightRecorder = $recorder;
    }

    /**
     * @return FlightRecorder
     */
    public function getFlightRecorder()
    {
        if (!$this->flightRecorder) {
            $this->flightRecorder = new FlightRecorder();
        }

        return $this->flightRecorder;
    }

    /**
     * @param string|array $record
     */
    public function addFlightRecord($record)
    {
        $this->getFlightRecorder()->addRecord($record);
    }

    /**
     * @param array $records
     */
    public function addFlightRecords($records)
    {
        $this->getFlightRecorder()->addRecords($records);
    }
}

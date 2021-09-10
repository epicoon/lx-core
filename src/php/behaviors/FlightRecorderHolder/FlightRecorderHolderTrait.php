<?php

namespace lx;

/**
 * @see FlightRecorderHolderInterface
 */
trait FlightRecorderHolderTrait
{
    private ?FlightRecorderInterface $flightRecorder = null;

    public function setFlightRecorder(FlightRecorderInterface $recorder): void
    {
        $this->flightRecorder = $recorder;
    }

    public function getFlightRecorder(): FlightRecorderInterface
    {
        if (!$this->flightRecorder) {
            $this->flightRecorder = \lx::$app->diProcessor->createByInterface(
                FlightRecorderInterface::class,
                [], [],
                FlightRecorder::class
            );
            $this->flightRecorder->setOwner($this);
        }

        return $this->flightRecorder;
    }
    
    public function hasFlightRecords(): bool
    {
        if (!$this->flightRecorder) {
            return false;
        }

        return !$this->flightRecorder->isEmpty();
    }

    /**
     * @return array
     */
    public function getFlightRecords(): array
    {
        if (!$this->flightRecorder) {
            return [];
        }

        return $this->flightRecorder->getRecords();
    }

    /**
     * @return mixed
     */
    public function getFirstFlightRecord()
    {
        if (!$this->flightRecorder) {
            return null;
        }

        return $this->flightRecorder->getFirstRecord();
    }

    /**
     * @param mixed $record
     */
    public function addFlightRecord($record): void
    {
        $this->getFlightRecorder()->addRecord($record);
    }

    /**
     * @param array $records
     */
    public function addFlightRecords(array $records): void
    {
        $this->getFlightRecorder()->addRecords($records);
    }
    
    public function mergeFlightRecords(FlightRecorderHolderInterface $holder)
    {
        $this->addFlightRecords($holder->getFlightRecords());
    }
}

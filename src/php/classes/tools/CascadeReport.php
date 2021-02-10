<?php

namespace lx;

/**
 * Class CascadeReport
 * @package lx
 */
abstract class CascadeReport
{
    const ACTION_ADD_ONE = 'addTo';
    const ACTION_ADD_LIST = 'addListTo';

    const COMPONENT_LIST = 'list';
    const COMPONENT_DICT = 'dict';

    /** @var array */
    private $data = [];

    abstract protected function getDataComponents(): array;

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        list($actionType, $dataComponentName) = $this->splitMethod($name);
        if (!$actionType) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Cascade report has not method $name",
            ]);
            return;
        }

        if (!array_key_exists($dataComponentName, $this->getDataComponents())) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Cascade report has not data component $dataComponentName",
            ]);
            return;
        }

        switch ($actionType) {
            case self::ACTION_ADD_ONE:
                $this->addOne($dataComponentName, $arguments);
                break;
            case self::ACTION_ADD_LIST:
                $this->addList($dataComponentName, $arguments);
                break;
        }
    }

    /**
     * @param CascadeReport $report
     */
    public function add($report)
    {
        if (!$this->checkSubReport($report)) {
            return;
        }

        $arr = $report->toArray();
        $components = $this->getDataComponents();
        foreach ($components as $componentName => $componentType) {
            $this->addList($componentName, [$arr[$componentName] ?? []]);
        }
    }

    public function isEmpty(): bool
    {
        foreach ($this->data as $value) {
            if (!empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $name
     * @return array
     */
    public function extract($name)
    {
        if (!array_key_exists($name, $this->data)) {
            return [];
        }

        $result = $this->data[$name];
        unset($this->data[$name]);
        return $result;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @param CascadeReport $subReport
     * @return bool
     */
    public function checkSubReport($subReport)
    {
        return true;
    }


    /*******************************************************************************************************************
     * PRIVATE
     ******************************************************************************************************************/

    /**
     * @param string $dataComponentName
     * @param array $arguments
     */
    private function addOne($dataComponentName, $arguments)
    {
        if (empty($arguments)) {
            return;
        }

        if (!array_key_exists($dataComponentName, $this->data)) {
            $this->data[$dataComponentName] = [];
        }

        $componentType = $this->getDataComponents()[$dataComponentName];
        if ($componentType == self::COMPONENT_LIST) {
            if (!in_array($arguments[0], $this->data[$dataComponentName])) {
                $this->data[$dataComponentName][] = $arguments[0];
            }
        } elseif ($componentType == self::COMPONENT_DICT) {
            if (!array_key_exists($arguments[0], $this->data[$dataComponentName])) {
                $this->data[$dataComponentName][$arguments[0]] = $arguments[1] ?? null;
            }
        }
    }

    /**
     * @param string $dataComponentName
     * @param array $arguments
     */
    private function addList($dataComponentName, $arguments)
    {
        $arr = $arguments[0] ?? null;
        if (!is_array($arr)) {
            return;
        }

        if (!array_key_exists($dataComponentName, $this->data)) {
            $this->data[$dataComponentName] = [];
        }

        $componentType = $this->getDataComponents()[$dataComponentName];
        if ($componentType == self::COMPONENT_LIST) {
            $this->data[$dataComponentName] = array_unique(array_merge($this->data[$dataComponentName], $arr));
        } elseif ($componentType == self::COMPONENT_DICT) {
            $this->data[$dataComponentName] = array_merge($this->data[$dataComponentName], $arr);
        }
    }

    /**
     * @param string $name
     * @return array [actionType, dataComponentName]
     */
    private function splitMethod($name)
    {
        $regExp = '/^('
            . self::ACTION_ADD_ONE
            . '|' . self::ACTION_ADD_LIST
            . ')(.+)$/';
        preg_match($regExp, $name, $matches);
        if (empty($matches)) {
            return [null, null];
        }

        return [$matches[1], lcfirst($matches[2])];
    }
}

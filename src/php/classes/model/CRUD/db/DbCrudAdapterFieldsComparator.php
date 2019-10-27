<?php

namespace lx;

class DbCrudAdapterFieldsComparator
{
    private $modelSchema;
    private $tableSchema;

    public function __construct($modelSchema, $tableSchema)
    {
        $this->modelSchema = $modelSchema;
        $this->tableSchema = $tableSchema;
    }

    public function run()
    {
        list (
            $fieldNamesCommon,
            $fieldNamesModelOnly,
            $fieldNamesTableOnly
            ) = $this->splitFieldNames();

        $changed = [];
        foreach ($fieldNamesCommon as $name) {
            $diff = $this->compareFields(
                $name,
                $this->modelSchema['fields'][$name],
                $this->tableSchema[$name]
            );
            $changed = array_merge($changed, $diff);
        }

        $renamed = [];
        foreach ($fieldNamesModelOnly as $keyModelOnly => $nameModelOnly) {
            foreach ($fieldNamesTableOnly as $keyTableOnly => $nameTableOnly) {
                $diff = $this->compareFields(
                    $nameModelOnly,
                    $this->modelSchema['fields'][$nameModelOnly],
                    $this->tableSchema[$nameTableOnly]
                );

                if (empty($diff)) {
                    $renamed[] = [
                        'action' => MigrationMaker::ACTION_RENAME_FIELD,
                        'old' => $nameTableOnly,
                        'new' => $nameModelOnly,
                    ];
                    unset($fieldNamesModelOnly[$keyModelOnly]);
                    unset($fieldNamesTableOnly[$keyTableOnly]);
                }
            }
        }

        $added = [];
        foreach ($fieldNamesModelOnly as $name) {
            $added[] = [
                'action' => MigrationMaker::ACTION_ADD_FIELD,
                'name' => $name,
                'params' => $this->modelSchema['fields'][$name],
            ];
        }

        $deleted = [];
        foreach ($fieldNamesTableOnly as $name) {
            $deleted[] = [
                'action' => MigrationMaker::ACTION_REMOVE_FIELD,
                'name' => $name,
                'params' => $this->tableSchema[$name],
            ];
        }

        return array_merge($added, $deleted, $renamed, $changed);
    }

    private function compareFields($name, $modelFieldData, $tableFieldData)
    {
        $actions = [];

        $modelField = ModelField::create($name, $modelFieldData);
        $tableField = ModelField::create($name, $tableFieldData);

        $diff = $modelField->compare($tableField);
        foreach ($diff as $item) {
            $actions[] = [
                'action' => MigrationMaker::ACTION_CHANGE_FIELD,
                'fieldName' => $name,
                'property' => $item['property'],
                'old' => $item['compared'],
                'new' => $item['current']
            ];
        }

        return $actions;
    }

    private function splitFieldNames()
    {
        $fieldNamesTableOnly = array_diff(
            array_keys($this->tableSchema),
            array_keys($this->modelSchema['fields'])
        );
        $fieldNamesModelOnly = array_diff(
            array_keys($this->modelSchema['fields']),
            array_keys($this->tableSchema)
        );
        $fieldNamesCommon = array_diff(
            array_merge($fieldNamesModelOnly, array_keys($this->tableSchema)),
            array_merge($fieldNamesTableOnly, $fieldNamesModelOnly)
        );
        foreach ($fieldNamesTableOnly as $key => $name) {
            if ($this->tableSchema[$name]['type'] == 'pk' || $this->tableSchema[$name]['type'] == 'fk') {
                unset($fieldNamesTableOnly[$key]);
            }
        }

        return [
            $fieldNamesCommon,
            $fieldNamesModelOnly,
            $fieldNamesTableOnly
        ];
    }
}

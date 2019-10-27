<?php

namespace lx;

class DbCrudAdapterRelationsComparator
{
    private $serviceName;
    private $relations;
    private $dbRelations;

    public function __construct($serviceName, $relations, $dbRelations)
    {
        $this->serviceName = $serviceName;
        $this->relations = $relations;
        $this->dbRelations = $dbRelations;
    }

    public function run()
    {
        //TODO тут все переделывать придется. Сейчас половина захардкожена, сил уже нет это делать
        // надо будет делать не только многие-ко-многим. А как быть с переименованием сервиса, я вообще хз

        list (
            $commonRelations,
            $newRelations,
            $oldRelations
            ) = $this->splitRelations();

        $changed = [];
        foreach ($commonRelations as $name) {
            $diff = $this->compare($this->relations[$name], $this->dbRelations[$name]);
            $changed = array_merge($changed, $diff);
        }

        $renamed = [];
        foreach ($newRelations as $keyNew => $nameNew) {
            foreach ($oldRelations as $keyOld => $nameOld) {
                $diff = $this->compare($this->relations[$nameNew], $this->dbRelations[$nameOld]);

                if (empty($diff)) {
                    $renamed[] = [
                        'action' => MigrationMaker::ACTION_RENAME_RELATION,
                        'old' => $nameOld,
                        'new' => $nameNew,
                    ];
                    unset($newRelations[$keyNew]);
                    unset($oldRelations[$keyOld]);
                }
            }
        }

        $added = [];
        foreach ($newRelations as $name) {
            $added[] = [
                'action' => MigrationMaker::ACTION_ADD_RELATION,
                'name' => $name,
                'type' => 'many-to-many',
                'value' => trim($this->relations[$name], ']['),
            ];
        }

        $deleted = [];
        foreach ($oldRelations as $name) {
            $relData = $this->dbRelations[$name];
            $relModelName = $this->serviceName == $relData['serviceName']
                ? $relData['modelName']
                : $relData['serviceName'] . '.' . $relData['modelName'];

            $deleted[] = [
                'action' => MigrationMaker::ACTION_REMOVE_RELATION,
                'name' => $name,
                'type' => 'many-to-many',
                'params' => $relModelName,
            ];
        }

        return array_merge($added, $deleted, $renamed, $changed);
    }


    private function compare($relation, $dbRelation)
    {

        //TODO - ищем случаи, когда связь со старым названием замкнули на другую модель
        return [];

    }

    private function splitRelations()
    {
        $old = array_diff(
            array_keys($this->dbRelations),
            array_keys($this->relations)
        );
        $new = array_diff(
            array_keys($this->relations),
            array_keys($this->dbRelations)
        );
        $common = array_diff(
            array_merge($new, array_keys($this->dbRelations)),
            array_merge($old, $new)
        );

        return [$common, $new, $old];
    }
}

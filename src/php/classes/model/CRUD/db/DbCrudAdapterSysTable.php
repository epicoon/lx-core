<?php

namespace lx;

/**
 * Class DbCrudAdapterSysTable
 * @package lx
 */
class DbCrudAdapterSysTable {
    /** @var DbCrudAdapter */
    private $crud;
    /** @var DbTable */
    private $table;
    /** @var array */
    private $cache;

    /**
     * DbCrudAdapterSysTable constructor.
     * @param $crud DbCrudAdapter
     * @param $table DbTable
     */
    public function __construct($crud, $table)
    {
        $this->crud = $crud;
        $this->table = $table;
        $this->cache = [];
    }

    /**
     * @param $serviceName string
     * @param $modelName string
     * @return bool
     */
    public function tableExists($serviceName, $modelName)
    {
        return (bool)$this->getModelTableName($serviceName, $modelName);
    }

    /**
     * @param $serviceName string
     * @param $modelName string
     * @return string
     */
    public function getModelTableName($serviceName, $modelName)
    {
        $data = $this->getServiceData($serviceName);
        if ( ! $data) {
            return null;
        }

        if ( ! array_key_exists($modelName, $data['table'])) {
            return null;
        }

        return $data['table'][$modelName];
    }

    /**
     * @param $serviceName string
     * @param $modelName string
     * @return array
     */
    public function getModelRelations($serviceName, $modelName)
    {
        $data = $this->getServiceData($serviceName);
        if ( ! $data) {
            return [];
        }

        if ( ! array_key_exists('relation', $data)) {
            return [];
        }

        if ( ! array_key_exists($modelName, $data['relation'])) {
            return [];
        }

        return $data['relation'][$modelName];
    }

    /**
     * @param $serviceName string
     * @param $modelName string
     * @return string
     */
    public function defineModelTableName($serviceName, $modelName)
    {
        $name = $this->getModelTableName($serviceName, $modelName);
        if ( ! $name) {
            $snakeCase = lcfirst(preg_replace_callback('/(.)([A-Z])/', function($match) {
                return $match[1] . '_' . strtolower($match[2]) ;
            }, $modelName));

            $snakeCase = $this->avoidReservedNames($snakeCase);

            $i = 0;
            $tempName = $snakeCase;
            while ($this->table->getDb()->tableExists($tempName)) {
                $tempName = $snakeCase . (++$i);
            }

            $name = $tempName;
        }

        return $name;
    }

    /**
     * @param $serviceName string
     * @param $modelName string
     * @param $tableName string
     */
    public function createTable($serviceName, $modelName, $tableName)
    {
        $this->table->insert([
            'model_info' => $serviceName . '.' . $modelName,
            'table_name' => $tableName,
            'type' => 'model',
        ], false);

        if ( ! array_key_exists($serviceName, $this->cache)) {
            $this->cache[$serviceName]['table'] = [];
        }

        $this->cache[$serviceName]['table'][$modelName] = $tableName;
    }

    /**
     * @param $serviceName string
     * @param $modelName string
     * @param $tableName string
     */
    public function deleteTable($serviceName, $modelName, $tableName)
    {
        //TODO учесть возможные релейшены

        $this->table->delete([
            'model_info' => $serviceName . '.' . $modelName,
            'table_name' => $tableName,
            'type' => 'model',
        ]);

        unset($this->cache[$serviceName]['table'][$modelName]);
    }

    /**
     * @param $schema ModelSchema
     * @param $relSchema ModelSchema
     * @param $relationName string
     * @param $tableName string
     */
    public function createRelativeTable($schema, $relSchema, $relationName, $tableName)
    {
        $arr = $this->prepareRalativeTableData($schema, $relSchema, $relationName);
        $this->table->insert([
            'model_info' => $this->getRelativeString($arr),
            'table_name' => $tableName,
            'type' => 'relation',
        ], false);

        $this->addRelationData($arr[0], $arr[1], $tableName);
        $this->addRelationData($arr[1], $arr[0], $tableName);
    }

    /**
     * @param $schema ModelSchema
     * @param $relSchema ModelSchema
     * @param $relationName string
     * @param $tableName string
     */
    public function removeRelativeTable($schema, $relSchema, $relationName, $tableName)
    {
        $arr = $this->prepareRalativeTableData($schema, $relSchema, $relationName);
        $this->table->delete([
            'model_info' => $this->getRelativeString($arr),
            'table_name' => $tableName,
            'type' => 'relation',
        ]);

        $this->removeRelationData($arr[0]);
        $this->removeRelationData($arr[1]);
    }


    /**************************************************************************************************************************
     * PRIVATE
     *************************************************************************************************************************/

    /**
     * @param $serviceName string
     * @return array|null
     */
    private function getServiceData($serviceName)
    {
        $this->loadServiceData($serviceName);

        if ( ! array_key_exists($serviceName, $this->cache)) {
            return null;
        }

        return $this->cache[$serviceName];
    }

    /**
     * @param $serviceName string
     */
    private function loadServiceData($serviceName)
    {
        if (array_key_exists($serviceName, $this->cache)) {
            return;
        }

        $data = $this->table->select('model_info, table_name, type', "model_info LIKE '%$serviceName.%'");
        $this->cache[$serviceName] = [];
        foreach ($data as $row) {
            if ( ! array_key_exists($row['type'], $this->cache[$serviceName])) {
                $this->cache[$serviceName][$row['type']] = [];
            }

            if ($row['type'] == 'model') {
                $modelName = explode('.', $row['model_info'])[1];
                $this->cache[$serviceName]['table'][$modelName] = $row['table_name'];
            } elseif ($row['type'] == 'relation') {
                $relationData = $this->parseRelationData($row['model_info']);
                $this->addRelationData($relationData[0], $relationData[1], $row['table_name']);
                $this->addRelationData($relationData[1], $relationData[0], $row['table_name']);
            }
        }
    }

    /**
     * @param $data string ven/dor.modelName1:relationName|ven/dor.modelName2:relationName
     * @return array
     */
    private function parseRelationData($data)
    {
        $arr = preg_split('/[.:|]/', $data);
        return [
            [
                'serviceName' => $arr[0],
                'modelName' => $arr[1],
                'relation' => $arr[2],
            ],
            [
                'serviceName' => $arr[3],
                'modelName' => $arr[4],
                'relation' => $arr[5],
            ],
        ];
    }

    /**
     * @param $data array
     * @param $relData array
     * @param $tableName string
     */
    private function addRelationData($data, $relData, $tableName)
    {
        $data = DataObject::create($data);
        if ( ! array_key_exists($data->serviceName, $this->cache)) {
            $this->cache[$data->serviceName] = [];
        }

        if ( ! array_key_exists('relation', $this->cache[$data->serviceName])) {
            $this->cache[$data->serviceName]['relation'] = [];
        }

        if ( ! array_key_exists($data->modelName, $this->cache[$data->serviceName]['relation'])) {
            $this->cache[$data->serviceName]['relation'][$data->modelName] = [];
        }

        $this->cache[$data->serviceName]['relation'][$data->modelName][$data->relation] = array_merge($relData, [
            'table' => $tableName,
        ]);
    }

    /**
     * @param $data array
     */
    private function removeRelationData($data)
    {
        $data = DataObject::create($data);
        if (isset($this->cache[$data->serviceName]['relation'][$data->modelName][$data->relation])) {
            unset($this->cache[$data->serviceName]['relation'][$data->modelName][$data->relation]);
            if (empty($this->cache[$data->serviceName]['relation'][$data->modelName])) {
                unset($this->cache[$data->serviceName]['relation'][$data->modelName]);

                if (empty($this->cache[$data->serviceName]['relation'])) {
                    unset($this->cache[$data->serviceName]['relation']);

                    if (empty($this->cache[$data->serviceName])) {
                        unset($this->cache[$data->serviceName]);
                    }
                }
            }
        }
    }

    /**
     * @param $schema ModelSchema
     * @param $relSchema ModelSchema
     * @param $relationName string
     * @return array
     */
    private function prepareRalativeTableData($schema, $relSchema, $relationName)
    {
        $arr = [];
        $arr[$schema->getName()] = [
            'serviceName' => $schema->getProvider()->getService()->name,
            'modelName' => $schema->getName(),
            'relation' => $relationName,
        ];

        $rels = $relSchema->getRelationsForModel($schema->getName());
        //TODO - что-то будем придумывать со связями еще. Пока простой вариант - многие-ко-многим - одна связь
        $relRelationName = array_keys($rels)[0];

        $arr[$relSchema->getName()] = [
            'serviceName' => $relSchema->getProvider()->getService()->name,
            'modelName' => $relSchema->getName(),
            'relation' => $relRelationName,
        ];

        ksort($arr);
        return array_values($arr);
    }

    /**
     * @param $arr array
     * @return string
     */
    private function getRelativeString($arr)
    {
        return $arr[0]['serviceName'] . '.' . $arr[0]['modelName'] . ':' . $arr[0]['relation']
            . '|'
            . $arr[1]['serviceName'] . '.' . $arr[1]['modelName'] . ':' . $arr[1]['relation'];
    }

    /**
     * @param $name string
     * @return string
     */
    private function avoidReservedNames($name)
    {
        switch ($name) {
            // Postgresql reserved words
            case 'user': return 'users';
        }

        return $name;
    }
}

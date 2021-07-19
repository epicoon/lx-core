<?php

namespace lx;

class DbConnectionRegistry
{
	private array $list = [];
	
	public function add(array $settings): ?DbConnectionInterface
	{
		$key = $this->getKey($settings);
		if (array_key_exists($key, $this->list)) {
			$this->list[$key]['count']++;
            return $this->list[$key]['connection'];
		}

		$connectionClass = $this->getConnectionClass($settings);
        if (!ClassHelper::exists($connectionClass)) {
            \lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Connection class '$connectionClass' does not exist",
            ]);
            return null;
        }

        if (!ClassHelper::implements($connectionClass, DbConnectionInterface::class)) {
            \lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Connection class '$connectionClass' has to implement 'lx\\DbConnectionInterface'",
            ]);
            return null;
        }

        /** @var DbConnectionInterface $connection */
        $connection = new $connectionClass($settings);
        if (!$connection->connect()) {
            \lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Error while '$connectionClass' connection creating: " . $connection->getFirstFlightRecord(),
            ]);

            return null;
        }

        $this->list[$key] = [
            'type' => $settings['driver'],
            'connection' => $connection,
            'count' => 1,
        ];

        return $connection;
	}

	public function drop(array $settings): bool
	{
	    $key = $this->getKey($settings);
	    if (!array_key_exists($key, $this->list)) {
            \lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Error while connection close: connection '$key' does not exist",
            ]);
	        return false;
        }
	    
	    
        $this->list[$key]['count']--;

        if ($this->list[$key]['count'] == 0) {
            /** @var DbConnectionInterface $connection */
            $connection = $this->list[$key]['connection'];
            if (!$connection->disconnect()) {
                $this->list[$key]['count']++;
                \lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
                    '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
                    'msg' => "Error while '$key' connection close: " . $connection->getFirstFlightRecord(),
                ]);
                return false;
            }
            
            unset($this->list[$key]);
        }
        
        return true;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function getKey(array $settings): string
    {
        return $this->getConnectionClass($settings)
            . '_' . $settings['hostname']
            . '_' . $settings['username']
            . '_' . $settings['dbName'];
    }

    private function getConnectionClass(array $settings): string
    {
        /** @var DbConnector $connector */
        $connector = $settings['connector'];
        $map = $connector->getConnectionClassesMap();
        return $map[$settings['driver']];
    }
}

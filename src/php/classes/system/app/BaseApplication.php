<?php

namespace lx;

/**
 * Class BaseApplication
 * @package lx
 *
 * @property-read Language $language
 * @property-read I18nApplicationMap $i18nMap
 */
abstract class BaseApplication extends AbstractApplication
{
    /** @var array */
    protected $settings;

    protected static function getDefaultComponents(): array
    {
        return array_merge(parent::getDefaultComponents(), [
            'language' => Language::class,
            'i18nMap' => I18nApplicationMap::class,
        ]);
    }

    /**
     * @return array
     */
    public function getBuildData()
    {
        return [
            'settings' => $this->settings,
        ];
    }

    /**
     * @param array $data
     */
    public function applyBuildData($data)
    {
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getSetting($name)
    {
        if (array_key_exists($name, $this->settings))
            return $this->settings[$name];
        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function addSetting($name, $value)
    {
        $this->settings[$name] = $value;
    }

    /**
     * @param array|string $config
     */
    public function useI18n($config)
    {
        $map = [];
        if (is_array($config)) {
            if (isset($config['service'])) {
                if ($this->i18nMap->inUse($config['service'])) {
                    return;
                } else {
                    $this->i18nMap->noteUse($config['service']);
                }

                $map = $this->getService($config['service'])->i18nMap->getMap();
            } elseif (isset($config['plugin'])) {
                if ($this->i18nMap->inUse($config['plugin'])) {
                    return;
                } else {
                    $this->i18nMap->noteUse($config['plugin']);
                }

                $map = $this->getPlugin($config['plugin'])->i18nMap->getMap();
            }
        } elseif (is_string($config)) {
            $path = $this->conductor->getFullPath($config);
            if ($this->i18nMap->inUse($path)) {
                return;
            }

            $file = $this->diProcessor->createByInterface(DataFileInterface::class, [$path]);
            if ($file->exists()) {
                $this->i18nMap->noteUse($path);
                $data = $file->get();
                if (is_array($data)) {
                    $map = $data;
                }
            }
        }

        if (!empty($map)) {
            $this->i18nMap->add($map, true);
        }
    }
}

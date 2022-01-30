<?php

namespace lx;

abstract class BaseApplication extends AbstractApplication
{
    private array $settings = [];

    public function getDefaultFusionComponents(): array
    {
        return array_merge(parent::getDefaultFusionComponents(), [
            'language' => Language::class,
            'i18nMap' => ApplicationI18nMap::class,
        ]);
    }

    public function getBuildData(): array
    {
        return [
            'settings' => $this->getSettings(),
        ];
    }

    public function applyBuildData(array $data): void
    {
    }

    public function getSettings(): array
    {
        if (!array_key_exists('cssPreset', $this->settings)) {
            $this->settings['cssPreset'] = $this->presetManager->getDefaultCssPreset();
        }

        return $this->settings;
    }

    /**
     * @return mixed
     */
    public function getSetting(string $name)
    {
        $settings = $this->getSettings();
        return $settings[$name] ?? null;
    }

    /**
     * @param mixed $value
     */
    public function addSetting(string $name, $value)
    {
        $this->settings[$name] = $value;
    }

    /**
     * @param array|string $config
     */
    public function useI18n($config): void
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

<?php

namespace lx;

class JsTypeDocumentation
{
    private string $type;
    private array $constraints = [];
    /** @var array<JsParamDocumentation>|JsParamDocumentation */
    private $items;
    private ?JsLinkDocumentation $itemsLink = null;
    /** @var array<JsLinkDocumentation> */
    private array $mergeLinks = [];

    public function __construct(array $config) {
        $this->type = $config['type'];
        //TODO если типа нет

        if (array_key_exists('enum', $config)) {
            $this->constraints['enum'] = $config['enum'];
        }

        if ($this->type == 'Array' || $this->type == 'Dict') {
            $this->items = new JsParamDocumentation('', $config['items'] ?? []);
            return;
        }

        $items = $config['items'] ?? [];
        $this->items = [];
        foreach ($items as $key => $item) {
            if ($key === '#schema') {
                $this->itemsLink = new JsLinkDocumentation($item);
                break;
            }

            if ($key === '#merge') {
                foreach ($item as $linkData) {
                    $this->mergeLinks[] = new JsLinkDocumentation($linkData);
                }
                continue;
            }

            $this->items[$key] = new JsParamDocumentation($key, $item);
        }
    }

    public function toArray(): array
    {
        $result = ['type' => $this->type];
        if (!empty($this->constraints)) {
            $result['constraints'] = $this->constraints;
        }

        if ($this->itemsLink) {
            $def = $this->itemsLink->toArray();
            $defType = $def['type'] ?? null;
            if ($defType) {
                $result['items'] = $defType['items'] ?? [];
            }
            return $result;
        }

        $items = [];
        if (is_array($this->items)) {
            foreach ($this->items as $key => $item) {
                $items[$key] = $item->toArray();
            }
        } else {
            $items = $this->items->toArray();
        }

        if ($this->mergeLinks) {
            foreach ($this->mergeLinks as $link) {
                $def = $link->toArray();
                $defType = $def['type'] ?? null;
                if (!$defType) {
                    continue;
                }
                $items = array_merge($items, $defType['items'] ?? []);
            }
        }

        if (!empty($items)) {
            $result['items'] = $items;
        }

        return $result;
    }
}

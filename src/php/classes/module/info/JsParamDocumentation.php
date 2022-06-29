<?php

namespace lx;

class JsParamDocumentation
{
    /** @var string|int */
    private $key;
    private bool $isRequired;
    /** @var mixed */
    private $default;
    private ?string $comment = null;
    private ?JsTypeDocumentation $type = null;
    /** @var array<JsTypeDocumentation> */
    private array $typeAlternatives = [];

    /**
     * @param string|int $key
     */
    public function __construct($key, array $info)
    {
        $this->key = $key;
        $this->isRequired = $info['required'] ?? false;
        $this->default = $info['default'] ?? new Undefined();
        $this->comment = $info['comment'] ?? null;

        $types = [];
        if (array_key_exists('type', $info)) {
            $types = [$info];
        } elseif (array_key_exists('typeAlternatives', $info)) {
            $types = $info['typeAlternatives'];
        }

        if (count ($types) == 1) {
            $this->type = new JsTypeDocumentation($types[0]);
        } else {
            foreach ($types as $type) {
                $this->typeAlternatives[] = new JsTypeDocumentation($type);
            }
        }
    }
    
    public function toArray(): array
    {
        $result = ['required' => $this->isRequired];
        if (!($this->default instanceof Undefined)) {
            $result['undefined'] = $this->default;
        }
        if ($this->comment) {
            $result['comment'] = $this->comment;
        }
        
        if ($this->type) {
            $result['type'] = $this->type->toArray();
        }
        if (!empty($this->typeAlternatives)) {
            $result['typeAlternatives'] = [];
            foreach ($this->typeAlternatives as $type) {
                $result['typeAlternatives'][] = $type->toArray();
            }
        }
        
        return $result;
    }
}

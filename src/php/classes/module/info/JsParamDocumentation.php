<?php

namespace lx;

class JsParamDocumentation
{
    private string $name;
    private bool $isRequired;
    /** @var mixed */
    private $default;


    private string $type;
    private array $fields;

    
    public function __construct(string $name, array $info)
    {
        
        
    }

}

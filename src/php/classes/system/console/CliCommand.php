<?php

namespace lx;

/**
 * Class CliCommand
 * @package lx
 */
class CliCommand
{
    /** @var array */
    private $data;

    /**
     * CliCommand constructor.
     * @param array $config
     */
    public function __construct($config)
    {
        $this->data = $config;
        $this->data['command'] = (array)$this->data['command'];
    }

    /**
     * @return array
     */
    public function getNames()
    {
        return $this->data['command'];
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->data['type'] ?? CliProcessor::COMMAND_TYPE_COMMON;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->data['description'] ?? 'Description not defined';
    }

    /**
     * @return CliArgument[]
     */
    public function getArguments()
    {
        return $this->data['arguments'] ?? [];
    }

    /**
     * @return string|array|null
     */
    public function getExecutor()
    {
        return $this->data['handler'] ?? null;
    }

    /**
     * @param CliArgumentsList $arguments
     * @return array
     */
    public function validateInput($arguments)
    {
        $argumentsDefinition = $this->data['arguments'] ?? [];
        if (empty($argumentsDefinition)) {
            $arguments->setValidatedData([]);
            return [];
        }

        $validatedArgs = [];
        $errorsCounter = 0;
        $errors = [
            'required' => [],
            'typeMismatch' => [],
            'enumMismatch' => [],
        ];
        /** @var CliArgument $definition */
        foreach ($argumentsDefinition as $definition) {
            $key = $definition->getKey();
            $value = $arguments->get($key);
            if ($value === null) {
                if ($definition->isMandatory()) {
                    $errors['required'][] = $definition;
                    $errorsCounter++;
                }

                continue;
            }

            $validationResult = $definition->validateValue($value);
            if ($validationResult == CliArgument::PROBLEM_ENUM_MISMATCH) {
                $errors['enumMismatch'][] = $definition;
                $errorsCounter++;
                continue;
            } elseif ($validationResult == CliArgument::PROBLEM_TYPE_MISMATCH) {
                $errors['typeMismatch'][] = $definition;
                $errorsCounter++;
                continue;
            }

            $validatedArgs[] = [
                'keys' => (array)$key,
                'value' => $value,
            ];
        }

        if ($errorsCounter) {
            $report = [];
            if (!empty($errors['required'])) {
                $report[] = 'This command requres mandatory parameters:';
                foreach ($errors['required'] as $definition) {
                    $report[] = '* ' . implode(' or ', (array)$definition->getKey()) . '. '
                        . $definition->getDescription();
                }
            }
            if (!empty($errors['typeMismatch'])) {
                $report[] = 'Parameter type mismatches:';
                foreach ($errors['typeMismatch'] as $definition) {
                    $report[] = '* ' . implode(' or ', (array)$definition->getKey()) . ': '
                        . $definition->getType() . '. ' . $definition->getDescription();
                }
            }
            if (!empty($errors['enumMismatch'])) {
                $report[] = 'Parameter enum mismatches:';
                foreach ($errors['enumMismatch'] as $definition) {
                    $report[] = '* ' . implode(' or ', (array)$definition->getKey()) . ': '
                        . 'available values - ' . implode(', ', $definition->getEnum()) . '. '
                        . $definition->getDescription();
                }
            }
            return $report;
        }

        $arguments->setValidatedData($validatedArgs);
        return [];
    }
    
    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }
}

<?php

namespace lx;

class CliCommand
{
    private array $data;

    public function __construct(array $config)
    {
        $this->data = $config;
        $this->data['command'] = (array)$this->data['command'];
    }

    public function getNames(): array
    {
        return $this->data['command'];
    }

    public function getType(): int
    {
        return $this->data['type'] ?? CliProcessor::COMMAND_TYPE_COMMON;
    }

    public function getDescription(): string
    {
        return $this->data['description'] ?? 'Description not defined';
    }

    /**
     * @return array<CliArgument>
     */
    public function getArguments(): array
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

    public function validateInput(CliArgumentsList $arguments): array
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
            $key = $definition->getKeys();
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
                    $report[] = '* ' . implode(' or ', $definition->getKeys()) . '. '
                        . $definition->getDescription();
                }
            }
            if (!empty($errors['typeMismatch'])) {
                $report[] = 'Parameter type mismatches:';
                foreach ($errors['typeMismatch'] as $definition) {
                    $report[] = '* ' . implode(' or ', $definition->getKeys()) . ': '
                        . $definition->getType() . '. ' . $definition->getDescription();
                }
            }
            if (!empty($errors['enumMismatch'])) {
                $report[] = 'Parameter enum mismatches:';
                foreach ($errors['enumMismatch'] as $definition) {
                    $report[] = '* ' . implode(' or ', $definition->getKeys()) . ': '
                        . 'available values - ' . implode(', ', $definition->getEnum()) . '. '
                        . $definition->getDescription();
                }
            }
            return $report;
        }

        $arguments->setValidatedData($validatedArgs);
        return [];
    }
    
    public function toArray(): array
    {
        return $this->data;
    }
}

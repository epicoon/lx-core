<?php

namespace lx;

abstract class AbstractCommand implements CommandInterface
{
    /**
     * @return CommandExecutorInterface|string|array|null
     */
    abstract public function getExecutor();

    abstract public function getName(): string;

    public function getDescription(): string
    {
        return 'Description not defined';
    }

    /**
     * @return array<CommandArgument>
     */
    public function getArgumentsSchema(): array
    {
        return [];
    }

    public function validateInput(CommandArgumentsList $arguments): array
    {
        $argumentsDefinition = $this->getArgumentsSchema();
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
        foreach ($argumentsDefinition as $definition) {
            $keys = $definition->getKeys();
            if (!$arguments->has($keys) && ($definition->withInput() || $definition->withSelect())) {
                continue;
            }
            $value = $arguments->get($keys);
            $validationResult = $definition->validateValue($value);
            switch ($validationResult) {
                case CommandArgument::PROBLEM_NO:
                    if ($arguments->has($keys)) {
                        $validatedArgs[] = [
                            'keys' => $keys,
                            'value' => $value,
                        ];
                    }
                    break;
                case CommandArgument::PROBLEM_REQUIRED:
                    $errors['required'][] = $definition;
                    break;
                case CommandArgument::PROBLEM_ENUM_MISMATCH:
                    $errors['enumMismatch'][] = $definition;
                    break;
                case CommandArgument::PROBLEM_TYPE_MISMATCH:
                    $errors['typeMismatch'][] = $definition;
                    break;
            }
            if ($validationResult !== CommandArgument::PROBLEM_NO) {
                $errorsCounter++;
            }
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

    public function getInputRequire(CommandArgumentsList $arguments): ?CommandArgument
    {
        $argumentsDefinition = $this->getArgumentsSchema();
        if (empty($argumentsDefinition)) {
            return null;
        }
        /** @var CommandArgument $definition */
        foreach ($argumentsDefinition as $definition) {
            if (!$definition->withInput() && !$definition->withSelect()) {
                continue;
            }

            $keys = $definition->getKeys();
            if (!$arguments->has($keys)) {
                return $definition;
            }

            $value = $arguments->get($keys);
            if ($definition->isMandatory() && ($value === '' || $value === null)) {
                return $definition;
            }
        }

        return null;
    }
}

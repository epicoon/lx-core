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
        /** @var CommandArgument $definition */
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
            if ($validationResult == CommandArgument::PROBLEM_ENUM_MISMATCH) {
                $errors['enumMismatch'][] = $definition;
                $errorsCounter++;
                continue;
            } elseif ($validationResult == CommandArgument::PROBLEM_TYPE_MISMATCH) {
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
}

<?php

namespace lx;

class ErrorHelper
{
    public static function renderErrorString(\Throwable $exception, array $additionalData = [])
    {
        $errorString = '>>> Application error [code=' . $exception->getCode() . ']:' . PHP_EOL;
        foreach ($additionalData as $key => $value) {
            $errorString .= '- ' . $key . ': ' . $value . PHP_EOL;
        }
        $errorString .= '- file: ' . $exception->getFile() . '(' . $exception->getLine() . ')' . PHP_EOL;
        $errorString .= '- message: ' . $exception->getMessage() . PHP_EOL;
        $errorString .= '- trace:' . PHP_EOL;
        $trace = [];
        foreach ($exception->getTrace() as $i => $item) {
            if (array_key_exists('class', $item)) {
                if (array_key_exists('file', $item)) {
                    $trace[] = '  #' . $i . ' ' . $item['file'] . '(' . $item['line'] . '): '
                        . $item['class'] . $item['type'] . $item['function'];
                } else {
                    $trace[] = '  #' . $i . ' ' . $item['class'] . $item['type'] . $item['function'];
                }
            } else {
                $trace[] = '  #' . $i . ' ' . $item['file'] . '(' . $item['line'] . ')';
            }
        }
        $errorString .= implode(PHP_EOL, $trace);
        return $errorString;
    }
}

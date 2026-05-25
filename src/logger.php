<?php

function appLog(string $message, array $context = []): void
{
    $logDir = __DIR__ . '/../logs';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

    if ($context) {
        $line .= ' ' . json_encode($context);
    }

    file_put_contents($logDir . '/app.log', $line . PHP_EOL, FILE_APPEND);
}


<?php

namespace Northrook\Logger;

use Psr\Log as Psr;

final class Logger extends Psr\AbstractLogger
{
    use Psr\LoggerTrait;

    private array $entries = [];

    public function log($level, $message, array $context = []): void
    {
        $this->entries[] = [$level, $message, $context];
    }

    public function getLogs() : array{
        return $this->entries;
    }

    public function cleanLogs(): array
    {
        $logs = $this->entries;
        $this->entries = [];

        return $logs;
    }

    public function __sleep(): array
    {
        throw new \BadMethodCallException('Cannot serialize '.__CLASS__);
    }

    public function __wakeup(): void
    {
        throw new \BadMethodCallException('Cannot unserialize '.__CLASS__);
    }

    public function __destruct()
    {
        foreach ($this->entries as [$level, $message, $context]) {
            if (str_contains($message, '{')) {
                foreach ($context as $key => $val) {
                    if (null === $val || \is_scalar($val) || (\is_object($val) && \is_callable([$val, '__toString']))) {
                        $message = str_replace("{{$key}}", $val, $message);
                    } elseif ($val instanceof \DateTimeInterface) {
                        $message = str_replace("{{$key}}", $val->format(\DateTimeInterface::RFC3339), $message);
                    } elseif (\is_object($val)) {
                        $message = str_replace("{{$key}}", '[object '.get_debug_type($val).']', $message);
                    } else {
                        $message = str_replace("{{$key}}", '['.\gettype($val).']', $message);
                    }
                }
            }

            error_log(sprintf('%s [%s] %s', date(\DateTimeInterface::RFC3339), $level, $message));
        }
    }

}
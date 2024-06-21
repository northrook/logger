# Logger

[PSR-3 compliant](https://www.php-fig.org/psr/psr-3/) logging implementation, for easy global logging.

The package provides two key classes:

```php
Northrook\Logger();     // a PSR-3 compliant logger.
Northrook\Logger\Log(); // a static accessor to any PSR-3 compliant logger. 
```

The goal of this package is to provide easy logging across your PHP application,
especially in scenarios where dependency injection may be cumbersome or impractical.

Using the static `Log` class, you can easily log directly to a `LoggerInterface` instance.

Think of it as a _facade_ or _proxy_ to a `LoggerInterface` instance.

If you are a stickler for OOP, you can just use the `Logger` class directly.

## Installation

Install the latest version with composer:

```bash
composer require northrook/logger
```

## Basic Usage

The `Log` class is a static accessor to a set `LoggerInterface`..

```php
use Northrook\Logger\Log;

Log::info( 'Hello World!' );
```

When any of the `Log` methods are called, the logger will instantiate a new `Logger` object if it has not been instantiated yet.

The included `Logger` will be the default.

### Assigning a Logger

You can manually assign a `LoggerInterface` using `Log::setLogger()`:

```php
use Northrook\Logger\Log;

Log::setLogger( 
    logger: new Logger(), // LoggerInterface
    import: true,         // bool - default: true
);
```

If `setLogger` is provided a `Northrook\Logger` instance, it will import any log entries any previous `LoggerInterface`.

If you want to just override the current `LoggerInterface` without importing, pass `false` as the second argument:

```php
Log::setLogger( 
    logger: new Logger(), 
    import: false, 
);
```

This is useful when you need to instantiate an arbitrary `LoggerInterface` earlier in your code,
and later use the included `Northrook\Logger` class.

The `Log` will act as a proxy to the `LoggerInterface` instance, using the included `Northrook\Logger` class is not required at all.

## Log - Static Accessor

It provides all the PSR-3 methods, with a few extras.

The arbitrary `log()` is replaced by the `Log::entry()` method.

### Logging Exceptions

The `Log` class provides a method to easily log exceptions:

```php
use Northrook\Logger\Log;

try {
    $variable = \file_get_contents( 'data.json' );
} catch( \Exception $exception ) {
    Log::exception( 
        $exception,   // required
        level: null,  // optional
        message: null // optional
        context: [],  // optional
     );
}

// logged as:
0 => 'warning',
1 => 'ile_get_contents(data.json): Failed to open stream: No such file or directory',
2 => [ 'exception' => $exception ],

```

It will parse the exception and log it accordingly.

It will not overwrite the `$level` or `$message` if they are provided.

The `$context['exception']` will be set to the provided `$$exception`.

### Precision Timestamps

When setting a `LoggerInterfacing` using `Log::setLogger()`, you can pass a `bool $precision` argument, setting the static `$enablePrecision` property.

>[!IMPORTANT]
> The default value is `true`.
> It is recommended to set this value according to your environment, as it can be expensive in production. 

When `Log::setLogger()` is first called, a static `int` will be assigned to the `hrtime(true)`. This is used to calculate the `DeltaMs` and `OffsetMs` values.

Each `Log::entry()` has the `?bool $precision` argument, which is `null` by default, using the static `$enablePrecision` property.

Use this to set `$precision` for the current `Log::entry()` call.

```php
use Northrook\Logger\Log;

Log::setLogger( 
    logger: new Logger(), 
    import: true, 
    precision: true, // default: true
);

// enable precision for the current entry
Log::entry( 'Hello World!', precision: true );

// disable precision for the current entry
Log::entry( 'Hello World!', precision: false );
```

Entries logged with `$precision` will have the following keys added to the `$context` array:
```php
'precision' => [
    "hrTime" => 330531205286100 // The hrtime at the time of the log entry
    "hrDelta" => 1081000        // The difference the current entry and first `Log::entry()` call
    "DeltaMs" => "1.08ms"       // Time since initial `Log::setLogger()` call in milliseconds
    "OffsetMs" => "0.0079ms"    // Time since the previous `Log::entry( .. precision: true )` call in milliseconds
]
```

## Logger

The provided `Logger` class is a PSR-3 compliant logger, extending the `Psr\Log\AbstractLogger`, implementing the `Psr\Log\LoggerInterface` interface.

It provides access to all the PSR-3 methods, and is a drop-in replacement for any `Psr\Log\LoggerInterface` instance.

In addition, it a few simple methods for managing log entries:

```php
$logger = new Northrook\Logger();

$logger->log( ... )          // log an entry using the PSR-3 standard
$logger->hasLogs() : bool    // check if there are any log entries
$logger->getLogs() : array   // get all log entries, without manipulating them
$logger->cleanLogs() : array // get all log entries, and clear them
$logger->clear()             // clear all log entries, without getting them
$logger->count() : int       // count all log entries
$logger->import( $logger )   // import log entries from another LoggerInterface
$logger->printLogs() : array // get an array of each entry as a human-readable string
```

The `printLogs()` method is useful for quickly printing all log entries.

It will **not** prefix a timestamp by default. Pass `true` as the first argument to prefix the timestamp.

```php
// example:
$logger->entries = [ 
    0 => 'warning',
    1 => 'ile_get_contents(data.json): Failed to open stream: No such file or directory',
    2 => [ 'exception' => $exception ],
];

// default:
0 => 'Warning: file_get_contents(data.json): Failed to open stream: No such file or directory'

// with timestamp:
0 => '[2024-06-20T06:47:47+00:00] Warning: file_get_contents(data.json): Failed to open stream: No such file or directory'

```

If the Logger is destroyed without first calling `cleanLogs()`, the `printLogs()` method will print the logs to the PHP error log.

## License

Licensed under the [MIT Licence](LICENSE), and is free to use in any project.

### Credits

[BufferingLogger](https://github.com/symfony/error-handler/blob/master/BufferingLogger.php) - Nicolas Grekas <p@tchwork.com>
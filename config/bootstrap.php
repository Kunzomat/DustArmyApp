<?php
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
  foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    $_ENV[$k] = $v;
  }
}

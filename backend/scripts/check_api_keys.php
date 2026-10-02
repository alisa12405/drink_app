<?php

declare(strict_types=1);

/**
 * Verify server-side API credentials without displaying their values.
 *
 * Run in Docker:
 *   docker compose exec app php scripts/check_api_keys.php
 *   docker compose exec app php scripts/check_api_keys.php --weather-provider=weatherapi
 *
 * Or, when PHP is installed on the host:
 *   php backend/scripts/check_api_keys.php --weather-provider=openweather
 *
 * The script reads environment variables first, then root .env and backend/.env.
 * Weather providers supported: openweather (default), weatherapi.
 */

const REQUEST_TIMEOUT_SECONDS = 15;
const DEFAULT_WEATHER_PROVIDER = 'openweather';

/** @return array{status: int, body: string, error: ?string} */
function get(string $url, array $headers = []): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => REQUEST_TIMEOUT_SECONDS,
            'ignore_errors' => true,
        ],
    ]);

    $error = null;
    set_error_handler(static function (int $severity, string $message) use (&$error): bool {
        $error = $message;

        return true;
    });
    $body = file_get_contents($url, false, $context);
    restore_error_handler();

    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $header, $matches) === 1) {
            $status = (int) $matches[1];
            break;
        }
    }

    return [
        'status' => $status,
        'body' => is_string($body) ? $body : '',
        'error' => $error,
    ];
}

function readDotEnvValue(string $file, string $key): ?string
{
    if (! is_file($file)) {
        return null;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return null;
    }

    foreach ($lines as $line) {
        if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/', $line, $matches) !== 1) {
            continue;
        }

        $value = trim($matches[1]);
        if (strlen($value) >= 2 && $value[0] === '"' && $value[-1] === '"') {
            $value = substr($value, 1, -1);
        }

        return $value === '' ? null : $value;
    }

    return null;
}

function setting(string $key): ?string
{
    $fromEnvironment = getenv($key);
    if (is_string($fromEnvironment) && $fromEnvironment !== '') {
        return $fromEnvironment;
    }

    $projectRoot = dirname(__DIR__, 2);
    foreach (["{$projectRoot}/.env", "{$projectRoot}/backend/.env"] as $file) {
        $value = readDotEnvValue($file, $key);
        if ($value !== null) {
            return $value;
        }
    }

    return null;
}

function errorSummary(string $body, ?string $transportError, string $secret): string
{
    if ($transportError !== null) {
        return 'network error: '.str_replace($secret, '[redacted]', $transportError);
    }

    $decoded = json_decode($body, true);
    if (is_array($decoded)) {
        $message = $decoded['error']['message'] ?? $decoded['message'] ?? null;
        if (is_string($message) && $message !== '') {
            return str_replace($secret, '[redacted]', $message);
        }
    }

    return $body === '' ? 'empty response' : 'unexpected response';
}

function report(string $service, bool $ok, string $detail): void
{
    printf("[%s] %s — %s\n", $ok ? 'OK' : 'FAIL', $service, $detail);
}

function weatherProvider(array $arguments): string
{
    foreach ($arguments as $argument) {
        if (str_starts_with($argument, '--weather-provider=')) {
            return strtolower(substr($argument, strlen('--weather-provider=')));
        }
    }

    return strtolower(setting('WEATHER_API_PROVIDER') ?? DEFAULT_WEATHER_PROVIDER);
}

function weatherUrl(string $provider, string $apiKey): ?string
{
    return match ($provider) {
        'openweather', 'openweathermap' => 'https://api.openweathermap.org/data/2.5/weather?lat=10.7769&lon=106.7009&units=metric&appid='.rawurlencode($apiKey),
        'weatherapi', 'weatherapi.com' => 'https://api.weatherapi.com/v1/current.json?key='.rawurlencode($apiKey).'&q=10.7769%2C106.7009',
        default => null,
    };
}

$openAiKey = setting('OPENAI_API_KEY');
$weatherKey = setting('WEATHER_API_KEY');
$allPassed = true;

if ($openAiKey === null) {
    report('OpenAI', false, 'OPENAI_API_KEY is missing.');
    $allPassed = false;
} else {
    $result = get('https://api.openai.com/v1/models', [
        'Authorization: Bearer '.$openAiKey,
        'Accept: application/json',
        'User-Agent: smart-drink-key-check/1.0',
    ]);

    $isSuccess = $result['status'] >= 200 && $result['status'] < 300;
    report('OpenAI', $isSuccess, $isSuccess
        ? 'credential accepted (HTTP '.$result['status'].')'
        : 'HTTP '.$result['status'].': '.errorSummary($result['body'], $result['error'], $openAiKey));
    $allPassed = $allPassed && $isSuccess;
}

$provider = weatherProvider($argv);
if ($weatherKey === null) {
    report('Weather', false, 'WEATHER_API_KEY is missing.');
    $allPassed = false;
} elseif (($url = weatherUrl($provider, $weatherKey)) === null) {
    report('Weather', false, "unsupported provider '{$provider}'. Use openweather or weatherapi.");
    $allPassed = false;
} else {
    $result = get($url, ['Accept: application/json', 'User-Agent: smart-drink-key-check/1.0']);
    $isSuccess = $result['status'] >= 200 && $result['status'] < 300;
    report('Weather/'.$provider, $isSuccess, $isSuccess
        ? 'credential accepted (HTTP '.$result['status'].')'
        : 'HTTP '.$result['status'].': '.errorSummary($result['body'], $result['error'], $weatherKey));
    $allPassed = $allPassed && $isSuccess;
}

exit($allPassed ? 0 : 1);

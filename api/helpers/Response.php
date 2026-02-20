<?php
/**
 * Standardised JSON response helper.
 * All methods terminate execution after sending headers + body.
 */
class Response {

    /**
     * Success response.
     *
     * @param mixed       $data
     * @param string      $message
     * @param int         $status   HTTP status code
     * @param array|null  $meta     Optional pagination metadata
     */
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200, ?array $meta = null): never {
        self::send([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'meta'    => $meta,
        ], $status);
    }

    /**
     * Created response (201).
     */
    public static function created(mixed $data = null, string $message = 'Created'): never {
        self::success($data, $message, 201);
    }

    /**
     * Error response.
     *
     * @param string $message   Human-readable message
     * @param int    $status    HTTP status code
     * @param string $error     Machine-readable error code
     * @param array  $errors    Field-level validation errors
     */
    public static function error(string $message, int $status = 400, string $error = '', array $errors = []): never {
        $body = [
            'success' => false,
            'message' => $message,
            'error'   => $error ?: self::defaultCode($status),
        ];
        if (!empty($errors)) {
            $body['errors'] = $errors;
        }
        self::send($body, $status);
    }

    public static function unauthorized(string $message = 'Unauthorized'): never {
        self::error($message, 401, 'UNAUTHORIZED');
    }

    public static function forbidden(string $message = 'Forbidden'): never {
        self::error($message, 403, 'FORBIDDEN');
    }

    public static function notFound(string $message = 'Not found'): never {
        self::error($message, 404, 'NOT_FOUND');
    }

    public static function validationError(array $errors, string $message = 'Validation failed'): never {
        self::error($message, 422, 'VALIDATION_ERROR', $errors);
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private static function send(array $body, int $status): never {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    private static function defaultCode(int $status): string {
        return match ($status) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'UNPROCESSABLE',
            429 => 'TOO_MANY_REQUESTS',
            500 => 'INTERNAL_ERROR',
            503 => 'SERVICE_UNAVAILABLE',
            default => 'ERROR',
        };
    }
}

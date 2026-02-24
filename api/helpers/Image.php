<?php
/**
 * Image URL helper.
 *
 * Converts a relative upload path stored in the database (e.g. /uploads/logos/x.jpg)
 * into a fully-qualified URL using APP_URL from constants.
 *
 * Usage:
 *   imageUrl($row['business_logo'])
 *   // → "http://dealmachan-api.local/uploads/logos/x.jpg"
 *
 * Returns null when the path is empty, so callers can handle missing images cleanly.
 */
function imageUrl(?string $path): ?string {
    if (empty($path)) {
        return null;
    }
    // Already a full URL — return unchanged
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
        return $path;
    }
    return APP_URL . '/' . ltrim($path, '/');
}

/**
 * Apply imageUrl() to a specific field across an array of rows.
 * Modifies the array in-place.
 *
 * Usage:
 *   imageUrlField($merchants, 'business_logo');
 */
function imageUrlField(array &$rows, string $field): void {
    foreach ($rows as &$row) {
        if (array_key_exists($field, $row)) {
            $row[$field] = imageUrl($row[$field]);
        }
    }
}

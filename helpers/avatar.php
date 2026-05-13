<?php

function inventra_avatar_url(mixed $avatar): ?string
{
    if (!is_string($avatar) || trim($avatar) === '') {
        return null;
    }

    $avatar = str_replace('\\', '/', trim($avatar));

    if (preg_match('#^data:image/(?:jpeg|jpg|png);base64,#i', $avatar) === 1) {
        return $avatar;
    }

    if (preg_match('#^https?://#i', $avatar) === 1) {
        return $avatar;
    }

    if (strpos($avatar, './') === 0) {
        $avatar = substr($avatar, 2);
    }

    if (strpos($avatar, 'public/') === 0) {
        $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $avatar);

        if (!is_file($absolutePath)) {
            return null;
        }

        $baseUrl = defined('BASE_URL') ? BASE_URL : './';
        return $baseUrl . ltrim($avatar, '/');
    }

    return $avatar;
}

function inventra_avatar_data_uri(string $filePath, string $mimeType): ?string
{
    $mimeType = strtolower(trim($mimeType));

    if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
        return null;
    }

    if ($filePath === '' || !is_file($filePath) || !is_readable($filePath)) {
        return null;
    }

    $contents = file_get_contents($filePath);

    if ($contents === false) {
        return null;
    }

    return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
}

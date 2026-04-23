<?php

namespace App\Helpers;

class IconHelper
{
    public static function forExtension(string $ext): string
    {
        return match (strtolower($ext)) {
            'pdf'  => 'fas fa-file-pdf text-danger',
            'docx' => 'fas fa-file-word text-primary',
            'md'   => 'fas fa-file-code text-success',
            'html' => 'fas fa-file-code text-warning',
            default => 'fas fa-file text-secondary',
        };
    }
}

<?php

namespace BoldMinded\Ansel\Service\Sources;

class UploadLocation
{
    public function __construct(
        public int    $uploadLocationId = 0,
        public int    $directoryId = 0,
        public string $type = 'ee',
    ) {}

    public static function getUploadLocationByIdentifier($identifier): UploadLocation
    {
        // It's a legacy value for File Manager compatibility mode.
        if (str_contains($identifier, ':')) {
            $parts = explode(':', $identifier);

            return new self(
                uploadLocationId: (int) $parts[1] ?? 0,
                type: $parts[0] ?? ''
            );
        }

        // Possible legacy value without a subdirectory
        if (
            !str_contains($identifier, '.') &&
            is_numeric($identifier)
        ) {
            return new self(
                uploadLocationId: (int) $identifier
            );
        }

        $parts = explode('.', $identifier);

        return new self(
            uploadLocationId: (int) $parts[0],
            directoryId: (int) ($parts[1] ?? 0)
        );
    }
}

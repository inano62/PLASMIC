<?php

namespace App\Domain\Object\Import;

class ImportForCreate
{
    /**
     * @param string $filename
     * @param string $mimeType
     * @param string $content
     * @param int $type
     */
    public function __construct(
        public readonly string $filename,
        public readonly string $mimeType,
        public readonly string $content,
        public readonly int $type
    )
    {
    }
}

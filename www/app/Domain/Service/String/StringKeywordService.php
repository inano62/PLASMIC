<?php

namespace App\Domain\Service\String;

interface StringKeywordService
{
    /**
     * @param ?string $value
     * @return array
     */
    public function parse(?string $value): array;
}

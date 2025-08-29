<?php

namespace App\Domain\Writer;

interface InvalidUserWriter
{
    /**
     * @param \App\Domain\Object\InvalidUser\InvalidUserForImport $invalidUsers
     * @return void
     */
    public function importBulk(array $invalidUsers);
}

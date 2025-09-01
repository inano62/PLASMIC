<?php

namespace App\Domain\Writer;

interface UserDuplicatedWriter
{
    /**
     * @param \App\Domain\Object\UserDuplicated\UserDuplicatedForImportCreate[] $users
     * @return void
     */
    public function importCreateBulk(array $users);

    /**
     * @param \App\Domain\Object\UserDuplicated\UserDuplicatedForImportUpdate[] $users
     * @return void
     */
    public function importUpdateBulk(array $users);

    /**
     * @param string $email
     * @return void
     */
    public function deleteByEmail(string $email);
}

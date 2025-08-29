<?php

namespace App\View\Console\Command;

class TemporaryCommand extends AbstractCommand
{
    /**
     * @var string
     */
    protected $signature = 'temporary';

    /**
     * @var string
     */
    protected $description = 'Temporary Command';

    /**
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit', '512M');
        

        // (new \App\View\Console\Command\Temporary\CourseAssumeCommand())->handle();
        // (new \App\View\Console\Command\Temporary\Debug01Command())->handle();
        // (new \App\View\Console\Command\Temporary\TruncateApplicationCommand())->handle();
        // (new \App\View\Console\Command\Temporary\CurriculumImportCommand())->handle();
        // (new \App\View\Console\Command\Temporary\UserTokenGenerateCommand())->handle();
        // (new \App\View\Console\Command\Temporary\ManualApproveCommand())->handle();
        // (new \App\View\Console\Command\Temporary\ManualApplicationDeleteCommand())->handle();
        // (new \App\View\Console\Command\Temporary\CopyApplicationPriceCommand())->handle();
        // (new \App\View\Console\Command\Temporary\UcanZipImportCommand())->handle();
        // (new \App\View\Console\Command\Temporary\UcanExportCommand())->handle();
        // (new \App\View\Console\Command\Temporary\CurriculumProviderImportCommand())->handle();
    }
}

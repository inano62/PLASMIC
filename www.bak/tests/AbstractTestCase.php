<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use App\Adapter\Injector\Injector;

abstract class AbstractTestCase extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->createApplication();
        \Artisan::call('migrate:refresh');
        \Artisan::call('db:seed');
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * @param string $class
     * @return mix
     */
    protected function getImplement(string $class)
    {
        return (new Injector())[$class];
    }

    /**
     * @param mix $inputData
     * @return mix
     */
    protected function handleUseCase($inputData)
    {
        return (new Injector())->handleUseCase($inputData);
    }

    /**
     * @access protected
     * @param callable $func
     * @param ?string $className
     * @return void
     */
    protected function assertThrowException(callable $func, ?string $className = NULL)
    {
        try {
            $func();
        } catch (\Exception $e) {
            $this->assertInstanceOf($className, $e);
            return;
        }
        throw new \Exception();
    }

    protected function getUploadedFile(string $filename): UploadedFile
    {
        $filepath = base_path('tests' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $filename);
        if (!file_exists($filepath)) { throw new \Exception("$filename is not found"); }

        return new UploadedFile(
            $filepath,
            $filename,
            mime_content_type($filepath),
            null,
            true
        );
    }
}

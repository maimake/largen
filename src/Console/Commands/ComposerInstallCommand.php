<?php

namespace Maimake\Largen\Console\Commands;

use Maimake\Largen\Contracts\NsPath\NsPath;
use Maimake\Largen\Services\ComposerParser;

class ComposerInstallCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:composer:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Composer install(Include modules)';

    protected function askMoreInfo()
    {
    }

    protected function generateFiles()
    {
        $this->mergeComposerRequire();
        $this->systemOrFail('composer install');
    }

    private function createComposerParserFromNsPath($nsPath)
    {
        /**
         * @var NsPath $nsPath
         */
        $composer_file = $nsPath->getRootBasePath('composer.json');
        return new ComposerParser($composer_file);
    }

    private function mergeComposerRequire() {
        $parsers = $this->getAppAndModulesPathServices();

        $appComposerParser = $this->createComposerParserFromNsPath($parsers['app']);

        foreach ($parsers as $key => $nsPath)
        {
            if ($key != 'app')
            {
                $moduleComposerParser = $this->createComposerParserFromNsPath($nsPath);
                $appComposerParser->mergeRequireFrom($moduleComposerParser);
            }
        }
        $appComposerParser->save();
    }
}

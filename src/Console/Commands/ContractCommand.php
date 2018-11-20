<?php

namespace Maimake\Largen\Console\Commands;

class ContractCommand extends GeneratorCommand
{
    protected $signature = 'largen:contract {name : Contract Name}';

    protected $description = 'Create a new Contract Interface';

    protected function askMoreInfo()
    {
    }


    protected function generateFiles()
    {
        $info = $this->nsPath->getClassInfoByType('contract', $this->argument('name'));
        $this->template('Contract.php', $info['output'], $info);
    }
}






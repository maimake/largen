<?php

namespace Maimake\Largen\Support\Log;
use \Illuminate\Log\LogManager as Base;

class LogManager extends Base
{
    protected $name;

    public function __construct($app, $name)
    {
        parent::__construct($app);
        $this->name = $name;
    }

    /**
     * Create an instance of the single file log driver.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createSingleDriver(array $config)
    {
        $config['path'] = add_path_suffix($config['path'], "-$this->name-" . PHP_SAPI);
        return parent::createSingleDriver($config);
    }

    /**
     * Create an instance of the daily file log driver.
     *
     * @param  array  $config
     * @return \Psr\Log\LoggerInterface
     */
    protected function createDailyDriver(array $config)
    {
        $config['path'] = add_path_suffix($config['path'], "-$this->name-" . PHP_SAPI);
        return parent::createDailyDriver($config);
    }

}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ReplaceStrCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'my:replace-str';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and replace some strings. Useful when publishing vendors files which contains some slow links.';



    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;



    protected $scanDir = [
        'resources',
        'public',
    ];

    protected $filesTypes = [
        '*.php',
        '*.css',
        '*.scss',
        '*.js',
        '*.html',
    ];

    protected $regxMap = [
        //'/((http|https):)?\/\/fonts\.googleapis\.com/i' => '//fonts.googleapis.cnpmjs.org',
        //'/((http|https):)?\/\/fonts\.gmirror\.org/i' => '//fonts.googleapis.cnpmjs.org',
        '/((http|https):)?\/\/ajax\.googleapis\.com\/ajax\/libs/i' => '//cdn.bootcss.com',
    ];

    protected $normalMap = [
        //
    ];

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $basePath = $this->laravel->basePath();
        $this->info("Curr Dir: " . $basePath);

        $this->findAndReplace(false);
        $this->findAndReplace(true);
    }


    protected function findAndReplace($useRegx)
    {
        $search = array_keys($useRegx ? $this->regxMap : $this->normalMap);
        $replace = array_values($useRegx ? $this->regxMap : $this->normalMap);

        if (count($search) == 0)
        {
            return;
        }

        $msg = $useRegx ? " Regx Search And Replace " : " Normal  Search And Replace ";
        $this->info("=========" . $msg . "=========");

        //find
        $files = Finder::create()->in($this->scanDir)->files();

        foreach ($search as $key)
        {
            $files->contains($key);
        }

        foreach ($this->filesTypes as $filesType)
        {
            $files->name($filesType);
        }

        foreach ($files as $file)
        {
            $this->info("Found " . $file);
            $this->replaceIn($file, $search, $replace, $useRegx);
        }

        $this->info("========= Done =========");
    }


    /**
     * Replace the given string in the given file.
     *
     * @param  string  $path
     * @param  string|array  $search
     * @param  string|array  $replace
     * @param  bool $useRegx
     * @return void
     */
    protected function replaceIn($path, $search, $replace, $useRegx)
    {
        if ($useRegx)
        {
            $this->files->put($path, preg_replace($search, $replace, $this->files->get($path)));
        }else
        {
            $this->files->put($path, str_replace($search, $replace, $this->files->get($path)));
        }
    }
}

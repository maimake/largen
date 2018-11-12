<?php

namespace Maimake\Largen\Console\Commands;

class AddTemplateExtCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:extension
    {dir : Template directory}
    {--ext=* : Which types of file should be append template extension}
    {--new-ext=twig}
    ';

    protected $append_extra_options = false;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Append template extension with template files';


    protected function askMoreInfo()
    {
        $dir = $this->askIfBlank('dir', true, function () {
            return $this->ask("Where is template directory");
        });
        assert(file_exists($dir), 'No such file or directory ' . $dir);

        $extensions = $this->option('ext');
        if (empty($extensions))
        {
            $extensions = ['css','sass', 'scss','js','php', 'json', 'xml', 'less'];
            $this->setOption('ext', $extensions);
        }
    }

    protected function generateFiles()
    {
        $new_ext = $this->option("new-ext");
        $dir = $this->argument('dir');

        foreach ($this->files->allFiles($dir, true) as $file)
        {
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            if ($ext == "tt")
            {
                $this->info("rename $file");
                $this->files->move($file, replace_path_ext($file, $new_ext));
            }else{
                if (in_array($ext, $this->argument('extensions')))
                {
                    $this->info("rename $file");
                    $this->files->move($file, "$file.$new_ext");
                }
            }
        }
    }
}

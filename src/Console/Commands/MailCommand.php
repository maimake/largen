<?php

namespace Maimake\Largen\Console\Commands;

class MailCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:mail 
    {name : Mail Name}
    {--type= : The type of mail[view|markdown]}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a mail';


    protected function askMoreInfo()
    {
        $this->askIfBlank('type', true, function () {
            return $this->choice("The type of mail", ['view', 'markdown'], 1);
        });
    }

    protected function generateFiles()
    {
        $view = snake_case($this->argument('name')) . "_mail";
        $view_path = $this->nsPath->getPath('view') . "mails/$view.blade.php";
        $view_name = $this->nsPath->getViewName("mails.$view");


        $info = $this->nsPath->getClassInfoByType('mail', $this->argument('name'));
        $info['view_name'] = $view_name;
        $this->template('Mail.php', $info['output'], $info);

        if ($this->option('type') == 'markdown')
        {
            $this->template('markdown.blade.php', $view_path);
        } else {
            $this->template('view.blade.php', $view_path);
        }
    }
}

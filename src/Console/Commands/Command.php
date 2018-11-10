<?php
/**
 * Created by PhpStorm.
 * User: mai
 * Date: 2017/12/7
 * Time: 14:41
 */

namespace Maimake\Largen\Console\Commands;
use Illuminate\Console\Command as BaseCommand;

abstract class Command extends BaseCommand
{
    protected function printWhenVerbose($msg) {
        if ($this->output->isVerbose())
        {
            $this->comment($msg);
        }
    }

    protected function setArgument($key, $value)
    {
        $this->input->setArgument($key, $value);
    }
    
    // manipulate option value
    protected function setOption($key, $value)
    {
        $this->input->setOption($key, $value);
    }

    public function choice($question, array $choices, $default = null, $attempts = null, $multiple = null)
    {
        $res = parent::choice($question, $choices, $default, $attempts, $multiple);

        if ($this->input->isInteractive())
        {
            return $res;
        }

        return $choices[$res];
    }

    protected function askIfBlank(string $key, bool $required=false, callable $callback, callable $transform = null, $askAlways=false)
    {
        if (!$this->hasArgument($key) && !$this->hasOption($key))
        {
            $this->abort("No argument or option named: $key");
        }
        $is_arg = $this->hasArgument($key);

        $val = $is_arg ? $this->argument($key) : $this->option($key);

        if ($askAlways || blank($val))
        {
            do{
                $val = $callback();
                if (blank($val))
                {
                    if($required && ($this->option('no-interaction') || $this->option('quiet')))
                    {
                        $this->abort("$key is required \n");
                    }
                }else{
                    break;
                }
            }while(true);
        }

        if ($transform)
        {
            $val = $transform($val);
        }
        $is_arg ? $this->setArgument($key, $val) : $this->setOption($key, $val);
        return $val;
    }



    // extend ask

    protected function expand($question,
                              $choices=[
                                  'yes' => 'overwrite',
                                  'no' => 'do not overwrite',
                                  'all' => 'overwrite this and all others',
                                  'quit' => 'abort'],
                              $default='no')
    {
        $choices['help'] = 'show this help';
        $hint = implode('|', array_keys($choices));
        do{
            if ($this->input->isInteractive())
            {
                $res = $choices[$default];
            }else{
                $res = $this->anticipate($question . " [$hint]", array_keys($choices), $default);
            }
            if ($res == 'help')
            {
                $helps = array_map(function ($k, $v) {
                    return "\t $k, $v";
                }, array_keys($choices), $choices);
                $this->line(implode("\n", $helps));
            }else{
                break;
            }
        } while (true);

        return $res;
    }


    const DB_FIELD_TYPES = [
        'index' => [
            "index"
        ],
        'unique' => [
            "unique"
        ],
        'foreign' => [
            "foreign"
        ],
        'morphs' => [
            "morphs",
            "nullableMorphs"
        ],
        'increments' => [
            "increments",
            "smallIncrements",
            "mediumIncrements",
            "bigIncrements"
        ],
        'string' => [
            "string",
            "char",
            "text",
            "mediumText",
            "uuid"
        ],
        'integer' => [
            "integer",
            "tinyInteger",
            "smallInteger",
            "mediumInteger",
            "bigInteger",
            "unsignedInteger",
            "unsignedTinyInteger",
            "unsignedSmallInteger",
            "unsignedMediumInteger",
            "unsignedBigInteger"
        ],
        'decimal' => [
            "decimal",
            "float",
            "double"
        ],
        'boolean' => [
            "boolean"
        ],
        'enum' => [
            "enum"
        ],
        'json' => [
            "json",
            "jsonb"
        ],
        'timestamp' => [
            "date",
            "dateTime",
            "dateTimeTz",
            "time",
            "timeTz",
            "timestamp",
            "timestampTz"
        ],
        'binary' => [
            "binary"
        ],
        'net' => [
            "ipAddress",
            "macAddress"
        ]
    ];

    protected function askTableField()
    {
        $rtn = [];
        while (true)
        {
            $field_name = $this->ask('Field Name (Press Enter to finish)', false);
            if (!$field_name)
            {
                break;
            }else{
                $field_name = snake_case($field_name);
                $field_type = $this->choice('Field Type', array_keys(self::DB_FIELD_TYPES), 5);
                $field_sub_types = self::DB_FIELD_TYPES[$field_type];
                if (count($field_sub_types) <= 1)
                {
                    $exact_type = $field_sub_types[0];
                }else{
                    $exact_type = $this->choice('Exact Type', $field_sub_types, 0);
                }
                if ($exact_type == 'foreign')
                {
                    if (!ends_with($field_name, '_id'))
                    {
                        $this->error("Foreign key($field_name) should end with '_id'. Please reEnter it again");
                        continue;
                    }
                    $exact_type = "integer:$exact_type";
                }elseif ($exact_type == 'enum')
                {
                    $items = $this->ask('Please enter the items(Separated by comma)');
                    $items = array_map(function ($item) {
                        return "'" . trim($item) . "'";
                    }, explode(',', $items));
                    $items = implode(',', $items);
                    $exact_type .= "([$items])";
                }
                $rtn[] = "$field_name:$exact_type";
            }
        }
        return empty($rtn) ? false : implode(',', $rtn);
    }


    // shell

    protected function systemOrFail($cmd)
    {
        $this->printWhenVerbose($cmd);
        system($cmd, $rtn_code);
        $rtn_code && $this->abort("Shell Error: $cmd \n");
    }
    protected function systemSudoOrFail($cmd, $quote_cmd=true)
    {
        if ($quote_cmd)
        {
            $cmd = "sudo bash -c \"$cmd\"";
        }else{
            $cmd = "sudo $cmd";
        }
        $this->printWhenVerbose($cmd);
        system($cmd, $rtn_code);
        $rtn_code && $this->abort("Shell Error: $cmd \n");
    }



    protected function abort($msg)
    {
        throw new \Exception($msg);
    }

    protected function dump_autoload()
    {
        $this->systemOrFail("composer dump-autoload");
    }


    // =======================================
    // File
    // =======================================

    protected function prependToFile($filename, $content, $force = false)
    {
        if (!$force)
        {
            if(str_contains(file_get_contents($filename), $content))
            {
                return;
            }
        }

        file_put_contents($filename, $content . file_get_contents($filename));

        $this->info("Prepend $filename");
    }

    protected function appendToFile($filename, $content, $force = false)
    {
        if (!$force)
        {
            if(str_contains(file_get_contents($filename), $content))
            {
                return;
            }
        }
        file_put_contents($filename, $content, FILE_APPEND);
        $this->info("Append $filename");
    }

    protected function insertToFile($filename, $content, $search, $search_as_regex=false, $pos="after", $next_line=true, $force = false,  $brackets=null)
    {
        $cmd_file = largen_path('insertfile.rb');
        $search = str_replace("'", "'\''", $search);
        $content = str_replace("'", "'\''", $content);
        $cmd = "ruby '$cmd_file' $filename --insertion='$content' --search='$search' --pos=$pos";

        if ($brackets) $cmd .= " --brackets=$brackets ";
        if ($next_line) $cmd .= " --next-line ";
        if ($force) $cmd .= " --force ";
        if ($search_as_regex) $cmd .= " --regex ";


        if ($this->output->isVerbose())
        {
            $cmd .= " -v ";
        }

        $this->systemOrFail($cmd);
        $this->info("Insert $filename");
    }

    protected function insertInArrayToFile($filename, $content, $search, $search_as_regex=false, $pos="after", $next_line=true, $force = false, $brackets="[]")
    {
        $this->insertToFile($filename, $content, $search, $search_as_regex, $pos, $next_line, $force, $brackets);
    }

    protected function insertInBlockToFile($filename, $content, $search, $search_as_regex=false, $pos="after", $next_line=true, $force = false, $brackets="{}")
    {
        $this->insertToFile($filename, $content, $search, $search_as_regex, $pos, $next_line, $force, $brackets);
    }


    protected function replaceInFile($filename, $replacement, $search, $search_as_regex=false)
    {
        $content = file_get_contents($filename);
        if ($search_as_regex)
        {
            $content = preg_replace($search, $replacement, $content, -1,  $count);
        }else{
            $content = str_replace($search, $replacement, $content, $count);
        }

        file_put_contents($filename, $content);
        $this->info("Replace $filename");

        return $count;
    }

    protected function changeJsonFile($filename, callable $callback)
    {
        $content = file_get_contents($filename);
        $json = \json_decode($content, true);
        $json = $callback($json);
        $content = \json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($filename, $content);
    }

}
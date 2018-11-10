<?php

namespace Maimake\Largen\Console\Commands;

class IdeaCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'largen:idea';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Config idea IDE directories map';


    protected function askMoreInfo()
    {
    }

    protected function generateFiles()
    {
        if (!$this->checkHasIdeaProject())
        {
            $this->abort("Should create idea project first !");
        }

        $this->templateDir('', base_path('.idea'), null, true);

        // paths
        $module_file = $this->getModuleFilePath();
        $this->addPathsMap($module_file);

        // interpreter_name PHP7.2
        $this->changeInterpreterName();

        $this->changeEs6();
    }

    private function checkHasIdeaProject()
    {
        return $this->files->exists(base_path('.idea'));
    }

    private function changeInterpreterName()
    {
        $ver = "PHP7.2";
        $this->replaceInFile(base_path('.idea/workspace.xml'),
            '<component name="PhpWorkspaceProjectConfiguration" backward_compatibility_performed="true" interpreter_name="'. $ver .'" />',
            '<component name="PhpWorkspaceProjectConfiguration" backward_compatibility_performed="true" />');
    }

    private function getModuleFilePath()
    {
        $xml = simplexml_load_file(base_path('.idea/modules.xml'));
        $module_file = (string)$xml->component->modules->module[0]['filepath'];
        $module_file = str_after($module_file, '$PROJECT_DIR$/');
        return base_path($module_file);
    }

    private function addPathsMap($module_file)
    {
        $xml = simplexml_load_file($module_file);
        $content = $xml->component->content;

        $this->addSourceFolder($content, 'app', false, 'App', true);
        $this->addSourceFolder($content, 'tests', true);

        $this->addExcludeFolder($content, 'public/css');
        $this->addExcludeFolder($content, 'public/fonts/vendor');
        $this->addExcludeFolder($content, 'public/js');
        $this->addExcludeFolder($content, 'public/modules');
        $this->addExcludeFolder($content, 'storage');
        $this->addExcludeFolder($content, 'bootstrap/cache');

        $xml->saveXML($module_file);
    }

    private function addSourceFolder(&$contentNode, $path, $isTestSource, $packagePrefix=null, $generated=null)
    {
        $url = 'file://$MODULE_DIR$/' . $path;
        if (empty($contentNode->xpath("sourceFolder[@url='$url']")))
        {
            $node = $contentNode->addChild('sourceFolder');
            $node['url'] = $url;
            $node['isTestSource'] = var_export($isTestSource, true);

            if($packagePrefix)    $node['packagePrefix'] = $packagePrefix;
            if($generated)        $node['generated'] = var_export($generated, true);
        }
    }

    private function addExcludeFolder(&$contentNode, $path)
    {
        $url = 'file://$MODULE_DIR$/' . $path;
        if (empty($contentNode->xpath("excludeFolder[@url='$url']")))
        {
            $node = $contentNode->addChild('excludeFolder');
            $node['url'] = 'file://$MODULE_DIR$/' . $path;
        }
    }

    private function changeEs6() {
        $this->insertToFile(base_path('.idea/misc.xml'), "
  <component name=\"JavaScriptSettings\">
    <option name=\"languageLevel\" value=\"ES6\" />
  </component>
", "<project version=");
    }

}

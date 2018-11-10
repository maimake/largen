<?php
/**
 * Created by PhpStorm.
 * User: mai
 * Date: 2017/12/11
 * Time: 10:18
 */

namespace Maimake\Largen\Contracts\NsPath;


interface NsPath
{
    /**
     * example:
     * <code>
     * app => app
     * module => Admin
     * package => maimake/largen
     * </code>
     */
    public function getId();

    /**
     * example:
     * <code>
     * app => app
     * module => admin
     * package => largen
     * </code>
     */
    public function getAlias();

    /**
     * example:
     * <code>
     * app => ''
     * module => admin::
     * package => largen::
     * </code>
     */
    public function getViewName($name='');

    /**
     * example:
     * <code>
     * app => App
     * module => Modules\Admin
     * package => Maimake\Largen
     * </code>
     */
    public function getRootNamespace($ns='');


    /**
     * example:
     * <code>
     * app => base_path()
     * module => base_path(modules/Admin)
     * package => base_path(vendor/maimake/largen)
     * </code>
     */
    public function getRootBasePath($path='');


    /**
     * example: getRootSrcPath()
     * <code>
     * app => app_path() or 'app'
     * module => base_path(modules/Admin) or ''
     * package => base_path(vendor/maimake/largen/src) or 'src'
     * </code>
     */
    public function getRootSrcPath($path='', $withBase = true);

    /**
     * example: getRootServiceProviderPath()
     * <code>
     * app => app_path('Providers/AppServiceProvider.php')
     * module => base_path(modules/Admin/AdminServiceProvider.php)
     * package => base_path(vendor/maimake/largen/src/LargenServiceProvider.php)
     * </code>
     */
    public function getRootServiceProviderPath();


    /**
     * example: getNamespace(command, sub)
     * <code>
     * app => App\Console\Command\Sub
     * module => Modules\Admin\Console\Command\Sub
     * package => Maimake\Largen\Console\Command\Sub
     * </code>
     */
    public function getNamespace($type, $subdir='');

    /**
     * example: getPath(command, sub)
     * <code>
     * app => app_path(Console/Command/Sub)
     * module => base_path(modules/Admin/Console/Command/Sub)
     * package => base_path(vendor/maimake/largen/src/Console/Command/Sub)
     * </code>
     */
    public function getPath($type, $subdir='');



    /**
     * Implements should add subdir automatically
     * example: getResourcePathByType(view, file.php)
     * <code>
     * app => base_path(resources/views/Sub/file.php)
     * module => base_path(modules/Admin/resources/views/Sub/file.php)
     * package => base_path(vendor/maimake/largen/resources/views/Sub/file.php)
     * </code>
     */
    public function getResourcePathByType($type, $path='');

    /**
     * Generate class's info for a type
     * example: getClassInfoByType(command, hello)
     * <code>
     * app => [
     *  App\Console\Command\Sub,
     *  HelloCommand,
     *  App\Console\Command\Sub\HelloCommand,
     *  app_path(Console/Command/Sub/HelloCommand.php)
     * ]
     *
     * module => [
     *  Modules\Admin\Console\Command\Sub,
     *  HelloCommand,
     *  Modules\Admin\Console\Command\Sub\HelloCommand,
     *  base_path(modules/Admin/Console/Command/Sub/HelloCommand.php)
     * ]
     *
     * package => [
     *  Maimake\Largen\Console\Command\Sub,
     *  HelloCommand,
     *  Maimake\Largen\Console\Command\Sub\HelloCommand,
     *  base_path(vendor/maimake/largen/src/Console/Command/Sub/HelloCommand.php)
     * ]
     * </code>
     *
     * @param        $type
     * @param        $name
     * @param null   $classname_suffix
     * @param null   $classname
     * @param string $sub_dir
     * @param string $file_ext
     *
     * @return mixed ['namespace', 'classname', 'fullClassname', 'output']
     */
    public function getClassInfoByType($type, $name, $classname_suffix = null, $classname = null, $sub_dir='', $file_ext = '.php');



    /**
     * Find out the full name of class in type dir
     * @param $type
     * @param $class_name string class's base name
     *
     * @return mixed full name of class
     */
    public function findClassesByName($type, $class_name);

    /**
     * Find out the path of class in type dir
     * @param $type
     * @param $class_name
     *
     * @return mixed
     */
    public function findPathsByClassName($type, $class_name);

    /**
     * example: getConfigFilePath()
     * <code>
     * app => config_path('app.php')
     * module => base_path(modules/Admin/config/admin.php)
     * package => base_path(vendor/maimake/largen/config/largen.php)
     * </code>
     */
    public function getConfigFilePath();

    /**
     * All paths with types
     * @return mixed
     */
    public function allPaths();

}
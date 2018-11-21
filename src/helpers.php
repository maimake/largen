<?php

//use Coduo\PHPHumanizer\StringHumanizer;
use function GuzzleHttp\Psr7\build_query;
use function GuzzleHttp\Psr7\parse_query;
use Illuminate\Encryption\Encrypter;
use Maimake\Largen\Support\Log\LogManager;
use Psr\Http\Message\UriInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
//use Maimake\Largen\Support\Database\Eloquent\Builder;




if (! function_exists('largen_path'))
{
    function largen_path($path = '') {
        $base = realpath(__DIR__ . '/../');
        return empty($path) ? $base : $base . DIRECTORY_SEPARATOR . $path;
    }
}

//if (! function_exists('humanize_case'))
//{
//    function humanize_case($val) {
//        return StringHumanizer::humanize($val);
//    }
//}
//
if (! function_exists('dot_case'))
{
    function dot_case($val) {
        return preg_replace("/[\\s]+/", ".", trim($val));
    }
}

if (! function_exists('namespace_case'))
{
    function namespace_case($val) {

        $components = preg_split("/[\/\\\\]+/", $val);

        $components = array_map(function ($item) {
            return studly_case($item);
        }, $components);

        return trim(implode('\\', $components), '\\');
    }
}

if (! function_exists('path_slash'))
{
    function path_slash($val, $finish_separator=true) {
        if (empty($val)) return '';
        $val = normalize_separator($val);
        return $finish_separator ? str_finish($val, DIRECTORY_SEPARATOR) : $val;
    }
}

if (! function_exists('normalize_separator'))
{
    function normalize_separator($val) {
        return preg_replace("/[\/\\\\]+/", DIRECTORY_SEPARATOR, trim($val));
    }
}

if (! function_exists('join_path'))
{
    function join_path($paths) {
        $rtn = '';
        foreach (array_flatten(func_get_args()) as $path)
        {
            if (!empty($path))
                $rtn = path_slash($rtn) . $path;
        }
        return normalize_separator($rtn);
    }
}


if (! function_exists('join_namespace'))
{
    function join_namespace($namespaces) {
        $path = join_path(func_get_args());
        return preg_replace(";/;", '\\', $path);
    }
}

if (! function_exists('get_class_dir'))
{
    function get_class_dir($class) {
        return dirname((new ReflectionClass($class))->getFileName());
    }
}

if (! function_exists('template'))
{
    function template($source, $data=[]) {
        $twig = app('largen.twig');
        return $twig->render($source, $data);
    }
}


if (! function_exists('mkdir_r'))
{
    function mkdir_r($paths) {
        $files = app('files');
        if (!is_array($paths))
        {
            $paths = [$paths];
        }
        foreach ($paths as $path)
        {
            if (ends_with($path, DIRECTORY_SEPARATOR))
            {
                if (! $files->isDirectory($path)) {
                    $files->makeDirectory($path, 0755, true, true);
                }
            }else{
                if (! $files->isDirectory(dirname($path))) {
                    $files->makeDirectory(dirname($path), 0755, true, true);
                }
            }
        }
    }
}

if (! function_exists('resolve_path'))
{
    /**
     * Parse path. Useful in config file. (Home path is not supported)
     * Supported:
     * 1. Absolute path starting with "/"
     * 2. Relative path from buit-in path. Which starts with "@xxx_path/" (xxx_path is the name of buit-in path, such as "storage_path")
     * 3. Other path means the relative path from second param
     *
     * @param string $path Absolute path or Built-in Path ; Or Relative Path with $base param
     * @param string $base use it when $path param is a Relative Path
     *
     * @return string
     * @throws Exception
     */
    function resolve_path(string $path, $base = '@base_path') {
        if(strpos($path,'/') === 0)
        {
            // Absolute path
            return $path;
        }
        if(strpos($path,'~') === 0)
        {
            // Home path is not supported
            throw new Exception('Can not parse path starting with "~" !');
        }
        else if (preg_match('#^@([^/]+)(/(.*))?#', $path, $matches))
        {
            // Relative path from buit-in path
            $fun = $matches[1];
            if (count($matches) == 4)
            {
                $path = $matches[3];
                return $fun($path);
            }else{
                return $fun();
            }
        }
        else
        {
            // Relative path from base
            return resolve_path($base) . DIRECTORY_SEPARATOR . $path;
        }
    }
}


//
//if (! function_exists('set_default_t')) {
//
//    /**
//     * Set default value to data if data is empty. And return transformed value if exists.
//     * <code>
//     * data_set_default($request['age'], 123)
//     * </code>
//     * return $request['age'] if it's not empty;
//     * set and return 123 if $request['age'] is empty
//     *
//     *
//     * @param mixed             $data       a value pointer
//     * @param callable|mixed    $default    default value
//     * @param callable|null     $transform  transform function
//     *
//     * @return mixed return data or transformed value if exists.
//     */
//    function set_default_t(&$data, $default, callable $ret_transform = null) {
//        if (empty($data)) {
//            $data = is_callable($default) ? $default() : $default;
//        }
//        return $ret_transform ? $ret_transform($data) : $data;
//    }
//}
//
//
if (! function_exists('modify_query')) {

    function modify_query(UriInterface $uri, array $querys = [])
    {
        $query = $uri->getQuery();
        $dic = parse_query($query);
        $dic = array_merge($dic, $querys);
        $dic = array_filter($dic, 'strlen');
        $query = build_query($dic);
        return $uri->withQuery($query);
    }
}


if (! function_exists('add_path_prefix'))
{
    function add_path_prefix($path, $prefix) {

        $rtn =  dirname($path) . DIRECTORY_SEPARATOR . $prefix . basename($path);
        if (ends_with($path, DIRECTORY_SEPARATOR))
        {
            return str_finish($rtn, DIRECTORY_SEPARATOR);
        }else{
            return $rtn;
        }
    }
}

if (! function_exists('add_path_suffix'))
{
    function add_path_suffix($path, $suffix) {
        if (ends_with($path, DIRECTORY_SEPARATOR))
        {
            return str_replace_last(DIRECTORY_SEPARATOR, $suffix . DIRECTORY_SEPARATOR, $path);
        }else{
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (empty($ext))
                return $path . $suffix;
            return str_replace_last(".$ext", "$suffix.$ext", $path);
        }
    }
}

if (! function_exists('replace_path_ext'))
{
    function replace_path_ext($path, $new_ext) {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return substr($path, 0, -strlen($ext)) . $new_ext;
    }
}


//if (! function_exists('get_table_columns'))
//{
//    function get_table_columns($model) {
//        return $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());
//    }
//}
//
//
//if (!function_exists('array_undot')) {
//    function array_undot($dotNotationArray)
//    {
//        $array = [];
//        foreach ($dotNotationArray as $key => $value) {
//            array_set($array, $key, $value);
//        }
//        return $array;
//    }
//}
//
//
//if (! function_exists('array_undot_recursive'))
//{
//    function array_undot_recursive($arr, $undot_str_value=false) {
//        if (!is_array($arr)) {
//            if ($undot_str_value && is_string($arr) && str_contains($arr, '.'))
//            {
//                $keys = explode('.', $arr);
//                $last = array_pop($keys);
//                return array_undot([implode(".", $keys) => $last]);
//            }else{
//                return $arr;
//            }
//        }
//
//        $rtn = [];
//        foreach ($arr as $k => $v)
//        {
//            if (str_contains($k, '.'))
//            {
//                $undot = array_undot([$k => array_undot_recursive($v, $undot_str_value)]);
//                $rtn = array_merge_recursive($rtn, $undot);
//            }elseif($undot_str_value && is_string($v)){
//                $undot = array_undot_recursive($v, $undot_str_value);
//                $rtn = array_merge_recursive($rtn, array_wrap($undot));
//            }else{
//                $undot = array_undot_recursive($v, $undot_str_value);
//                $rtn = array_merge_recursive($rtn, [$k => $undot]);
//            }
//        }
//        return $rtn;
//    }
//}
//
//
//if (! function_exists('array_minimal_keys'))
//{
//    function array_minimal_keys($arr, $node_key = '') {
//        $arr = array_undot_recursive($arr);
//        if (!is_array($arr)) return $arr;
//
//        $rtn = [];
//        foreach ($arr as $k => $v)
//        {
//            $v = array_minimal_keys($v);
//            if (is_int($k))
//            {
//                $rtn[] = $v;
//            }
//            elseif (is_array($v) && count($v) == 1 && !is_int(head(array_keys($v))))//只有唯一节点，并且是[key=>val]形式的，允许合并key路径
//            {
//                $only_key = head(array_keys($v));
//
//                $new_key = [];
//                if(!empty($node_key)) $new_key[] = $node_key;
//                $new_key[] = $k;
//                $new_key[] = $only_key;
//
//                $rtn[implode('.', $new_key)] = $v[$only_key];
//            }else{
//                $rtn[$k] = $v;
//            }
//        }
//        return $rtn;
//    }
//}
//
//
//if (! function_exists('array_map_recursive'))
//{
//    function array_map_recursive($callback, $array)
//    {
//        if (is_null($array)) return null;
//
//        $func = function ($item) use (&$func, &$callback) {
//            return is_array($item) ? array_map($func, $item) : call_user_func($callback, $item);
//        };
//
//        return array_map($func, $array);
//    }
//}
//
//
if (! function_exists('pretty_json'))
{
    function pretty_json($value) {
        return \json_encode(($value instanceof Collection) ? $value->toArray() : $value, JSON_PRETTY_PRINT);
    }
}

if (! function_exists('get_logger')) {

    function get_logger($name)
    {
        if (!app()->bound("log.$name")) {
            app()->singleton("log.$name", function ($app) use ($name) {
                return new LogManager($app, $name);
            });
        }
        return app("log.$name");
    }
}


if (! function_exists('token_generate'))
{
    function token_generate() {
        return md5(Encrypter::generateKey(config('app.cipher'))) . md5(Str::orderedUuid());
    }
}


if (! function_exists('load_api_by_versions'))
{
    function load_api_by_versions($api_dir, $fallback = true) {

        $api_dir = path_slash($api_dir);
        $files = collect(app('files')->glob($api_dir . "**/*.php"));
        $files = $files->mapToGroups(function ($item) use ($api_dir) {
            $g = str_after($item, $api_dir);
            $g = str_before($g, "/");
            return [$g => $item];
        });

        if ($fallback) {

            $vers = $files->keys()->sort('version_compare')->values();

            for ($i = 0; $i < $vers->count(); $i++) {
                $ver = $vers->get($i);
                $route = Route::prefix($ver)->namespace("Api\\" . studly_case($ver));
                for ($j = 0; $j <= $i; $j++) {
                    $phps = $files->get($vers->get($j));
                    foreach ($phps as $php) {
                        $route->group($php);
                    }
                }
            }

        } else {

            $files->each(function($item, $ver) {
                $route = Route::prefix($ver)->namespace("Api\\" . studly_case($ver));
                foreach ($item as $php) {
                    $route->group($php);
                }
            });
        }
    }
}


if (! function_exists('num_code_random'))
{
    function num_code_random($len) {
        return str_pad(mt_rand(1, pow(10, $len) - 1), $len, '0');
    }
}


if (! function_exists('internal_request'))
{
    function internal_request($uri, $method = 'GET', $parameters = [], $headers = [])
    {
        $original_request = request();
        $request = Request::create($uri, $method, $parameters, $original_request->cookies->all());
        if ($headers != false) {
            $request->headers->add(request()->headers->all());
            $request->headers->add($headers);
        }

        return app()->handle($request);
    }
}

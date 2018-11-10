<?php

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

if (! function_exists('random_IP'))
{
    /**
     * Get a random Chinese IP address
     * @return string
     */
    function random_IP() {
        $ip_long = [
            ['607649792', '608174079'], //36.56.0.0-36.63.255.255
            ['1038614528', '1039007743'], //61.232.0.0-61.237.255.255
            ['1783627776', '1784676351'], //106.80.0.0-106.95.255.255
            ['2035023872', '2035154943'], //121.76.0.0-121.77.255.255
            ['2078801920', '2079064063'], //123.232.0.0-123.235.255.255
        ];
        $rand_key = mt_rand(0, 4);
        $ip       = long2ip(mt_rand($ip_long[$rand_key][0], $ip_long[$rand_key][1]));
        return $ip;
    }
}


if (! function_exists('sort_link'))
{
    /**
     * Create &lt;a&gt; link with a sort field and add class of "desc/asc"
     * @param  string      $key   the sort will be add to the current url. With prefix "-" means "DESC"
     * @param  string      $title link title
     * @return string          link
     */
    function sort_link(string $key, string $title) {

        $sort = request('sort');

        // get key name
        $trimKey = ltrim($key, '-');
        $regx = "/(?<!(\w|-))-?$trimKey/";

        if(preg_match($regx, $sort, $matchs))
        {
            // if has the same name already, then toggle the sort direction
            $curr = $matchs[0];
            $key = starts_with($curr, '-') ? substr($curr, 1) : '-' . $curr;
            $css = starts_with($curr, '-') ? 'desc' : 'asc';
            $sort = preg_replace($regx, $key, $sort);
        }else{
            // if has not the same name, then use it directly
            $sort = $key;
        }

        // change query param
        $url = request()->fullUrlWithQuery(['sort' => $sort]);

        return link_to($url, $title, [
            'class' => $css ?? null
        ]);
    }
}


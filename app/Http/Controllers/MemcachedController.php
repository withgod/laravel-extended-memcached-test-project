<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MemcachedController extends Controller
{
    public function index(Request $request)
    {
        $newrelic_enabled = 0;
        if (function_exists('newrelic_set_appname')) {
            newrelic_set_appname("php-memc-bench");
            $newrelic_enabled = 1;
        }

        $min = $request->input('min', 10);
        $max = $request->input('min', 50);;
        $try = mt_rand($min, $max);

        echo "newrelic is " . ($newrelic_enabled ? "enabled" : "disabled") . "\n";
        printf("try[%d]min[%d]max[%d]\n", $try, $min, $max);

        echo "loop start.\n";
        echo "time:" . microtime(true) . "\n";
        $keys = ['memc-bench-0000000000000'];
        $expire = time() + 300;
        Cache::put($keys[0], str_repeat('0', 32), $expire);
        $last_time = microtime(true);
        $diffs = [];
        $lines = [];
        for ($i = 0; $try > $i; $i++) {
            $r = mt_rand(1, 100);
            $key = uniqid("memc-bench-");
            $operation = NULL;
            $value = NULL;
            $ret = NULL;
            if ($r % 3 == 0) {
                $operation = 'set';
                $value = md5(uniqid());
                $keys[] = $key;
                $ret = Cache::put($key, $value, $expire);
            } else {
                if ($r % 5 == 0) {
                    $operation = 'get-miss';
                    $ret = Cache::get($key);
                } else {
                    $operation = 'get-hit';
                    $key = $keys[array_rand($keys)];
                    $ret = Cache::get($key);
                    $value = $ret;
                }
            }

            $current_time = microtime(true);
            $diff = $current_time - $last_time;
            $diffs[] = $diff;
            $last_time = $current_time;
            $lines[] = sprintf("diff[%f]ope[% 10s]key[%s]val[%s]ret[%s]", $diff, $operation, $key, $value, $ret);
        }
        printf("diff avg: %f\n", array_sum($diffs) / count($diffs));
        echo join("\n", $lines);
        echo "\ntime:" . microtime(true) . "\n";
        echo "loop end.\n";

        return response('', 200)->header('Content-Type', 'text/plain');
    }
}

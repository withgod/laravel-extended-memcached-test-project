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
        $expire = now()->addMinutes(3);
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

        echo "add method\n";
        $k = uniqid("memc-bench-add-");
        $v = str_repeat('x', 32);
        $ret = Cache::add($k, $v, $expire);
        echo var_export($ret, true) . "\n";
        echo "add method failure\n";
        $ret = Cache::add($k, $v, $expire);
        echo var_export($ret, true) . "\n";
        echo "putMany method\n";
        $putmany = [];
        $manykeys = [];
        $k = preg_replace("/add/", "putmany", $k);
        for ($i = 0; 10 > $i; $i++) {
            $key = $k . '-' . $i;
            $manykeys[] = $key;
            $putmany[$key] = $v;
        }
        $ret = Cache::putMany($putmany, $expire);
        echo var_export($ret, true) . "\n";
        echo "many method\n";
        $ret = Cache::many($manykeys);
        echo var_export($ret, true) . "\n";
        echo "increment method\n";
        $k = preg_replace("/putmany/", "increment", $k);
        $ret = Cache::put($k, 1, $expire);
        echo var_export($ret, true) . "\n";
        $ret = Cache::increment($k);
        echo var_export($ret, true) . "\n";
        $ret = Cache::increment($k);
        echo var_export($ret, true) . "\n";
        $ret = Cache::increment($k , 2);
        echo var_export($ret, true) . "\n";
        $ret = Cache::get($k);
        echo var_export($ret, true) . "\n";
        echo "decrement method\n";
        $k = preg_replace("/increment/", "decrement", $k);
        $ret = Cache::put($k, 100, $expire);
        echo var_export($ret, true) . "\n";
        $ret = Cache::decrement($k);
        echo var_export($ret, true) . "\n";
        $ret = Cache::decrement($k);
        echo var_export($ret, true) . "\n";
        $ret = Cache::decrement($k, 2);
        echo var_export($ret, true) . "\n";
        $ret = Cache::get($k);
        echo var_export($ret, true) . "\n";

        echo "*******";
        return response('', 200)->header('Content-Type', 'text/plain');
    }
}

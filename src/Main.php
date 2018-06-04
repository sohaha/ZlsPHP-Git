<?php

namespace Zls\Git;

/**
 * Zls
 * @author        影浅
 * @email         seekwe@gmail.com
 * @copyright     Copyright (c) 2015 - 2017, 影浅, Inc.
 * @link          ---
 * @since         v0.0.1
 * @updatetime    2017-07-22 14:00
 */
use Z;

class Main
{
    private static $CONFIG = [];

    public function __construct()
    {
        self::$CONFIG = [
            'path'   => ZLS_APP_PATH . '../',
            'max'    => 10,
            'branch' => 'origin/master',
            'ips'    => [
                //'127.0.0.1',
                //'0.0.0.0/16',
            ],
        ];
    }

    /**
     * @return array
     */
    public static function getConfig()
    {
        return self::$CONFIG;
    }


    public function setConfig(array $config)
    {
        self::$CONFIG = array_merge(self::$CONFIG, $config);
    }

    /**
     * @param null $clientIp
     */
    public function run($clientIp = null)
    {
        set_time_limit(0);
        if (!$clientIp) {
            $clientIp = z::clientIp(null, ['REMOTE_ADDR']);
        }
        $auth = false;
        $ips = z::arrayGet(self::$CONFIG, 'ips');
        foreach ($ips as $key => $ip) {
            if ($this->ipInNetworknetwork($clientIp, $ip)) {
                $auth = true;
                break;
            }
        }
        if (!$auth) {
            z::finish('hi:' . $clientIp);
        } else {
            $this->view();
        }
    }

    public function ipInNetworknetwork($originIp, $network)
    {
        $s = explode('/', $network);
        if (!z::arrayKeyExists(1, $s)) {
            return $originIp === $network;
        }
        $ip = (double)(sprintf("%u", ip2long($originIp)));
        $network_start = (double)(sprintf("%u", ip2long($s[0])));
        $network_len = pow(2, 32 - $s[1]);
        $network_end = $network_start + $network_len - 1;
        if ($ip >= $network_start && $ip <= $network_end) {
            return true;
        }

        return false;
    }

    private function view()
    {
        echo <<<EC
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Zls Git</title>
    <link rel="stylesheet" href="//cdn.bootcss.com/bootstrap/3.2.0/css/bootstrap.min.css">
</head>
<body>
<div class="container">
EC;
        $commitId = z::post('commitId');
        if ($commitId) {
            $this->commit($commitId);
        } else {
            $this->lists();
        }
        echo <<<EC
</div>
</body>
</html>
EC;
    }

    private function commit($commitId)
    {
        $dir = z::realPath(z::arrayGet(self::$CONFIG, 'path'));
        $cmd = "cd {$dir} && git checkout -f && git reset --hard " . escapeshellcmd($commitId);
        $res = Z::command($cmd, null, true, false);
        $cmd = \str_replace("cd {$dir} &&", '', $cmd);
        echo <<<EC
<div class="page-header jumbotrons"><h2>部署结果</h2><a href="?"><button type="button" class="btn btn-primary btn-xs">返回列表</button></a></div>
<div class="panel panel-default">
    <div class="panel-body">
       {$cmd}<br><br>{$res}
    </div>
</div>
EC;
    }

    private function lists()
    {
        $dir = z::realPath(z::arrayGet(self::$CONFIG, 'path'));
        $branch = z::arrayGet(self::$CONFIG, 'branch', '');
        $max = z::arrayGet(self::$CONFIG, 'max');
        $cmd = "cd {$dir} && git fetch && git log -{$max} {$branch} --pretty=oneline ";
        $res = Z::command($cmd, null, true, false);
        $list = explode("\n", $res);
        echo <<<EC
<div class="page-header jumbotrons"><h2>历史版本</h2><h6><strong>MaxLog:</strong>{$max}&emsp;&emsp;<strong>branch:</strong>{$branch}</h6></div>
        <table class="table table-hover table-bordered">
            <thead>
            <tr><th>提交说明</th><th id="commit-id">版本号</th><th>操作</th></tr>
            </thead>
            <tbody>
EC;
        $me = trim(Z::command("cd {$dir} && git rev-parse HEAD", null, true, false));
        foreach ($list as $value) {
            if (!!$value) {
                $_item = explode(' ', $value);
                echo '<tr><td>' . implode(' ', array_slice($_item, 1)) . '</td><td>' . $_item[0] . '</td><td>';
                if (trim($_item[0]) != $me) {
                    echo '<form method="post"><input type="hidden" name="commitId" value="' . $_item[0] . '"><button type="submit" class="btn btn-primary btn-xs">切换版本</button></form>';
                } else {
                    echo '<button type="button" class="btn btn-danger btn-xs">当前版本</button>';
                }
                echo '</td></tr>';
            }
        }
        echo <<<EC
      </tbody>
        </table>
EC;
    }
}

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <title>Debugger</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="//apps.bdimg.com/libs/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet"/>
    <style type="text/css">
        table th, table td {
            font-size: 12px;
        }
        body {margin: 0}
        #banner {background: #3367d6; margin-bottom: 20px}
        #banner h1 {margin: 0; padding: 10px; color: #fff}
    </style>
    <script src="//apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
    <script src="//apps.bdimg.com/libs/bootstrap/3.3.4/js/bootstrap.min.js"></script>

</head>
<body>
<div id="banner">
    <h1>Debug Info</h1>
</div>

<div class="container-fluid">

    <ul class="nav nav-tabs" id="tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#home" aria-controls="home" role="tab" data-toggle="tab">基本信息</a>
        </li>
        <li role="presentation">
            <a href="#get" aria-controls="get" role="tab" data-toggle="tab">$_GET</a>
        </li>
        <li role="presentation">
            <a href="#post" aria-controls="post" role="tab" data-toggle="tab">$_POST</a>
        </li>
        <li role="presentation">
            <a href="#cookies" aria-controls="cookies" role="tab" data-toggle="tab">$_COOKIE</a>
        </li>
        <li role="presentation">
            <a href="#files" aria-controls="files" role="tab" data-toggle="tab">$_FILES</a>
        </li>
        <li role="presentation">
            <a href="#server" aria-controls="server" role="tab" data-toggle="tab">$_SERVER</a>
        </li>

    </ul>

    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" id="home">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="150">路由地址</th>
                    <td><?= $route ?></td>
                </tr>
                <tr>
                    <th>请求URL</th>
                    <td><?= $request->getUri() ?></td>
                </tr>
                <tr>
                    <th>请求方法</th>
                    <td><?= $request->getMethod() ?></td>
                </tr>
                <tr>
                    <th>请求时间</th>
                    <td><?= date('Y-m-d H:i:s', $startTime) ?></td>
                </tr>
                <tr>
                    <th>执行时间</th>
                    <td><?= round($execTime, 4) . '秒' ?></td>
                </tr>
                <tr>
                    <th>内存使用</th>
                    <td><?= \Core\Lib\FileHelper::sizeFormat($memoryUsage) ?></td>
                </tr>
            </table>
        </div>

        <?php
        $superVars = ['$_GET' => 'get', '$_POST' => 'post', '$_COOKIE' => 'cookies', '$_FILES' => 'files', '$_SERVER' => 'server'];
        foreach ($superVars as $name => $var):
            ?>
            <div role="tabpanel" class="tab-pane" id="<?= $var ?>">
                <table class="table table-bordered table-striped">
                    <?php foreach (${$var} as $key => $value) : ?>
                        <tr>
                            <th width="150"><?= $key ?></th>
                            <td><?= \Core\Lib\VarDumper::dumpAsString($value) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <h4>响应Headers</h4>
    <table class="table table-bordered table-striped">
        <?php foreach ($responseHeaders as $key => $value) : ?>
            <tr>
                <th width="150"><?= $key ?></th>
                <td><?= implode(', ', $value) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h4>SQL</h4>
    <table class="table table-bordered table-striped">
        <tr>
            <th width="50">序号</th>
            <th width="100">耗时</th>
            <th>查询</th>
        </tr>
        <?php foreach ($sqlLogs as $k => $log) : ?>
            <tr>
                <td><?= $k + 1 ?></td>
                <td><?= round($log['time'], 4) . 's' ?></td>
                <td><?= $log['sql'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

</div>
</body>
</html>
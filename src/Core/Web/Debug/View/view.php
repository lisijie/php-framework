<div class="row">
    <div class="col-md-12">
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
            <li role="presentation">
                <a href="#response" aria-controls="response" role="tab" data-toggle="tab">Response header</a>
            </li>

        </ul>

        <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="home">
                <table class="table table-bordered table-striped">
                    <tr>
                        <th width="150">路由地址</th>
                        <td><?= $meta['route'] ?></td>
                    </tr>
                    <tr>
                        <th>请求URL</th>
                        <td><?= $meta['url'] ?></td>
                    </tr>
                    <tr>
                        <th>请求方法</th>
                        <td><?= $meta['method'] ?></td>
                    </tr>
                    <tr>
                        <th>请求时间</th>
                        <td><?= date('Y-m-d H:i:s', $meta['startTime']) ?></td>
                    </tr>
                    <tr>
                        <th>执行时间</th>
                        <td><?= $this->formatTime($meta['execTime']) ?></td>
                    </tr>
                    <tr>
                        <th>内存使用</th>
                        <td><?= $this->formatSize($meta['memoryUsage']) ?></td>
                    </tr>
                </table>
            </div>

            <?php
            $superVars = ['$_GET' => 'get', '$_POST' => 'post', '$_COOKIE' => 'cookies', '$_FILES' => 'files', '$_SERVER' => 'server'];
            foreach ($superVars as $name => $var):
                ?>
                <div role="tabpanel" class="tab-pane" id="<?= $var ?>">
                    <table class="table table-bordered table-striped">
                        <?php foreach ($meta[$var] as $key => $value) : ?>
                            <tr>
                                <th width="150"><?= $key ?></th>
                                <td><?= \Core\Lib\VarDumper::dumpAsString($value) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endforeach; ?>

            <div role="tabpanel" class="tab-pane" id="response">
                <table class="table table-bordered table-striped">
                    <?php foreach ($meta['responseHeaders'] as $key => $value) : ?>
                        <tr>
                            <th width="150"><?= $key ?></th>
                            <td><?= implode(', ', $value) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <h4>SQL</h4>
        <table class="table table-bordered table-striped tablesorter">
            <thead>
            <tr>
                <th width="80">序号</th>
                <th width="100">耗时</th>
                <th width="100">文件</th>
                <th>查询</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($sqlLogs as $k => $log) : ?>
                <tr>
                    <td><?= $k + 1 ?></td>
                    <td><?= $this->formatTime($log['time']) ?></td>
                    <td>
                        <?php
                        foreach (['controller', 'service', 'model'] as $item) {
                            if ($log[$item]) {
                                echo $log[$item] . '<br />';
                            }
                        }
                        ?>
                    </td>
                    <td style="word-break: break-all"><?= $log['sql'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h4>性能分析</h4>
        <?php if (empty($profile)) : ?>
            <p class="bg-warning">请先安装并开启 XHProf 扩展。</p>
        <?php else: ?>
            <table id="profile" class="table table-bordered table-hover tablesorter">
                <thead>
                <tr>
                    <th>函数</th>
                    <th width="100">调用次数</th>
                    <th width="100">执行时间</th>
                    <th width="100">CPU时间</th>
                    <th width="100">内存占用</th>
                    <th width="100">内存峰值</th>
                    <th width="100">总执行时间</th>
                    <th width="100">总CPU时间</th>
                    <th width="100">总内存占用</th>
                    <th width="100">总内存峰值</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($profile as $key => $value) : ?>
                    <tr>
                        <td class="text"><?= $key ?></td>
                        <td class="center"><?= $value['ct'] ?></td>
                        <td class="center"><?= $this->formatTime($value['ewt']) ?></td>
                        <td class="center"><?= $this->formatTime($value['ecpu']) ?></td>
                        <td class="center"><?= $this->formatSize($value['emu']) ?></td>
                        <td class="center"><?= $this->formatSize($value['epmu']) ?></td>
                        <td class="center"><?= $this->formatTime($value['wt']) ?></td>
                        <td class="center"><?= $this->formatTime($value['cpu']) ?></td>
                        <td class="center"><?= $this->formatSize($value['mu']) ?></td>
                        <td class="center"><?= $this->formatSize($value['pmu']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <table id="profile" class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>请求地址</th>
                <th width="100">请求方法</th>
                <th width="200">时间</th>
                <th width="100">执行时间</th>
                <th width="100">内存占用</th>
                <th width="100">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($list as $r) : ?>
                <tr>
                    <td class="text"><?= $r['url'] ?></td>
                    <td class="center"><?= $r['method'] ?></td>
                    <td class="center"><?= date('Y-m-d H:i:s', $r['time']) ?></td>
                    <td class="center"><?= $this->formatTime($r['exec_time']) ?></td>
                    <td class="center"><?= $this->formatSize($r['memory']) ?></td>
                    <td class="center"><a href="/debug/view?id=<?= $r['id'] ?>">查看</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?=$pager?>
    </div>
</div>
</div>
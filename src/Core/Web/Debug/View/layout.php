<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <title>性能分析工具</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="//apps.bdimg.com/libs/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet"/>
    <style type="text/css">
        * {
            outline: none;
        }

        .tablesorter .tablesorter-header {
            background-image: url(data:image/gif;base64,R0lGODlhFQAJAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAkAAAIXjI+AywnaYnhUMoqt3gZXPmVg94yJVQAAOw==);
            background-repeat: no-repeat;
            background-position: center right;
            white-space: normal;
        }

        .tablesorter .headerSortUp,
        .tablesorter .tablesorter-headerSortUp,
        .tablesorter .tablesorter-headerAsc {
            background-image: url(data:image/gif;base64,R0lGODlhFQAEAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAQAAAINjI8Bya2wnINUMopZAQA7);
        }

        .tablesorter .headerSortDown,
        .tablesorter .tablesorter-headerSortDown,
        .tablesorter .tablesorter-headerDesc {
            background-image: url(data:image/gif;base64,R0lGODlhFQAEAIAAACMtMP///yH5BAEAAAEALAAAAAAVAAQAAAINjB+gC+jP2ptn0WskLQA7);
        }
    </style>
</head>
<body>
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <a class="navbar-brand" href="<?=URL('debug/index')?>">性能分析工具</a>
        </div>
        <p class="navbar-text navbar-right">状态：
            <?php if ($status == 'on') :?>
                <span class="label label-success">开启中</span>
            <?php else: ?>
                <span class="label label-warning">已关闭</span>
            <?php endif; ?>
        </p>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li class="<?= CUR_ROUTE == 'debug/index' ? 'active' : '' ?>"><a href="<?=URL('debug/index')?>">最近运行</a></li>
                <li class="<?= CUR_ROUTE == 'debug/view' ? 'active' : '' ?>"><a href="<?=URL('debug/view')?>">查看详情</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                       aria-expanded="false">操作 <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <li><a href="<?=URL('debug/start')?>">全局开启</a></li>
                        <li><a href="<?=URL('debug/stop')?>">全局关闭</a></li>
                        <li><a href="<?=URL('debug/clear')?>">清空日志</a></li>
                    </ul>
                </li>
            </ul>
        </div>

    </div>
</nav>
<div class="container-fluid">
    <?= $this->content(); ?>
</div>
<script src="//apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
<script src="//apps.bdimg.com/libs/bootstrap/3.3.4/js/bootstrap.min.js"></script>
<script src="//cdn.bootcss.com/jquery.tablesorter/2.30.3/js/jquery.tablesorter.min.js"></script>
<script>
    $(function () {
        $(".tablesorter").tablesorter();
    });
</script>
</body>
</html>
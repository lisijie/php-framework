<?php
/**
 * 自定义路由配置
 */

return [
    ['/welcome', 'home/index'],
    // 组路由
    '/user' => [
        ['/list', 'user/list'],
        ['/:id', 'user/info'],
    ],
];
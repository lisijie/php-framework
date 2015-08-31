<?php
/**
 * 路由配置示例文件
 *
 * 
 */

return array(

    array('new', 'main/main/newest', array()),
    array('discuss', 'main/main/discuss', array()),
    array('jobs', 'main/main/jobs', array()),
    array('node/{name:string}', 'main/node/index', array('name' => '$1')),
    array('nodes', 'main/node/all', array()),
    array('topic/:id', 'main/topic/show', array('id' => '$1')),
    array('go/:id', 'main/topic/go', array('id' => '$1')),
    array('submit', 'main/submit/index', array()),

    //注册
    array('signup', 'user/account/register', array()),
    //登录
    array('signin', 'user/account/login', array()),
    //退出
    array('signout', 'user/account/logout', array()),

    //------------设置----------
    array('settings', 'user/profile/index', array()),
    array('settings/avatar', 'user/profile/avatar', array()),
    array('settings/password', 'user/profile/password', array()),

    //------------查看用户----------
    array('user', 'user/view/index', array()),
);
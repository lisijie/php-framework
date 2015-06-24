<?php


return array(

	//array('/topic/:id', 'main.topic.show', array('id'=>':id', 'asda'=>'2ax')),
	array('new', 'main/main/new', array()),
	array('main/index/:string', 'main/index', array('foo'=>'$1')),
	array('node/:string', 'main/node/index', array('name'=>'$1')),
	array('nodes', 'main/node/all', array()),
	array('topic/:id', 'main/topic/show', array('id'=>'$1')),
    array('go/:id', 'main/topic/go', array('id'=>'$1')),
    array('submit', 'main/submit/index', array()),
    //注册
	array('signup', 'user/account/register', array()),
    //登录
	array('signin', 'user/account/login', array()),

    //------------设置----------
	array('settings', 'user/profile/index', array()),
	array('settings/avatar', 'user/profile/avatar', array()),
	array('settings/password', 'user/profile/password', array()),
);
# 我的PHP框架 #

一个优雅、简洁、高效的PHP框架，用于快速开发各种类型的PHP项目，零学习成本。

### 特点

- 符合PSR编码规范，支持composer
- 简单的路由配置
- 清晰的代码结构，方便开发可维护的大型项目

### 服务器配置

##### Nginx

要启用Url重写，请在Nginx网站配置中增加以下配置：

	location / {
	    try_files $uri $uri/ /index.php?$args;
	}


### 演示项目

- [www.lampnotes.com](http://www.lampnotes.com "Web开发技术分享平台") (Web开发技术分享平台)

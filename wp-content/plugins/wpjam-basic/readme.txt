=== WPJAM Basic ===
Contributors: denishua
Donate link: https://wpjam.com/
Tags: WPJAM,性能优化
Requires at least: 5.2
Requires PHP: 7.2
Tested up to: 5.4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

<strong>最新 3.0 版本，要求 Linux 服务器，和 PHP 7.2 版本，以及服务器支持 Memcached。</strong> WPJAM Basic 是我爱水煮鱼博客多年来使用 WordPress 来整理的优化插件，WPJAM Basic 除了能够优化你的 WordPress，也是 WordPress 果酱团队进行 WordPress 二次开发的基础。

== Description ==

WPJAM Basic 是<a href="http://blog.wpjam.com/">我爱水煮鱼博客</a>多年来使用 WordPress 来整理的优化插件，WPJAM Basic 除了能够优化你的 WordPress ，也是 WordPress 果酱团队进行 WordPress 二次开发的基础。

WPJAM Basic 主要功能，就是去掉 WordPress 当中一些不常用的功能，比如文章修订等，还有就是提供一些经常使用的函数，比如获取文章中第一张图，获取文章摘要等。

如果你的主机安装了 Memcacached 等这类内存缓存组件和对应的 WordPress 插件，这个插件也针对提供一些针对一些常用的插件和函数提供了对象缓存的优化版本。

详细介绍和安装说明： <a href="http://blog.wpjam.com/project/wpjam-basic/">http://blog.wpjam.com/project/wpjam-basic/</a>。

除此之外，WPJAM Basic 还支持多达十七个扩展，你可以根据自己的需求选择开启：

| 扩展 | 简介 | 
| ------ | ------ |
| 文章数量 | 设置不同页面不同的文章列表数量，不同的分类不同文章列表数量。 |
| 文章目录 | 自动根据文章内容里的子标题提取出文章目录，并显示在内容前。 |
| 相关文章 | 根据文章的标签和分类，自动生成相关文章，并在文章末尾显示。 |
| 用户角色 | 用户角色管理，以及用户额外权限设置。 |
| 统计代码 | 自动添加百度统计和 Google 分析代码。 |
| 百度站长 | 支持主动，被动，自动以及批量方式提交链接到百度站长。 |
| 移动主题 | 给移动设备设置单独的主题，以及在PC环境下进行移动主题的配置。 |
| 301 跳转 | 支持网站上的 404 页面跳转到正确页面。 |
| 简单 SEO | 设置简单快捷，功能强大的 WordPress SEO 功能。 |
| SMTP 发信 | 简单配置就能让 WordPress 使用 SMTP 发送邮件。 |
| 常用短代码 | 添加 list table 等常用短代码，并在后台罗列所有系统所有短代码。|
| 文章浏览统计 | 统计文章阅读数，激活该扩展，请不要再激活 WP-Postviews 插件。|
| 文章快速复制 | 在后台文章列表，添加一个快速复制按钮，点击可快复制一篇草稿用于新建。 |
| 摘要快速编辑 | 在后台文章列表，点击快速编辑之后也支持编辑文章摘要。 |
| Rewrite 优化 | 清理无用的 Rewrite 代码，和添加自定义 rewrite 代码。 |
| 自定义页脚代码 | 在文章编辑页面可以单独设置每篇文章 Footer 代码。 |
| 文章类型转换器 | 文章类型转换器，可以将文章在多种文章类型中进行转换。 |

== Installation ==

1. 上传 `wpjam-basic`目录 到 `/wp-content/plugins/` 目录
2. 激活插件，开始设置使用。

== Changelog ==

= 4.2.2 =
* 每周日常更新，修正用户提到bug。
* wpjam-list-table 支持左右栏模式
* 新增核心代码处理 class WPJAM_Core
* 优化路径管理 Class WPJAM_Path
* 百度站长扩展支持快速收录

= 4.2 =
* 新增获取当前平台函数 wpjam_get_current_platform()。
* 新增验证文本文件管理 class WPJAM_VerifyTXT。
* 优化 WPJAM_Comment class。
* 新增 wpjam_page_action Action。
* 新增路径管理 Class WPJAM_Path
* 全面提升插件的安全性。

= 4.1 =
* 新增禁止古腾堡编辑器加载 Google 字体。
* 常用短代码新增B站视频支持 [bilibili]。
* 经典编辑器标签切换优化。

= 4.0 =
* 优化后台界面
* 新增 class-wpjam-path.php
* 新增 class-wpjam-users-list-table.php
* 新增 wpjam_is_json_request 函数
* 新增 wpjam_sha1 函数
* 新增 wpjam_builtin_page_load action
* 新增 wpjam_plugin_page_load action
* 新增路径处理函数
* 新增用于判断登录界面的 is_login 函数

= 3.9 =
* 支持 WordPress 国内镜像更新。
* 改进 object-cache.php，建议重新覆盖。
* 远程图片支持复制到本地再镜像到云存储选项。
* 新增「文章数量」扩展。
* 新增「摘要快速编辑」扩展。
* 新增「文章快速复制」扩展。
* 新增后台文章列表页搜索支持ID功能。
* 新增后台文章列表页作者筛选功能。
* 新增后台文章列表页排序选型功能。
* 新增后台文章列表页修改特色图片功能。
* 新增后台文章列表页修改浏览数功能。
* 优化 Model 缓存处理。
* 升级「简单SEO」扩展，支持列表页快速操作。
* 升级「百度站长」扩展，支持列表页批量提交。
* 新增支持 name[subname] 方式的字段
* wpjam-list-table 新增拖动排序

= 3.8 =
* 修复「去掉URL中category」不支持多级分类的问题
* 修复裁图组件获取宽度和高度兼容问题
* 添加屏蔽字符转码功能
* 添加屏蔽Feed功能
* 添加Google字体加速服务
* 添加Gravatar加速服务
* 添加移除后台界面右上角的选项
* 添加移除后台界面右上角的帮助
* 增强附件名添加时间戳功能
* 新增 str_replace_deep 函数
* 将文章页代码独立成独立扩展
* 「百度站长」扩展支持不加载推送 JS
* 「Rewrite」扩展支持查看所有规则
* 只给管理员显示讨论组
* 修改插件只支持 WordPress 5.2

= 3.7 =
* 插件 readme 添加 PHP 7.2 最低要求
* 新增 class-wpjam-message.php
* 修正 CDN http/https 切换的一个 bug
* WPJAM_LIST_TABLE 增强 overall 操作。
* 「用户角色」扩展添加重置功能
* 优化头像接口
* 修正自定义文章类型更新提示
* 修正自定义分类模式更新提示
* 修复图片编辑失效的问题
* 加强「屏蔽Trackbacks」功能
* 去掉「屏蔽主题Widget」功能
* 优化 Admin Notice 功能
* 新增 class-wpjam-terms-list-table.php
* 增强 wpjam_send_json

= 3.6 =
* 兼容 Gutenberg
* CDN 组件更好支持缩图
* CDN 组件更好的支持 HTTPS
* 全新的讨论组，非常顺滑
* 新增 class-wpjam-comment.php
* 「移动主题」扩展支持在后台启用移动主题。
* 更多 bug 修正，反正你也看不懂，就不细说了。

= 3.5 =
* 5.1 版本兼容处理
* 添加「301跳转」扩展
* 添加「移动主题」扩展
* 添加「百度站长」扩展，修正预览提交
* 讨论组移动到 WPJAM 菜单下
* 修正简单SEO的标题功能
* 修正相关文章中包含置顶文章的bug
* 将高级缩略图集成到缩略图设置
* 优化「去掉分类目录 URL 中的 category」功能

= 3.4 =
* 支持 UCLOUD 对象存储
* 支持屏蔽Gutenberg
* 修正部分站点不能更新 CDN 设置保存的问题。
* 修复文章内链接替换成 CDN 链接的bug。
* 修复图片中文名bug
* 添加高级缩略图扩展
* 添加相关文章扩展
* 更新核心接口

= 3.3 =
* 重构整个插件文件夹，更加合理
* 支持缓存 WordPress 菜单，真正实现首页0SQL。
* 把中文版后台 Settings 菜单名称改成设置。中文版好久不更新，只能自己手动来了
* 更新 WPJAM 后台 Javascript 库

= 3.2 =
* 提供选项让用户去掉URL中category
* 提供选项让用户上传图片加上时间戳
* 提供选项让用户可以简化后台用户界面
* 修复CDN组件的未设置文件扩展名会导致博客打不开的bug
* 增强WPJAM SEO扩展，支持sitemap拆分
* 增强讨论组功能，支持搜索和性能优化

= 3.1 =
* 修正 WPJAM Basic 3.0 以后大部分 bug
* 想到好方法，重新支持回 PHP7.2以下版本，但是PHP7.2以下版本不再新增功能和修正
* 修正主题自定义功能失效的bug
* 添加 object-cache.php 到 template 目录

= 3.0 =
* 基于 PHP 7.2 进行代码重构，效率更高，更加快速。
* 全AJAX操作后台

= 2.6 =
* 分拆功能组件
* WPJAM Basic 作为基础插件库使用

= 2.5 =
* 版本大更新

= 2.4 =
* 上架 WordPress 官方插件站
* 更新 wpjam-setting-api.php
* 新增屏蔽 WordPress REST API 功能
* 新增屏蔽文章 Embed 功能
* 由于腾讯已经取消“通过发送邮件的方式发表 Qzone 文章”功能，取消同步到QQ空间功能

= 2.3 = 
* 新增数据库优化
* 内置列表功能

= 2.2 = 
* 新增短代码
* 新增 SMTP 功能
* 新增插入统计代码功能

= 2.1 = 
* 新增最简洁效率最高的 SEO 功能

= 2.0 =
* 初始版本直接来个 2.0 显得牛逼点
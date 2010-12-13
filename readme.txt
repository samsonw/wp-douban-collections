=== Douban Collections ===
Contributors: samsonw
Tags: douban, collections, page
Requires at least: 2.5
Tested up to: 3.0
Stable tag: 0.8.0
License: GPLv3

Douban Collections provides a douban collections (books, movies, musics) page for WordPress.

== Description ==

*Douban Collections* provides a douban collections (books, movies, musics) page for WordPress, just put "[douban_collections]" in your Post or Page to show the douban collections page.

*Douban Collections* (豆瓣收藏) 是一个WordPress插件。用户可以把豆瓣中想读，在读，读过的书籍 (以后考虑支持电影和音乐) 作为WordPress的一个页面显示，支持自定义显示书籍数量和显示样式。 在WordPress的post或page里面加上 “[douban_collections]” 即可显示“豆瓣收藏”页面。

View [live demo](http://blog.samsonis.me/douban/)

[在线 demo](http://blog.samsonis.me/douban/)

**For latest update, please check github repository:**
**http://github.com/samsonw/wp-douban-collections**


== Installation ==

1. Upload and unpack the douban-collections plugin to wordpress plugin directory **"wp-content/plugins/douban-collections"**
2. Activate **Douban Collections** plugin on your *Plugins* page in *Site Admin*.
3. Create a "Douban" page (use "Page Full Width" template to get more width)
4. Put "[douban_collections]" in the content and save it.
5. Set your douban user id or username and tweak configuration and settings on **Douban Collections** page in *Site Admin* *Settigns* section
6. Check the newly created page, enjoy


<ol>
<li>解压 douban-collections 插件到 wordpress plugin 目录 **"wp-content/plugins/douban-collections"**</li>
<li>在后台插件管理界面激活 **Douban Collections** 插件</li>
<li>创建一个 “Douban” 的页面，如果主题支持的话，建议使用没有边栏（sidebar）的page template，以获得最宽的展示页面</li>
<li>在上面创建的页面里加上 “[douban_collections]” 并保存</li>
<li>在后台插件管理设置界面自定义 **Douban Collections** 插件，务必填上您的豆瓣用户id或username</li>
<li>访问刚创建的页面，have fun</li>
</ol>


== Frequently Asked Questions ==


== Screenshots ==
http://blog.samsonis.me/douban/


== Changelog ==

= 0.8.0 =

* added an option to allow user to custom the display stylesheets, these customizations won't be lost after plugin update.

= 0.7.0 =

* added options for users to control how many "reading", "read" and "wish" books to show up in the collections page

= 0.6.1 =

* stripslashes collection status text

= 0.6.0 =

* use api key while calling douban api
* added douban user info
* display totally 500 books (reading, read, wish) at maximum

= 0.5.0 =

* Initial import the working copy of my blog douban collections page: http://blog.samsonis.me/douban/


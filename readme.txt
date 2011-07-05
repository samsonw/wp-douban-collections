=== Douban Collections ===
Contributors: samsonw
Tags: douban, collections, page
Requires at least: 2.5
Tested up to: 3.2
Stable tag: 1.0.0
License: GPLv3

Douban Collections provides a douban collections (books, movies, musics) page for WordPress.

== Description ==

*Douban Collections* provides a douban collections (books, movies, musics) page for WordPress, just put "[douban_collections]" in your Post or Page to show the douban books collections page.  This tag support 2 parameters: "category" and "with_user_info".  For example, you can put "[douban_collections category="movie" with_user_info="true"]" to show the movies collections page with douban user info.

*Douban Collections* (豆瓣收藏) 是一个WordPress插件。用户可以把豆瓣中想读、在读、读过的书籍，想看、看过的电影作为WordPress的一个页面显示，支持自定义显示书籍数量和显示样式。 在WordPress的post或page里面加上 “[douban_collections]” 即可显示带用户信息的“豆瓣收藏”书籍页面。 同时支持category和with_user_info两个参数，比如“[douban_collections category="movie" with_user_info="true"]”可以显示带用户信息的电影页面。

View [live demo](http://blog.samsonis.me/douban/)

[在线 demo](http://blog.samsonis.me/douban/)

**For latest update, please check github repository:**
**http://github.com/samsonw/wp-douban-collections**


== Installation ==

1. Upload and unpack the douban-collections plugin to wordpress plugin directory **"wp-content/plugins/douban-collections"**
2. Activate **Douban Collections** plugin on your *Plugins* page in *Site Admin*.
3. Create a "Douban" page (use "Page Full Width" template to get more width)
4. Put "[douban_collections]" in the content and save it. (the default is [douban_collections category="book" with_user_info="true"], also support movie: [douban_collections category="movie"])
5. Set your douban user id or username and tweak configuration and settings on **Douban Collections** page in *Site Admin* *Settigns* section
6. Check the newly created page, enjoy


<ol>
<li>解压 douban-collections 插件到 wordpress plugin 目录 **"wp-content/plugins/douban-collections"**</li>
<li>在后台插件管理界面激活 **Douban Collections** 插件</li>
<li>创建一个 “Douban” 的页面，如果主题支持的话，建议使用没有边栏（sidebar）的page template，以获得最宽的展示页面</li>
<li>在上面创建的页面里加上 “[douban_collections]” 并保存，此默认等同于“[douban_collections category="book" with_user_info="true"]”，显示电影可使用“[douban_collections category="movie"]”</li>
<li>在后台插件管理设置界面自定义 **Douban Collections** 插件，务必填上您的豆瓣用户id或username</li>
<li>访问刚创建的页面，have fun</li>
</ol>


== Frequently Asked Questions ==


== Screenshots ==

http://blog.samsonis.me/douban/

http://blog.samsonis.me/movies/


== Changelog ==

= 1.0.0 =

* added a option to enable load plugin resources only in specific pages and posts, thus increase load speed of other pages and posts.
* tiny UI tweak in plugin admin setting page, make it compatible with the Refreshed Administative UI of wordpress 3.2.
* make the plugin compatible with wordpress 3.2.

= 0.9.3 =

* fixed an [issue](http://blog.samsonis.me/2010/12/wordpress-%E8%B1%86%E7%93%A3%E6%8F%92%E4%BB%B6-douban-collections-%E8%B1%86%E7%93%A3%E6%94%B6%E8%97%8F-0-9-0/#comment-1307) which happens when collections is empty

= 0.9.2 =

* minor bug fixing

= 0.9.1 =

* added movie "watching" status support

= 0.9.0 =

* added movie collections support
* added 2 parameters (category, with_user_info) for [douban_collections]

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


# 晚夜工作室 · 企业官网内容管理系统

**白色简约的企业官网 + 轻量博客后台，PHP 7.3 原生实现，零框架、零网络依赖**

![PHP](https://img.shields.io/badge/PHP-7.3%2B-777BB4?logo=php\&logoColor=white)

![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-4479A1?logo=mysql\&logoColor=white)

![License](https://img.shields.io/badge/License-All%20Rights%20Reserved-orange)

![No Dependencies](https://img.shields.io/badge/外部依赖-0-brightgreen)

---

## 📖 项目简介

这是一个简约企业官网（[www.iswanye.cn](http://www.iswanye.cn)），包含**展示型前台**与**内容管理后台**两部分。

它的设计取舍很明确：**数据库只存元信息，文章正文存本地文件**。

- 数据库里只有：文章编号、标题、作者、分类、摘要、发布 IP、状态、点击量、评论量
- 正文同时保存为 `data/posts/{id}.md`（Markdown 源）与 `{id}.html`（渲染结果，自动套用统一头尾模板）
- 前台展示时：先查数据库拿到编号，再由 PHP 读取对应的本地正文文件渲染

这样做的好处是数据库极轻、备份迁移简单、读取无需解析 Markdown，也不需要依赖任何缓存组件。

---

## ✨ 功能特性

### 前台

| 页面   | 地址               | 说明                                                    |
| ---- | ---------------- | ----------------------------------------------------- |
| 首页   | `/`              | 导航下滑自动隐藏、hover 滑动下划线、最新动态 4 篇、服务能力、数据条、关于我们、底部版权与 ICP |
| 企业动态 | `/news/`         | 分类筛选（动态 / 通知）+ 底部分页，条数后台可配                            |
| 文章详情 | `/article/?id=1` | 标题居中、Markdown 排版、代码高亮、已读人数（按设备去重）                     |
| 评论页  | `/comment/?id=1` | 独立页面，后台可一键关闭                                          |

- **丝滑的导航体验**：向下滚动时标题栏自动收起，向上滚动立即回显；所有 `a` 标签 hover 时有从左到右滑出的下划线
- **首页最新动态**：默认取最新 4 篇，标题超 2 行、摘要超 3 行自动省略号，排版不会被长标题撑坏
- **干净的详情页**：只有正文与「已读人数」，没有点赞、收藏、打赏等任何互动组件
- **已读去重**：以一年期 Cookie 设备标识 + 数据库唯一键双重判定，同一设备对同一篇文章只计一次，防刷
- **响应式**：桌面 / 平板 / 手机三端适配，移动端导航自动折叠
- **无刷新阅读文章**：首页卡片与动态页列表点击文章标题时，通过 AJAX 只替换 `<main>` 内容（`assets/js/pjax.js`），导航栏不动、地址栏同步、浏览器前进后退可用；渐进增强——不支持或请求失败时自动退回正常跳转，链接本身仍是真实 URL，可直接访问与被搜索引擎抓取

### 后台 `/admin/`

- **登录页**：白色简约双栏卡片 + GD 图形验证码，登录失败 5 次自动锁定 60 秒
- **概览**：文章总数、累计点击量、评论总数、近 7 天阅读趋势图、热门文章 TOP5、最新文章 / 评论
- **文章管理**：关键词搜索、分类与状态筛选、分页；每篇可编辑、删除、切换发布状态，并直接看到**点击量与评论量**
- **发布文章**：内置 Markdown 编辑器 —— 工具栏、实时分栏预览、图片上传（按钮 / 拖拽 / 粘贴三种方式）、`Ctrl + S` 保存
- **评论管理**：审核、隐藏、删除，支持按文章编号筛选
- **站点设置**：站点名称、口号、ICP 备案号、每页条数、评论开关 + 运行环境自检

---

## 🧩 技术栈

| 层次             | 选型                    | 说明                     |
| -------------- | --------------------- | ---------------------- |
| 后端             | PHP 7.3（兼容 7.0 ~ 8.x） | 原生实现，无框架、无 Composer 依赖 |
| 数据库            | MySQL 5.6+ / 8.0      | PDO 预处理，全参数化查询         |
| Markdown（服务端）  | Parsedown             | 安全模式渲染，自动转义内联 HTML     |
| Markdown（后台预览） | marked.js             | 本地文件，仅后台使用             |
| 代码高亮           | highlight.js          | 本地文件，仅文章详情页加载          |
| 验证码            | GD + 本地 Inter 字体      | 无字体时自动回退内置点阵字体，保证可用    |
| 前端             | 原生 CSS / JS           | 无 jQuery、无构建工具，打开即用    |

> **全部第三方组件已下载到 `assets/vendor/` 与 `admin/assets/vendor/`，运行期不会请求任何外部 CDN。**

---

## 📁 目录结构

```text
.
├── index.php                  首页
├── 404.php                    404 错误页（自包含，不依赖配置）
├── 403.php                    403 错误页（自包含，不依赖配置）
├── news/                      企业动态列表      /news/
├── article/                   文章详情          /article/?id=1（支持 ?ajax=1 输出片段）
├── comment/                   评论页            /comment/?id=1
├── admin/                     后台管理系统      /admin/
│   ├── index.php              登录页
│   ├── dashboard.php          概览
│   ├── posts.php              文章管理
│   ├── post_edit.php          Markdown 编辑器（发布 / 编辑）
│   ├── post_action.php        删除 / 状态切换（CSRF 校验）
│   ├── comments.php           评论管理
│   ├── settings.php           站点设置 + 环境自检
│   ├── captcha.php            验证码（GD）
│   └── upload.php             图片上传接口
├── includes/
│   ├── config.php             ★ 本机配置（含账号密码，已加入 .gitignore，不入库）
│   ├── config.example.php     ★ 配置模板，克隆后复制为 config.php 再填写
│   ├── db.php                 PDO 封装
│   ├── functions.php          ★ 公共函数 + 文章正文头尾模板
│   ├── article_view.php       文章正文渲染（整页与 AJAX 片段共用）
│   ├── Parsedown.php          Markdown 解析
│   ├── site_header.php        前台公共头
│   └── site_footer.php        前台公共尾
├── assets/                    前台样式 / 脚本 / 本地组件
│   └── js/pjax.js             站内文章无刷新加载
├── data/posts/                文章正文：{id}.md + {id}.html
├── uploads/                   文章图片
├── install/                   ★ 安装程序
└── docs/                      Nginx 伪静态规则等部署文档
```

---

## 🚀 快速开始

### 方式一：程序安装（60 秒）

访问/install按照步骤进行相应操作

### 方式二：本地体验（30 秒）

需要 PHP 7.0+（开启 `pdo_mysql`、`gd`、`mbstring`）与 MySQL：

```bash
# 1. 导入数据库
mysql -u root -p 数据库名称 < install/database.sql

# 2. 修改配置
#    编辑 includes/config.php，填写数据库信息与后台账号

# 3. 访问网站
#    后端：/admin
```

---

## 🔧 伪静态与错误页配置

### Nginx / 宝塔

粘贴位置：宝塔面板 → 网站 → 站点设置 → **伪静态**（保存时宝塔会自动执行 `nginx -t`）。  
完整文件内容见 `docs/nginx-rewrite.conf`。

> ⚠️ **不要把根目录 `.htaccess` 的内容贴到「伪静态」框里。**  
> `.htaccess` 是 Apache 专用文件，`DirectoryIndex`、`Options -Indexes`、`RewriteEngine` 都是 Apache 指令，  
> 贴进 Nginx 会直接报 `nginx: [emerg] unknown directive "DirectoryIndex"`，导致整个站点配置测试失败。  
> 该文件已被 Nginx 忽略（不会被读取），Apache 用户部署时仍然有用。

```nginx
# 1. 禁止目录列表（Nginx 默认即为 off，写上以防被全局配置覆盖）
autoindex off;

# 1.1 自定义错误页（部署在子目录时，请把路径改为 /子目录名/404.php）
error_page 404 /404.php;
error_page 403 /403.php;

# 2. 隐藏 URL 中的 index.php
#    /index.php -> /     /article/index.php?id=1 -> /article/?id=1（参数自动保留）
if ($request_uri ~* "index\.php") {
    rewrite ^/(.*?)index\.php$ /$1 permanent;
}

# 3. 目录访问自动补 index.php：/news/ -> /news/index.php
if (-d $request_filename) {
    rewrite ^/(.+)/$ /$1/index.php last;
}

# 4. 禁止通过 HTTP 直接读取敏感目录
location ^~ /includes/ { deny all; }
location ^~ /data/     { deny all; }
```

Nginx 不需要 `DirectoryIndex`，对应的能力来自「默认文档」：确认站点设置 → **网站目录 → 默认文档** 第一条是 `index.php`（宝塔默认即是），这样 `/article/?id=1`、`/news/` 这类目录式 URL 才能正确落到 `index.php`。

### Apache

根目录已自带 `.htaccess`，只要虚拟主机启用了 `AllowOverride`（宝塔 Apache 默认启用）就会自动生效，无需任何操作：

```apache
DirectoryIndex index.php index.html
Options -Indexes
ErrorDocument 404 /404.php
ErrorDocument 403 /403.php

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{THE_REQUEST} /index\.php [NC]
    RewriteRule ^(.*?)index\.php$ /$1 [R=301,L,NE]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^(.+)/$ $1/index.php [L]
</IfModule>
```

### 配置完成后自检

| 检查项                       | 期望结果                     |
| ------------------------- | ------------------------ |
| `/news/`                  | 200，正常显示动态列表             |
| `/article/?id=1`          | 200，正常显示文章详情             |
| `/article/index.php?id=1` | 301 跳转到 `/article/?id=1` |
| `/includes/config.php`    | 403，显示站内的 403 错误页        |
| `/not-exist.html`         | 404，显示站内的 404 错误页        |

`404.php` 与 `403.php` 是完全自包含的（不连数据库、不引 `includes/`），即使站点数据库异常也能正常渲染。

---

## ⚙️ 配置说明

所有需要改的地方都集中在 `includes/config.php`：

| 常量                                | 默认值                            | 说明                   |
| --------------------------------- | ------------------------------ | -------------------- |
| `SITE_NAME`                       | 晚夜计算机技术工作室                     | 站点名称                 |
| `SITE_SLOGAN`                     | 做好量变的准备 促进事物的质变                | 企业口号                 |
| `SITE_ICP`                        | 苏ICP备2026005148号-1             | 备案号（底部展示）            |
| `DB_HOST` / `DB_PORT`             | 127.0.0.1 / 3306               | 数据库地址                |
| `DB_NAME` / `DB_USER` / `DB_PASS` | iswanye / iswanye /（安装时填写） | 数据库凭据              |
| `ADMIN_USER` / `ADMIN_PASS`       | admin /（安装时填写）            | **后台账号密码（上线必改）**     |
| `ADMIN_PASS_HASH`                 | 注释状态                           | 可选：用 bcrypt 哈希代替明文密码 |

文章正文的**统一头尾模板**在两个函数中，改一次全局生效：

```php
// includes/functions.php
function article_tpl_head() { return '<div class="post-body markdown-body">' . "\n"; }
function article_tpl_foot() { return "\n" . '</div>' . "\n"; }
```

---

## 🗄 数据库表

| 表名               | 作用                                      |
| ---------------- | --------------------------------------- |
| `isw_posts`      | 文章主表：编号、标题、作者、分类、摘要、封面、IP、状态、点击量、评论量、时间 |
| `isw_post_views` | 阅读明细：`(post_id, visitor)` 唯一键，用于已读去重防刷  |
| `isw_comments`   | 评论：文章号、昵称、内容、IP、审核状态                    |
| `isw_settings`   | 站点配置（key / value）                       |
| `isw_logs`       | 后台操作日志（登录、发布、删除等）                       |

初始化文件 `install/database.sql` 含 5 张表 + 3 篇示例文章，导入即可看到完整效果。

---

## 🔐 安全设计

- **SQL 注入**：全部使用 PDO 预处理；文章编号强制 `^[1-9][0-9]{0,9}$` 正整数校验，同时杜绝目录穿越
- **XSS**：所有输出经 `htmlspecialchars` 转义；Markdown 渲染启用 Parsedown 安全模式，自动转义内联 HTML
- **CSRF**：后台所有写操作表单均带 Token 校验，接口异步上传同样校验
- **越权**：后台统一鉴权入口，未登录访问直接跳转登录页；接口类脚本不输出任何 HTML
- **爆破防护**：登录失败 5 次锁定 60 秒；验证码每次校验后立即失效
- **目录保护**：`data/` 与 `includes/` 已通过 `.htaccess` 禁止 HTTP 直接访问（Nginx 需参考部署指南添加规则）
- **上传安全**：仅接受 JPG / PNG / GIF / WEBP，用 `getimagesize` 校验真实图片类型，大小限制 3MB，文件名随机化

---

## ❓ 常见问题

<details>

<summary>后台验证码不显示？</summary>

PHP 未安装 gd 扩展。宝塔「软件商店」→ PHP 7.3 设置 → 安装扩展 → 安装 `gd` 后重启 PHP。

</details>

<details>

<summary>发布文章后前台看不到？</summary>

文章状态是「草稿」，在文章管理中点「发布」；或 `data/posts/` 目录不可写导致正文保存失败。

</details>

<details>

<summary>宝塔填完伪静态就报 unknown directive / 整站打不开？</summary>

把 `.htaccess`（Apache 专用）的内容填进了 Nginx 的伪静态框。  
请清空该框，改贴「伪静态与错误页配置」里的 Nginx 规则（或 `docs/nginx-rewrite.conf` 的内容）后保存。

</details>

<details>

<summary>页面上 CSS / JS 全部失效，但文件直接打开是好的？</summary>

页面以 `https` 打开，而站内资源被拼成了 `http://` 地址，被浏览器按「混合内容」拦截。  
把 `includes/config.php` 里的 `SITE_FORCE_HTTPS` 保持为 `true` 即可（本地 / 内网 IP 会自动走 http）。

</details>

---

## 📄 版权与许可

Copyright © 2026 晚夜. All rights reserved.

本项目为企业官网示例源代码，仅供学习交流参考；未经授权，可以用于商业用途，但不得二次倒卖。  
如需二次开发，请联系 <me@iswanye.cn>。

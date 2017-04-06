<?php
return array(
    //'配置项'=>'配置值'
    'URL_HTML_SUFFIX' => '',
    'TMPL_FILE_DEPR'        =>  '_',
    'TMPL_PARSE_STRING'  =>
    array
    (
        '__PUBLIC__' =>'/Public/Admin',// 更改默认的/Public 替换规则
        '__PUBLICHOME__' =>'/Public/Home',
        '__PUBLICH__'=>'/Public/Home',
        '__UPLOAD__' => '/Uploads'
    ),
    'URL_MODEL'=>'2',
    'TMPL_ACTION_SUCCESS'     =>  'Success:jump', // 默认错误跳转对应的模板文件
    'TMPL_ACTION_ERROR'     =>  'Error:jump', // 默认错误跳转对应的模板文件
    "DB_PREFIX"             =>  'we_',
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  'localhost', // 服务器地址
    'DB_NAME'               =>  'fixed',          // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  'toot',          // 密码
    'SESSION_TYPE'          =>  'Db',
    'SESSION_EXPIRE'        =>  '7200',    //session 过期时间
    'SESSION_TABLE'=>'think_session',
    'DEFAULT_MODULE'        =>  'Admin',  // 默认模块
    'DEFAULT_CONTROLLER'    =>  'Index', // 默认控制器名称
    'DEFAULT_ACTION'        =>  'login', // 默认操作名称
);

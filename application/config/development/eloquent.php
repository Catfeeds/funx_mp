<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*$config['eloquent'] = array(
'driver'    => 'mysql',
'host'      => 'localhost',
'database'  => 'jindi',
'username'  => 'root',
'password'  => '00000',
'charset'   => 'utf8',
'collation' => 'utf8_general_ci',
'prefix'    => ''
);*/

//本地测试数据库

//金地上线测试数据库
$config['eloquent'] = array(
    'driver'    => 'mysql',
    'host'      => 'mysql',
    'database'  => 'jindi',
    'username'  => 'root',
    'password'  => 'A23f@#53g3g2g=023',
    'charset'   => 'utf8',
    'collation' => 'utf8_general_ci',
    'prefix'    => '',
);

//正式上线数据库

//$config['eloquent'] = array(
//    'driver'    => 'mysql',
//    'host'      => 'rds45hf2v05567xa3knw900.mysql.rds.aliyuncs.com',
//    'database'  => 'strongberry_pms',
//    'username'  => 'strongberry',
//    'password'  => 'A23f@#53g3g2g=',
//    'charset'   => 'utf8',
//    'collation' => 'utf8_general_ci',
//    'prefix'    => ''
//);

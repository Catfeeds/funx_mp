<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-15
 * Time: 10:15
 * [web端]生活服务model
 */

class Servicetypemodel extends Basemodel
{
    protected $table = 'boss_service_type';
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
}



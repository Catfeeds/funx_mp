<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-05-15
 * Time: 11:08
 * [web端] 集中式房间model
 */
class Roomunionmodel extends Basemodel
{
    protected $table = 'boss_room_union';
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

}
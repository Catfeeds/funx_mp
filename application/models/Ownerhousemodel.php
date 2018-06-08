<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * User: wws
 * Date: 2018-06-07
 * Time: 14:54
 *  [web] 小业主房子model
 */
class Ownerhousemodel extends Basemodel
{

    public function __construct()
    {
        parent::__construct();
    }

    protected $table    = 'boss_owner_house';
    protected $hidden   = ['updated_at','deleted_at'];



}
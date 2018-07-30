<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Author:      zjh<401967974@qq.com>
 * Date:        2018/6/5 0005
 * Time:        16:36
 * Describe:
 */
class Home extends MY_Controller
{

    public function listhome(){

        $input  = $this->input->post(null,true);
        $where  = [];

        $this->load->model('storemodel');

        $ids    = new Storemodel();

        if(isset($input['city'])&&!empty($input['city'])){
            $ids    = $ids->where('city',$input['city']);
        }
        if(isset($input['name'])){
            $name   = $input['name'];
            $ids    = $ids->where('name','like',"%$name%");
        }
        $ids    = $ids->get(['id'])->map(function($query){
            return $query->id;
        });

        $this->load->model('roomtypemodel');
        $this->load->model('roomunionmodel');
        $page   = isset($input['page'])?$input['page']:1;
        $per_page   = isset($input['per_page'])?$input['per_page']:PAGINATE;

        $offset = ($page-1)*$per_page;

        $count  = Roomtypemodel::with('store','roomunion')->where('display',Roomtypemodel::DISPLAY)
            ->get()->map(function($query){
                $query->max_price   = $query->roomunion->max('rent_price');
                $query->min_price   = $query->roomunion->min('rent_price');
                return $query;
            })
            ->whereIn('store.id',$ids)
            ->count();

        $total_page = ceil($count/$per_page);

        $room_types   = Roomtypemodel::with('store','roomunion')->offset($offset)->limit($per_page)
            ->where('display',Roomtypemodel::DISPLAY)
            ->get()
            ->whereIn('store.id',$ids)
            ->map(function($query){
                $query->images  = $this->fullAliossUrl(json_decode($query->images,true),true);
                $query->max_price   = (int)$query->roomunion->max('rent_price');
                $query->min_price   = (int)$query->roomunion->min('rent_price');
                return $query;
            })->toArray();

        $this->api_res(0,['count'=>$count,'page'=>$page,'per_page'=>$per_page,'total_page'=>$total_page,'room_types'=>$room_types]);

    }



}
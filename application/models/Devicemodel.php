<?php

class Devicemodel extends Basemodel
{
    const STATE_PENDING     = 'PENDING';    // 默认
    const STATE_CONFIRM     = 'CONFIRM';    // 确认
    const STATE_COMPLETED   = 'COMPLATE';   // 完成

    protected $table        = 'devices';

    public function roomtype()
    {
        return $this->belongsTo(Roomtypemodel::class, 'room_type_id');
    }

    public function room()
    {
        return $this->belongsTo(Roomunionmodel::class, 'room_id');
    }

    public function apartment()
    {
        return $this->belongsTo(Storemodel::class, 'store_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customermodel::class, 'customer_id');
    }
}

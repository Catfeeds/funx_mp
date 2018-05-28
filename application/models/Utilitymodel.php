<?php

class Utilitymodel extends Basemodel
{
    const STATE_PENDING     = 'PENDING';    // 默认
    const STATE_CONFIRM     = 'CONFIRM';    // 确认
    const STATE_COMPLETED   = 'COMPLATE';   // 完成

    protected $table        = 'utilities';

    public function roomtype()
    {
        return $this->belongsTo(Roomtypemodel::class, 'room_type_id');
    }

    public function room()
    {
        return $this->belongsTo(Roommodel::class, 'room_id');
    }

    public function apartment()
    {
        return $this->belongsTo(Apartmentmodel::class, 'apartment_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customermodel::class, 'customer_id');
    }
}

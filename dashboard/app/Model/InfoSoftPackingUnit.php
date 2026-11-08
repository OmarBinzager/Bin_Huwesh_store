<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class InfoSoftPackingUnit extends Model
{
    protected $table = 'infosoft_packing_units';

    protected $guarded = ['id'];

    protected $casts = [
        'default_pk_qty' => 'float',
    ];
}

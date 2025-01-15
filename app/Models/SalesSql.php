<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesSql extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'sales';
    protected $fillable = ['id', 'name', 'email', 'phone', 'address', 'total', 'status', 'created_at', 'updated_at'];
    public $timestamps = true;
    protected $primaryKey = 'id';
}

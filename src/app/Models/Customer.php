<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public const CREATED_AT = 'insert_date';

    public const UPDATED_AT = 'update_date';

    protected $table = 'db_stsystem.customers';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shime' => 'integer',
            'shiharai' => 'integer',
            'shiharai_day' => 'integer',
            'user_id' => 'integer',
            'avail_flg' => 'boolean',
            'insert_date' => 'datetime',
            'insert_id' => 'integer',
            'update_date' => 'datetime',
            'update_id' => 'integer',
        ];
    }
}

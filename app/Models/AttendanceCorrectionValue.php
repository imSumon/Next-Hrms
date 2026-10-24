<?php

namespace App\Models;

use App\Models\Concerns\HasCanonicalUlid;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionValue extends Model
{
    use HasCanonicalUlid;

    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'attendance_correction_id',
        'value_stage',
        'field_key',
        'field_value',
    ];
}

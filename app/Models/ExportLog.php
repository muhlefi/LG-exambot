<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportLog extends Model
{
    protected $fillable = [
        'user_id',
        'exam_session_id',
        'document_type',
        'format',
        'path',
    ];
}

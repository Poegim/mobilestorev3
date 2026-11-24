<?php

namespace App\Models;

use App\Enums\PrintableDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PrintableDocument extends Model
{
    protected $fillable = ['documentable_type', 'documentable_id', 'type', 'html', 'ordinal'];

    protected $casts = [
        'type' => PrintableDocumentType::class,
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }
}
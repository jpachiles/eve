<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInteraction extends Model
{
    protected $fillable = [

        'conversation_id',

        'provider',
        'model',

        'input_tokens',
        'output_tokens',
        'reasoning_tokens',

        'cost',

        'first_token_ms',
        'total_ms',

        'status',

    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}

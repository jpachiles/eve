<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'title',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function interactions()
    {
        return $this->hasMany(AiInteraction::class);
    }
}

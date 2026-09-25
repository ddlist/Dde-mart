<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * DDE-Mart Admin — ChatThread model (original). Read-only support inbox.
 */
class ChatThread extends Model
{
    use HasFactory;

    protected $fillable = ['legacy_id', 'audience', 'subject', 'last_message', 'status'];

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['audience' => 'admin', 'status' => 'open'];

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id')->orderBy('sent_at')->orderBy('id');
    }
}

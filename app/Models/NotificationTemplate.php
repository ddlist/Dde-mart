<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — automated push templates (original).
 * Keys like order.placed; body supports :order_number :customer :status :total.
 */
class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'audience', 'subject', 'body', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['audience' => 'customer', 'is_active' => true];

    public function render(array $data): array
    {
        $replace = [];

        foreach ($data as $name => $value) {
            $replace[":{$name}"] = (string) $value;
        }

        return [
            'subject' => strtr($this->subject, $replace),
            'body' => strtr($this->body, $replace),
        ];
    }
}

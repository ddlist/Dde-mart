<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * DDE-Mart Admin — EmailTemplate model (original).
 * Rendering helper only; delivery via Laravel mailer lands with customer accounts.
 */
class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'subject', 'body', 'send_to_admin', 'is_active'];

    protected function casts(): array
    {
        return ['send_to_admin' => 'boolean', 'is_active' => 'boolean'];
    }

    /** Application-level defaults (DB defaults alone don't hydrate the model). */
    protected $attributes = ['send_to_admin' => false, 'is_active' => true];

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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Exceptions\SystemCategoryException;

class Category extends Model
{
    protected $fillable = ['user_id', 'name', 'type', 'is_system', 'icon', 'color'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Prevent deletion of system-default categories at the model level.
     * This guard fires regardless of the call origin (controller, artisan, etc.).
     */
    protected static function booted(): void
    {
        static::deleting(function (Category $category) {
            if ($category->is_system) {
                throw new SystemCategoryException('System categories cannot be deleted.');
            }
        });

        static::updating(function (Category $category) {
            if ($category->is_system && $category->isDirty('is_system')) {
                throw new SystemCategoryException('The is_system flag cannot be changed.');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}

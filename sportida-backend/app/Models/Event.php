<?php
// app/Models/Event.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Event extends Model
{
    use HasFactory, SoftDeletes, HasSlug;

    protected $fillable = [
        'organization_id', 'sport_id', 'type', 'status', 'name', 'slug',
        'short_name', 'description', 'program', 'rules', 'start_date',
        'end_date', 'registration_start', 'registration_end', 'city',
        'venue_name', 'venue_address', 'venue_coordinates', 'cover_image',
        'gallery', 'documents', 'max_participants', 'current_participants',
        'price_min', 'price_max', 'currency', 'is_classificational',
        'judge_panel', 'metadata', 'views_count', 'created_by',
        'published_at', 'seo_title', 'seo_description', 'seo_keywords', 'og_image'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_start' => 'datetime',
        'registration_end' => 'datetime',
        'published_at' => 'datetime',
        'gallery' => 'array',
        'documents' => 'array',
        'judge_panel' => 'array',
        'metadata' => 'array',
        'venue_coordinates' => 'point',
        'price_min' => 'decimal:2',
        'price_max' => 'decimal:2',
        'is_classificational' => 'boolean',
        'views_count' => 'integer',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ranks(): HasMany
    {
        return $this->hasMany(EventRank::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['published', 'registration_open', 'in_progress']);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now())->orderBy('start_date');
    }

    public function scopeFilterByRanks($query, array $ranks)
    {
        return $query->whereHas('ranks', function ($q) use ($ranks) {
            $q->whereIn('rank_code', $ranks);
        });
    }

    // Accessors
    public function getFormattedDateRangeAttribute(): string
    {
        if ($this->start_date->format('m') === $this->end_date->format('m')) {
            return $this->start_date->format('d') . ' - ' . $this->end_date->format('d.m.Y');
        }
        return $this->start_date->format('d.m') . ' - ' . $this->end_date->format('d.m.Y');
    }

    public function getRegistrationStatusAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }
        
        if (!$this->registration_start || !$this->registration_end) {
            return 'unknown';
        }

        $now = now();
        
        if ($now < $this->registration_start) {
            return 'pending';
        }
        
        if ($now > $this->registration_end) {
            return 'closed';
        }
        
        if ($this->max_participants && $this->current_participants >= $this->max_participants) {
            return 'full';
        }
        
        return 'open';
    }

    // Increment views
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}

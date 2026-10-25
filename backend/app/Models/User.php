<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'must_change_password',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Accesor para obtener el nombre completo
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Perfil del menor / infante asociado al acudiente
     */
    public function babyProfile(): HasOne
    {
        return $this->hasOne(BabyProfile::class, 'user_id');
    }

    /**
     * Historial de matrículas del usuario
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'user_id');
    }

    /**
     * Matrícula activa para un curso específico
     */
    public function activeEnrollmentForCourse(int $courseId): ?Enrollment
    {
        return $this->enrollments()
            ->where('course_id', $courseId)
            ->where('expires_at', '>=', now()->toDateString())
            ->latest('id')
            ->first();
    }

    /**
     * Cuentos consumidos/abiertos por el usuario
     */
    public function storyReads(): HasMany
    {
        return $this->hasMany(StoryRead::class, 'user_id');
    }
}

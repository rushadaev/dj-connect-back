<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telegram_id',
        'phone_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $with = ['dj'];

    protected $appends = [
        'is_dj',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the DJ profile associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dj()
    {
        return $this->hasOne(DJ::class);
    }

    /**
     * Get the user's DJ status.
     *
     * @return bool
     */
    public function getIsDjAttribute(): bool
    {
        return $this->dj !== null;
    }

    /**
     * Attach a DJ role or information to the user.
     *
     * @param array $data Information or data to attach to the user as a DJ.
     * @return Model 
     */
    public function attachDJ(array $djData)
    {
        if ($this->dj) {
            throw new \Exception('User already has a DJ profile.');
        }

        $djData['user_id'] = $this->id;
        return DJ::create($djData);
    }
    /**
     * Get User orders
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

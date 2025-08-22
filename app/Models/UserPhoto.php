<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPhoto extends Model
{
    protected $guarded   = [];
    protected $fillable = ['user_id', 'photo_url'];
    protected $appends = ['absolute_photo_url'];

    public function getAbsolutePhotoUrlAttribute(): string
    {
        $url = $this->photo_url ?? '';
        if ($url === '') {
            return $url;
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        if ($url[0] !== '/') {
            $url = '/' . $url;
        }
        $base = rtrim(config('app.url', env('APP_URL', 'http://localhost:8000')), '/');
        return $base . $url;
    }
}

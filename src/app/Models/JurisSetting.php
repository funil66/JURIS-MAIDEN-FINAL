<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurisSetting extends Model
{
    protected $table = 'juris_settings';

    protected $fillable = [
        'office_name','phone','whatsapp','contact_email','diligencias_email','address','oab','website','primary_color'
    ];

    public static function firstOrMakeFromConfig(): self
    {
        $first = static::first();

        if (!$first) {
            $first = static::create([
                'office_name' => config('juris.office_name'),
                'phone' => config('juris.phone'),
                'whatsapp' => config('juris.whatsapp'),
                'contact_email' => config('juris.emails.contact'),
                'diligencias_email' => config('juris.emails.diligencias'),
                'address' => config('juris.address'),
                'oab' => config('juris.oab'),
                'website' => config('juris.website'),
                'primary_color' => config('juris.primary_color'),
            ]);
        }

        return $first;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class City
 * @package App\Models
 */
class City extends Model
{
    //
    use SoftDeletes;
    /**
     * @var string
     */
    protected $table = "cities";
    /**
     * @var array
     */
    protected $dates = ['deleted_at'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cityTrans()
    {
        return $this->hasMany('App\Models\CityTranslation', 'city_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo('App\Models\Country', 'country_id');
    }

    /**
     * @param null $lang_code
     * @return Model|null|object|static
     */
    public function translate($lang_code = null)
    {
        if (!$lang_code) {

            $lang_id = Language::where('lang_code', app()->getLocale())->first()->id;
        } else {

            $lang_id = Language::where('lang_code', $lang_code)->first()->id;
        }

        return $this->cityTrans()->where('lang_id', $lang_id)->first();
    }

}

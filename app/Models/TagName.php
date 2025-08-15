<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagName extends Model
{
    protected $table = 'tag_names';
    protected $fillable = ['tag_id','body', 'follow_to'];

    public static array $post=['طرطوس','اللاذقية','دمشق','حمص','ريف دمشق','ترفيهي','طبيعي','ادلب','الحسكة','الرقة'];
    public static array $article=['السويداء','حماة','درعا','دير الزور','حلب','القنيطرة','طرطوس','اللاذقية','دمشق','حمص','ريف دمشق','ادلب','الحسكة','الرقة','ترفيهية','دينية','ثقافية','تاريخية','أثرية','طبيعية'];
    public static array $trip=['طرطوس','اللاذقية','دمشق','حمص','ريف دمشق','ترفيهي','طبيعي','ادلب','الحسكة','الرقة'];
    public static array $place=['طرطوس','اللاذقية','دمشق','حمص','ريف دمشق','ترفيهي','طبيعي','ادلب','الحسكة','الرقة'];

    public function tags()
    {
        return $this->hasMany(Tag::class, 'tag_name_id');
    }
}

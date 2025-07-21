<?php

namespace Database\Seeders;

use App\Models\TagName;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagNameSeeder extends Seeder
{
    public function run(): void
    {
        foreach (TagName::$trip as $name) {
            TagName::create(['body' => $name,'follow_to'=>'trip']);
        }
        foreach (TagName::$post as $name) {
            TagName::create(['body' => $name,'follow_to'=>'post']);
        }
        foreach (TagName::$article as $name) {
            TagName::create(['body' => $name,'follow_to'=>'article']);
        }
        foreach (TagName::$place as $name) {
            TagName::create(['body' => $name,'follow_to'=>'place']);
        }
    }
}

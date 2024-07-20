<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('states')->insert([
            'id' => \DB::raw('gen_random_uuid()'),
            'code' => 'SK',
            'name' => 'Sikkim',
            'map' => 'maps',
            'officialPage' => 'page1',
            'descriptionShort' => 'Your Short Description',
            'descriptionBrief' => 'Your Brief Description',
            'population' => '700,000',
            'populationMale' => '3,50,000',
            'populationFemale' => '3,50,000',
            'populationElectors' => '4,45,000',
            'populationElectorsMale' => '2,21,000',
            'populationElectorsFemale' => '2,24,000',
            'gdp' => '5 Billion',
            'languages' => 'Nepali,Lepcha',
            'hashTags' => '#sikkim',
            'createdBy' => '9a2117c8-5e13-4a07-88a8-7627e5adf969', 
            'updatedBy' => '9a2117c8-5e13-4a07-88a8-7627e5adf969', 
        ]);
    }
}

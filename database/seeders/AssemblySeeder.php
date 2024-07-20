<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
class AssemblySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('assembly_consituencies')->insert([
            [
                'id' => \DB::raw('gen_random_uuid()'),
                'code' => 'SK',
                'name' => 'Gangtok',
                'type' => 'GN',
                'map' => 'map',
                'stateid' =>'b226bf0d-aa8b-4aac-848d-8f4088bbc73d',
                'officialPage' => 'map',
                'descriptionShort' => 'Short Description1',
                'descriptionBrief' => 'Brief Description1',
                'population' => '20,649',
                'populationMale' => '9,000',
                'populationFemale' => '11,000',
                'populationElectors' => '11,649',
                'populationElectorsMale' => '6,649',
                'populationElectorsFemale' => '5,000',
                'languages' => 'Nepali',
                'hashTags' => '#Gangtok',
                'districId' => '9a2117c8-5e13-4a07-88a8-7627e5adf960',
                'createdAt' => now(),
                'updatedAt' => now(),
                'createdBy' => '9a2117c8-5e13-4a07-88a8-7627e5adf969', 
                'updatedBy' => '9a2117c8-5e13-4a07-88a8-7627e5adf969', 
    
            ],
        ]);
    }
}

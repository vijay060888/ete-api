<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Auth;
use DB;
use Str;
class PagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

     public function run(): void
     {
         $userId = Auth::id();
     
         DB::table('pages')->insert([
             [ 
                'id' => '9a0fed09-8d1d-455b-89ad-cb34a4e28a97',
                 'name' => 'Page A',
                 'permalink' => 'page-a',
                 'parentContituencyPageId' => null,
                 'parentStatePageId' => null,
                 'parentPartyPageId' => null,
                 'parentLoksabhaConsituencyPageId' => null,
                 'parentAssemblyConsituencyPageId' => null,
                 'boothId' => null,
                 'assemblyConsituencyId' => null,
                 'loksabhaConsituencyId' => null,
                 'createdAt' => now(),
                 'updatedAt' => now(),
                 'createdBy' => $userId, // Set createdBy to the authenticated user's ID
                 'updatedBy' => $userId, // Set updatedBy to the authenticated user's ID
             ],
             [
                'id' => '9a0fed09-8d1d-455b-89ad-cb34a4e28a98',
                 'name' => 'Page B',
                 'permalink' => 'page-b',
                 'parentContituencyPageId' => null,
                 'parentStatePageId' => null,
                 'parentPartyPageId' => null,
                 'parentLoksabhaConsituencyPageId' => null,
                 'parentAssemblyConsituencyPageId' => null,
                 'boothId' => null,
                 'assemblyConsituencyId' => null,
                 'loksabhaConsituencyId' => null,
                 'createdAt' => now(),
                 'updatedAt' => now(),
                 'createdBy' => $userId, // Set createdBy to the authenticated user's ID
                 'updatedBy' => $userId, // Set updatedBy to the authenticated user's ID
             ],
             [
                'id' => '9a0fed09-8d1d-455b-89ad-cb34a4e28a99',
                 'name' => 'Page C',
                 'permalink' => 'page-c',
                 'parentContituencyPageId' => null,
                 'parentStatePageId' => null,
                 'parentPartyPageId' => null,
                 'parentLoksabhaConsituencyPageId' => null,
                 'parentAssemblyConsituencyPageId' => null,
                 'boothId' => null,
                 'assemblyConsituencyId' => null,
                 'loksabhaConsituencyId' => null,
                 'createdAt' => now(),
                 'updatedAt' => now(),
                 'createdBy' => $userId, // Set createdBy to the authenticated user's ID
                 'updatedBy' => $userId, // Set updatedBy to the authenticated user's ID
             ],
             [
                'id' => '9a0fed09-8d1d-455b-89ad-cb34a4e28a9a',
                 'name' => 'Page D',
                 'permalink' => 'page-d',
                 'parentContituencyPageId' => null,
                 'parentStatePageId' => null,
                 'parentPartyPageId' => null,
                 'parentLoksabhaConsituencyPageId' => null,
                 'parentAssemblyConsituencyPageId' => null,
                 'boothId' => null,
                 'assemblyConsituencyId' => null,
                 'loksabhaConsituencyId' => null,
                 'createdAt' => now(),
                 'updatedAt' => now(),
                 'createdBy' => $userId, // Set createdBy to the authenticated user's ID
                 'updatedBy' => $userId, // Set updatedBy to the authenticated user's ID
             ],
             [
                'id' => '9a0fed09-8d1d-455b-89ad-cb34a4e28a9b',
                 'name' => 'Page E',
                 'permalink' => 'page-e',
                 'parentContituencyPageId' => null,
                 'parentStatePageId' => null,
                 'parentPartyPageId' => null,
                 'parentLoksabhaConsituencyPageId' => null,
                 'parentAssemblyConsituencyPageId' => null,
                 'boothId' => null,
                 'assemblyConsituencyId' => null,
                 'loksabhaConsituencyId' => null,
                 'createdAt' => now(),
                 'updatedAt' => now(),
                 'createdBy' => $userId, // Set createdBy to the authenticated user's ID
                 'updatedBy' => $userId, // Set updatedBy to the authenticated user's ID
             ],
         ]);
     }
    }     
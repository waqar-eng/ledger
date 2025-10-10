<?php

namespace Database\Seeders;

use App\DeletionPeriod;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Users
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@zee.com',
                'user_type'=> 'owner',
                'password' => Hash::make('admin@zee$#1'),
            ],
            [
                'name' => 'Investor User',
                'email' => 'user@zee.com',
                'user_type'=> 'investor',
                'password' => Hash::make('112233'),
            ],
        ];
        User::insert($users);

         $cutomers = [
             [
                'name' => 'walk-in customer',
                'email' => 'walkincustomer@gmail.com',
                'phone_number' => '03001034567',
                'address'=>"walkincustomer"
            ],
             [
                'name' => 'Ali',
                'email' => 'ali@zee.com',
                'phone_number' => '03001234567',
                'address'=>"abc city"
            ],
              [
                'name' => 'Second User',
                'email' => 'user@zee.com',
                'phone_number' => '03000234567',
                'address'=>"abc city"
            ],
         ];

        Customer::insert($cutomers);
         $category = [
            [
                'categoryName' => 'First transaction'
            ],
            [
                'categoryName' => 'Company'
            ],
        ];
        Category::insert($category);
        // app settings
        AppSetting::updateOrCreate(
            ['key' => 'deletion_period'],
            ['value' => DeletionPeriod::OneWeek->value]
        );
    }
}



<?php

namespace Database\Seeders;

use App\AppSettingPeriod;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\LedgerSeason;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
          // Categories
        $categories = [
            ['categoryName' => 'Kapas', 'created_at' => now(),'updated_at' => now()],
            ['categoryName' => 'Makai', 'created_at' => now(),'updated_at' => now()],
        ];
        Category::insert($categories);

        // cutomers
        $cutomers = [
            [
                'name' => 'Admin User',
                'email' => 'admin@zee.com',
                'phone_number' => '03001034577',
                'address'=>"admin address",
                'type'=> 'owner',
                'password' => Hash::make('admin@zee$#1'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Investor User',
                'email' => 'user@zee.com',
                'phone_number' => '03001034587',
                'address'=>"investor address",
                'type'=> 'investor',
                'password' => Hash::make('112233'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Walk-in buyer',
                'email' => 'walk-in-buyer@gmail.com',
                'phone_number' => '03001034567',
                'address'=>"walkinbuyer",
                'type'=>"buyer",
                'password' => Hash::make('112233'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Walk-in supplier',
                'email' => 'walk-in-supplier@gmail.com',
                'phone_number' => '03001134567',
                'address'=>"walkinsupplier",
                'type'=>"supplier",
                'password' => Hash::make('112233'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Other User',
                'email' => 'other@user.com',
                'phone_number' => '03001234567',
                'address'=>"other city",
                'type'=>"other",
                'password' => Hash::make('112233'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        User::insert($cutomers);
        // app settings
        AppSetting::updateOrCreate(
            ['key' => 'deletion_period'],
            ['value' => AppSettingPeriod::OneWeek->value]
        );
        AppSetting::updateOrCreate(
            ['key' => 'updation_period'],
            ['value' => AppSettingPeriod::OneWeek->value]
        );
        LedgerSeason::insert([
            'name'=>'Default Season',
            'description'=>'Default Season',
            'status'=>'active',
            'start_date'=>now(),
            'end_date'=>now()->addMonths(6),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
    }
}

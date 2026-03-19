<?php

namespace Database\Seeders;

use App\AppSettingPeriod;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\LedgerSeason;
use App\Models\User;
use Carbon\Carbon;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
          // Categories
        // $categories = [
        //     ['categoryName' => 'Kapas', 'created_at' => now(),'updated_at' => now()],
        //     ['categoryName' => 'Makai', 'created_at' => now(),'updated_at' => now()],
        // ];
        // Category::insert($categories);
        // ✅ Create Permissions
        $permissions = [
            // Dashboard
            'dashboardSummary ledger',
            
            // User
            'index user',
            'create user',
            'update user',
            'destroy user',
            'AllUsers',

            // Role
            'index role',
            'create role',
            'update role',
            'destroy role',
            'manage role',

            // Ledger
            'index ledger',
            'create ledger',
            'update ledger',
            'destroy ledger',

            // Ledger Season
            'index ledger season',
            'create ledger season',
            'update ledger season',
            'destroy ledger season',
            'view ledger season summary',

            // Sale
            'index sale',
            'create sale',
            'update sale',
            'destroy sale',

            // Purchase
            'index purchase',
            'create purchase',
            'update purchase',
            'destroy purchase',

            // Investment
            'index investment',
            'create investment',
            'update investment',
            'destroy investment',

            // Category
            'index category',
            'create category',
            'update category',
            'destroy category',

            // Expense Type
            'index expense type',
            'create expense type',
            'update expense type',
            'destroy expense type',

            // Activity Log
            'index activity log',
            'destroy activity log',

            // App Setting
            'index app setting',
            'create app setting',
            'update app setting',
            'destroy app setting',

            // Expense
            'index expense',
            'create expense',
            'update expense',
            'destroy expense',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'api']);
        $superAdmin->givePermissionTo(Permission::all());
        $adminUser = User::where('email', 'admin@zee.com')->first();
        $adminUser->assignRole('Super Admin');
        // cutomers
        // $cutomers = [
        //     [
        //         'name' => 'Admin User',
        //         'email' => 'admin@zee.com',
        //         'phone_number' => '03001034577',
        //         'address'=>"admin address",
        //         'type'=> 'owner',
        //         'password' => Hash::make('admin@zee$#1'),
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name' => 'Investor User',
        //         'email' => 'user@zee.com',
        //         'phone_number' => '03001034587',
        //         'address'=>"investor address",
        //         'type'=> 'investor',
        //         'password' => Hash::make('112233'),
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name' => 'Walk-in buyer',
        //         'email' => 'walk-in-buyer@gmail.com',
        //         'phone_number' => '03001034567',
        //         'address'=>"walkinbuyer",
        //         'type'=>"buyer",
        //         'password' => Hash::make('112233'),
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name' => 'Walk-in supplier',
        //         'email' => 'walk-in-supplier@gmail.com',
        //         'phone_number' => '03001134567',
        //         'address'=>"walkinsupplier",
        //         'type'=>"supplier",
        //         'password' => Hash::make('112233'),
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name' => 'Other User',
        //         'email' => 'other@user.com',
        //         'phone_number' => '03001234567',
        //         'address'=>"other city",
        //         'type'=>"other",
        //         'password' => Hash::make('112233'),
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        // ];
        // User::insert($cutomers);
        // // app settings
        // AppSetting::updateOrCreate(
        //     ['key' => 'deletion_period'],
        //     ['value' => AppSettingPeriod::OneWeek->value]
        // );
        // AppSetting::updateOrCreate(
        //     ['key' => 'updation_period'],
        //     ['value' => AppSettingPeriod::OneWeek->value]
        // );
        // LedgerSeason::insert([
        //     'name'=>'Default Season',
        //     'description'=>'Default Season',
        //     'status'=>'active',
        //     'start_date'=>now(),
        //     'end_date'=>now()->addMonths(6),
        //     'created_at'=>now(),
        //     'updated_at'=>now(),
        // ]);
    }
}

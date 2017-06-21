<?php

use Illuminate\Database\Seeder;
use App\Models\Role;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      if (App::isLocal()) {
          echo "App is running locally, created debug users.\n";
          $data = array(
            ['id' => 1, 'firstname' => 'Jack', 'lastname' => 'Jackson', 'email' => 'admin@noreply.com', 'password' => Hash::make('password123')],
            ['id' => 2, 'firstname' => 'John', 'lastname' => 'Johnson', 'email' => 'user@noreply.com', 'password' => Hash::make('password123')],
          );
          DB::table('users')->insert($data);

          //Insert related roles
          $userId = Role::where('name','user')->first()->id;
          $adminId = Role::where('name','admin')->first()->id;
          $data = array(
            ['user_id' => 1, 'role_id' => $userId],
            ['user_id' => 1, 'role_id' => $adminId],
            ['user_id' => 2, 'role_id' => $userId],
          );
          DB::table('role_user')->insert($data);
      }

    }
}

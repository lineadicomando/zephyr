<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });
        // $now = now();
        // $userId = DB::table('users')->insert([
        //     [
        //         'name' => 'root',
        //         'email' => 'root@mail.devel',
        //         'password' => Hash::make('jQ9)(^efuaxtj9<@'),
        //         'email_verified_at' => $now,
        //         'created_at' => $now,
        //         'updated_at' => $now,
        //     ],
        // ]);
        // $rootGroup = Group::where('role', Group::ROLE_ROOT)->first();
        // $user = User::where('name', 'root')->first();
        // if ($user && $rootGroup) {
        //     $user->groups()->attach([$rootGroup->id]);
        // }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

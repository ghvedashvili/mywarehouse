<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_role_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('role');
            $table->date('effective_from');
            $table->date('effective_to');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['role', 'effective_from', 'effective_to']);
        });

        // backfill — არსებულ თანამშრომლებს ერთი ჯანჯაწერი ექაუებათ ანგარიშის
        // შექმნის დღიდან, მიმდინარე როლით, რომ ისტორია დღეიდანვე სრული იყოს.
        $now = now()->toDateString();
        foreach (User::all() as $user) {
            DB::table('user_role_history')->insert([
                'user_id'        => $user->id,
                'role'           => $user->role,
                'effective_from' => $user->created_at ? $user->created_at->toDateString() : '2000-01-01',
                'effective_to'   => '2050-01-01',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role_history');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type', 20)->default('listener')->after('email'); // admin | listener | creator
            $table->string('avatar_path')->nullable()->after('account_type');
        });
        // Every user created before the mobile API existed is a control-room admin.
        DB::table('users')->update(['account_type' => 'admin']);

        Schema::table('contents', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('art_style');
            $table->string('banner_path')->nullable()->after('cover_path');
            $table->string('file_path')->nullable()->after('banner_path');
        });

        Schema::table('creators', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
        Schema::table('listeners', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('listeners', fn (Blueprint $t) => $t->dropConstrainedForeignId('user_id'));
        Schema::table('creators', fn (Blueprint $t) => $t->dropConstrainedForeignId('user_id'));
        Schema::table('contents', fn (Blueprint $t) => $t->dropColumn(['cover_path', 'banner_path', 'file_path']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['account_type', 'avatar_path']));
    }
};

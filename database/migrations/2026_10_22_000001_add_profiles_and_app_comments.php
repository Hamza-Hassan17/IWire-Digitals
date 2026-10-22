<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 20)->nullable()->after('avatar_path'); // male | female | other | prefer_not_to_say
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('country', 80)->nullable()->after('date_of_birth');
            $table->string('bio', 500)->nullable()->after('country');
        });

        // App users of any account type can comment, so comments link to users;
        // listener_id stays for comments that came from the original listener records.
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('listener_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('comments', fn (Blueprint $t) => $t->dropConstrainedForeignId('user_id'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['gender', 'date_of_birth', 'country', 'bio']));
    }
};

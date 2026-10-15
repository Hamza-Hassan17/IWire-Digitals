<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('type'); // Musician | Filmmaker
            $table->string('status')->default('Active'); // Active | Suspended
            $table->unsignedBigInteger('total_plays')->default(0);
            $table->unsignedSmallInteger('art_seed')->default(0);
            $table->date('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // Music | Films
            $table->string('title');
            $table->string('genre');
            $table->string('mood')->nullable();
            $table->text('description')->nullable();
            $table->string('duration');
            $table->unsignedBigInteger('plays')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('comments_count')->default(0);
            $table->string('status')->default('Pending'); // Pending | Live | Unpublished | Flagged | Rejected
            $table->boolean('explicit')->default(false);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedSmallInteger('art_seed')->default(0);
            $table->unsignedTinyInteger('art_style')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('reject_reasons')->nullable();
            $table->text('reject_note')->nullable();
            $table->timestamps();
        });

        Schema::create('listeners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('status')->default('Active');
            $table->unsignedInteger('streams')->default(0);
            $table->string('device')->default('Android');
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('art_seed')->default(0);
            $table->timestamp('last_active_at')->nullable();
            $table->date('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listener_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('status')->default('Visible'); // Visible | Reported | Hidden
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('kind'); // Track | Film | Comment
            $table->foreignId('content_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->string('subject_name');
            $table->string('reason');
            $table->unsignedInteger('count')->default(1);
            $table->string('status')->default('Open'); // Open | Resolved | Dismissed
            $table->unsignedSmallInteger('art_seed')->default(0);
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('icon');
            $table->string('color');
            $table->string('action');
            $table->string('target');
            $table->string('context')->default('All'); // All | Music | Films
            $table->timestamps();
        });

        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('body');
            $table->string('audience');
            $table->string('status')->default('Sent'); // Sent | Scheduled
            $table->unsignedInteger('reach')->nullable();
            $table->decimal('ctr', 5, 1)->nullable();
            $table->timestamp('send_at')->nullable();
            $table->timestamps();
        });

        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('kind'); // music | films | moods
            $table->string('name');
            $table->string('color');
            $table->unsignedInteger('position')->default(0);
            $table->unsignedSmallInteger('art_seed')->default(0);
            $table->unsignedTinyInteger('art_style')->default(0);
            $table->timestamps();
        });

        Schema::create('curation_rows', function (Blueprint $table) {
            $table->id();
            $table->string('home'); // Music | Films
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->json('content_ids');
            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->string('tagline');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'hero_slides', 'curation_rows', 'genres', 'push_notifications', 'activities', 'reports', 'comments', 'listeners', 'contents', 'creators'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_name', 5);
            $table->char('primary_color', 7)->default('#35604b');
            $table->timestamps();
        });

        Schema::create('matches', function (Blueprint $table): void {
            $table->id();
            $table->string('competition')->default('Premier League');
            $table->string('slug')->unique();
            $table->foreignId('home_team_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->restrictOnDelete();
            $table->timestamp('starts_at')->index();
            $table->string('status', 16)->default('scheduled');
            $table->string('venue')->nullable();
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->unsignedTinyInteger('minute')->nullable();
            $table->boolean('is_sample')->default(false);
            $table->timestamps();
            $table->index(['status', 'starts_at']);
        });

        Schema::create('watch_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->char('market_code', 2);
            $table->string('market_name');
            $table->string('broadcaster');
            $table->string('website_url');
            $table->boolean('is_official')->default(false);
            $table->timestamps();
            $table->index(['match_id', 'market_code', 'is_official']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_options');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('teams');
    }
};

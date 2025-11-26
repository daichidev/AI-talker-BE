<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_earthquakes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('earthquakes', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique(); // P2PQuake の id
            $table->string('version')->nullable();
            $table->dateTime('reported_at')->nullable(); // item.time
            $table->dateTime('occurred_at')->nullable(); // earthquake.time
            $table->string('hypocenter_name')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->integer('depth_km')->nullable();
            $table->decimal('magnitude', 4, 1)->nullable();
            $table->integer('max_scale')->nullable();
            $table->string('max_scale_label')->nullable();
            $table->string('tsunami_code')->nullable();
            $table->string('tsunami_label')->nullable();
            $table->json('raw')->nullable(); // 元 JSON 全体を保存しておく
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('earthquakes');
    }
};

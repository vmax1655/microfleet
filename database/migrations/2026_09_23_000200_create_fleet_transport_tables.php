<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depots', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->decimal('default_fuel_efficiency_kml', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plate_number')->unique();
            $table->string('vin')->nullable()->unique();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color')->nullable();
            $table->string('fuel_type')->default('gasoline');
            $table->decimal('tank_capacity_liters', 8, 2)->nullable();
            $table->decimal('current_odometer_km', 12, 2)->default(0);
            $table->string('status')->default('available');
            $table->date('acquisition_date')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('document_number')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('valid');
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_number')->nullable()->unique();
            $table->string('license_number')->unique();
            $table->string('license_restrictions')->nullable();
            $table->date('license_expires_at');
            $table->date('medical_clearance_expires_at')->nullable();
            $table->string('phone')->nullable();
            $table->string('availability_status')->default('available');
            $table->decimal('safety_score', 5, 2)->default(100);
            $table->timestamps();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_depot_id')->nullable()->constrained('depots')->nullOnDelete();
            $table->string('route_code')->unique();
            $table->string('name');
            $table->string('center_code')->nullable();
            $table->string('destination_name');
            $table->decimal('destination_latitude', 10, 7)->nullable();
            $table->decimal('destination_longitude', 10, 7)->nullable();
            $table->decimal('planned_distance_km', 10, 2);
            $table->unsignedInteger('estimated_duration_minutes');
            $table->string('road_profile')->default('mixed');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_number')->unique();
            $table->foreignId('requester_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose');
            $table->unsignedSmallInteger('passenger_count')->default(1);
            $table->string('load_level')->default('light');
            $table->dateTime('scheduled_start_at');
            $table->dateTime('scheduled_end_at');
            $table->decimal('predicted_fuel_liters', 10, 2)->nullable();
            $table->decimal('predicted_cost', 12, 2)->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('dispatch_number')->unique();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->constrained();
            $table->foreignId('dispatcher_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('checked_out_at')->nullable();
            $table->decimal('start_odometer_km', 12, 2)->nullable();
            $table->string('checkout_condition')->nullable();
            $table->string('status')->default('assigned');
            $table->timestamps();
        });

        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number')->unique();
            $table->foreignId('dispatch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('departed_at')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->decimal('start_odometer_km', 12, 2)->nullable();
            $table->decimal('end_odometer_km', 12, 2)->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->string('status')->default('scheduled');
            $table->text('return_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed_kph', 8, 2)->nullable();
            $table->unsignedSmallInteger('heading')->nullable();
            $table->dateTime('recorded_at');
            $table->timestamps();
        });

        Schema::create('fuel_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->string('station_name');
            $table->string('receipt_number')->nullable();
            $table->string('fuel_type')->default('gasoline');
            $table->decimal('liters', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_cost', 12, 2);
            $table->decimal('odometer_km', 12, 2);
            $table->dateTime('fueled_at');
            $table->string('status')->default('posted');
            $table->timestamps();
        });

        Schema::create('trip_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('expense_type');
            $table->decimal('amount', 12, 2);
            $table->string('receipt_number')->nullable();
            $table->text('description')->nullable();
            $table->string('approval_status')->default('pending');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('incurred_at');
            $table->timestamps();
        });

        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->string('work_order_number')->unique();
            $table->string('service_type');
            $table->text('description')->nullable();
            $table->decimal('odometer_km', 12, 2)->nullable();
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('labor_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->string('status')->default('open');
            $table->dateTime('opened_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->decimal('fuel_cost', 12, 2)->default(0);
            $table->decimal('expense_cost', 12, 2)->default(0);
            $table->decimal('maintenance_allocation', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->decimal('cost_per_km', 12, 2)->nullable();
            $table->string('center_code')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('ml_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model_name');
            $table->string('model_version')->default('v1');
            $table->decimal('predicted_fuel_liters', 10, 2);
            $table->decimal('predicted_cost', 12, 2);
            $table->decimal('actual_fuel_liters', 10, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->decimal('variance_percent', 8, 2)->nullable();
            $table->json('feature_payload')->nullable();
            $table->dateTime('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_predictions');
        Schema::dropIfExists('transport_costs');
        Schema::dropIfExists('maintenance_records');
        Schema::dropIfExists('trip_expenses');
        Schema::dropIfExists('fuel_transactions');
        Schema::dropIfExists('trip_locations');
        Schema::dropIfExists('trips');
        Schema::dropIfExists('dispatches');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('vehicle_documents');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('vehicle_types');
        Schema::dropIfExists('depots');
    }
};

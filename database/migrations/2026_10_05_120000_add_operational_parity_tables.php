<?php

use App\Models\Permission;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive parity tables/columns for ManCentre behavioral gaps.
 * Creates new tables and nullable columns only. Does not drop or rewrite existing data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('custom_lists')) {
            Schema::create('custom_lists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 64);
                $table->string('name');
                $table->string('code', 64);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'type', 'code']);
                $table->index(['company_id', 'type', 'is_active']);
            });
        }

        if (! Schema::hasTable('measure_units')) {
            Schema::create('measure_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code', 32);
                $table->string('symbol', 16)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['company_id', 'code']);
            });
        }

        if (! Schema::hasTable('subcategories')) {
            Schema::create('subcategories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'slug']);
                $table->index(['category_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('product_options')) {
            Schema::create('product_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('selection_mode', 16)->default('multiple'); // fixed, multiple
                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['company_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('option_variants')) {
            Schema::create('option_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_option_id')->constrained('product_options')->cascadeOnDelete();
                $table->string('name');
                $table->decimal('extra_price', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_option_product')) {
            Schema::create('product_option_product', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_option_id')->constrained('product_options')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['product_id', 'product_option_id'], 'product_option_unique');
            });
        }

        if (! Schema::hasTable('printers')) {
            Schema::create('printers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('role', 16)->default('both'); // customer, kitchen, both
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->json('ticket_config')->nullable();
                $table->json('kitchen_config')->nullable();
                $table->json('advanced_config')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'store_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('delivery_platforms')) {
            Schema::create('delivery_platforms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('code', 64);
                $table->string('kind', 16)->default('external'); // internal, external
                $table->boolean('is_active')->default(true);
                $table->boolean('is_delivery_agent')->default(true);
                $table->string('commission_type', 16)->default('percent'); // percent, fixed
                $table->decimal('commission_value', 12, 2)->default(0);
                $table->timestamps();

                $table->unique(['company_id', 'code']);
            });
        }

        $this->addColumns('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'subcategory_id')) {
                $table->foreignId('subcategory_id')->nullable()->after('category_id')->constrained('subcategories')->nullOnDelete();
            }
            if (! Schema::hasColumn('products', 'measure_unit_id')) {
                $table->foreignId('measure_unit_id')->nullable()->after('unit')->constrained('measure_units')->nullOnDelete();
            }
        });

        $this->addColumns('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'payment_status_code')) {
                $table->string('payment_status_code', 32)->default('unpaid')->after('status');
            }
            if (! Schema::hasColumn('sales', 'ticket_name')) {
                $table->string('ticket_name')->nullable()->after('reference');
            }
            if (! Schema::hasColumn('sales', 'ticket_group')) {
                $table->string('ticket_group')->nullable()->after('ticket_name');
            }
            if (! Schema::hasColumn('sales', 'service_mode')) {
                $table->string('service_mode', 32)->nullable()->after('ticket_group');
            }
            if (! Schema::hasColumn('sales', 'appointment_at')) {
                $table->timestamp('appointment_at')->nullable()->after('sold_at');
            }
            if (! Schema::hasColumn('sales', 'pickup_date')) {
                $table->date('pickup_date')->nullable()->after('appointment_at');
            }
            if (! Schema::hasColumn('sales', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->after('notes');
            }
        });

        $this->addColumns('sale_payments', function (Blueprint $table) {
            $this->paymentWorkflowColumns($table, 'sale_payments');
        });

        $this->addColumns('pos_sales', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_sales', 'payment_status_code')) {
                $table->string('payment_status_code', 32)->default('unpaid')->after('status');
            }
            if (! Schema::hasColumn('pos_sales', 'ticket_name')) {
                $table->string('ticket_name')->nullable()->after('number');
            }
            if (! Schema::hasColumn('pos_sales', 'ticket_group')) {
                $table->string('ticket_group')->nullable()->after('ticket_name');
            }
            if (! Schema::hasColumn('pos_sales', 'service_mode')) {
                $table->string('service_mode', 32)->nullable()->after('ticket_group');
            }
            if (! Schema::hasColumn('pos_sales', 'service_mode_list_id')) {
                $table->foreignId('service_mode_list_id')->nullable()->after('service_mode')->constrained('custom_lists')->nullOnDelete();
            }
            if (! Schema::hasColumn('pos_sales', 'predefined_ticket_id')) {
                $table->foreignId('predefined_ticket_id')->nullable()->after('service_mode_list_id')->constrained('custom_lists')->nullOnDelete();
            }
            if (! Schema::hasColumn('pos_sales', 'delivery_platform_id')) {
                $table->foreignId('delivery_platform_id')->nullable()->after('predefined_ticket_id')->constrained('delivery_platforms')->nullOnDelete();
            }
            if (! Schema::hasColumn('pos_sales', 'appointment_at')) {
                $table->timestamp('appointment_at')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('pos_sales', 'pickup_date')) {
                $table->date('pickup_date')->nullable()->after('appointment_at');
            }
            if (! Schema::hasColumn('pos_sales', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->after('pickup_date');
            }
            if (! Schema::hasColumn('pos_sales', 'client_uuid')) {
                $table->uuid('client_uuid')->nullable()->unique()->after('number');
            }
            if (! Schema::hasColumn('pos_sales', 'amount_refunded')) {
                $table->decimal('amount_refunded', 14, 2)->default(0)->after('total_ttc');
            }
        });

        $this->addColumns('pos_sale_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_sale_lines', 'options_payload')) {
                $table->json('options_payload')->nullable()->after('sku');
            }
            if (! Schema::hasColumn('pos_sale_lines', 'returned_quantity')) {
                $table->decimal('returned_quantity', 14, 3)->default(0)->after('line_total');
            }
        });

        $this->addColumns('pos_payments', function (Blueprint $table) {
            $this->paymentWorkflowColumns($table, 'pos_payments');
        });

        $this->addColumns('crm_activities', function (Blueprint $table) {
            if (! Schema::hasColumn('crm_activities', 'recurrence')) {
                $table->string('recurrence', 16)->default('none')->after('priority');
            }
            if (! Schema::hasColumn('crm_activities', 'recurrence_until')) {
                $table->date('recurrence_until')->nullable()->after('recurrence');
            }
            if (! Schema::hasColumn('crm_activities', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('completed_at');
            }
            if (! Schema::hasColumn('crm_activities', 'parent_activity_id')) {
                $table->foreignId('parent_activity_id')->nullable()->after('customer_id')->constrained('crm_activities')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('payment_collections')) {
            Schema::create('payment_collections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sale_payment_id')->nullable()->constrained('sale_payments')->cascadeOnDelete();
                $table->foreignId('pos_payment_id')->nullable()->constrained('pos_payments')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 32); // scheduled, rescheduled, collected, cancelled, confirmed
                $table->decimal('amount', 14, 2)->default(0);
                $table->timestamp('scheduled_for')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'action']);
            });
        }

        if (! Schema::hasTable('sale_refunds')) {
            Schema::create('sale_refunds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sale_id')->nullable()->constrained('sales')->cascadeOnDelete();
                $table->foreignId('pos_sale_id')->nullable()->constrained('pos_sales')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('number');
                $table->string('type', 16)->default('partial'); // partial, full
                $table->string('method', 32);
                $table->string('reason')->nullable();
                $table->text('notes')->nullable();
                $table->decimal('amount', 14, 2);
                $table->boolean('restock')->default(false);
                $table->string('status', 32)->default('completed'); // completed, restocked
                $table->json('lines')->nullable();
                $table->timestamp('refunded_at');
                $table->timestamps();

                $table->index(['company_id', 'refunded_at']);
                $table->index(['sale_id']);
                $table->index(['pos_sale_id']);
            });
        }

        if (! Schema::hasTable('crm_activity_attachments')) {
            Schema::create('crm_activity_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('crm_activity_id')->constrained('crm_activities')->cascadeOnDelete();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('path');
                $table->string('original_name');
                $table->string('mime', 128)->nullable();
                $table->unsignedInteger('size')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('crm_incidents')) {
            Schema::create('crm_incidents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('number');
                $table->string('type', 64);
                $table->string('priority', 16)->default('normal');
                $table->string('status', 32)->default('open');
                $table->string('subject');
                $table->text('body')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'number']);
                $table->index(['company_id', 'status', 'type']);
            });
        }

        if (! Schema::hasTable('incident_type_assignments')) {
            Schema::create('incident_type_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('incident_type', 64);
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['company_id', 'incident_type']);
            });
        }

        if (! Schema::hasTable('automation_rules')) {
            Schema::create('automation_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('trigger', 32); // low_stock, sales_threshold, production_event, time_based, custom
                $table->boolean('is_active')->default(true);
                $table->json('config')->nullable();
                $table->timestamp('last_ran_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'trigger', 'is_active']);
            });
        }

        $this->grantPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
        Schema::dropIfExists('incident_type_assignments');
        Schema::dropIfExists('crm_incidents');
        Schema::dropIfExists('crm_activity_attachments');
        Schema::dropIfExists('sale_refunds');
        Schema::dropIfExists('payment_collections');

        if (Schema::hasTable('crm_activities') && Schema::hasColumn('crm_activities', 'parent_activity_id')) {
            Schema::table('crm_activities', function (Blueprint $table) {
                $table->dropConstrainedForeignId('parent_activity_id');
            });
        }
        $this->dropColumns('crm_activities', ['recurrence', 'recurrence_until', 'reminder_sent_at']);
        $this->dropPaymentCustomList('pos_payments');
        $this->dropColumns('pos_payments', array_values(array_filter(
            $this->paymentColumnNames(),
            fn ($column) => $column !== 'change_amount'
        )));
        $this->dropColumns('pos_sale_lines', ['options_payload', 'returned_quantity']);

        foreach ([
            'delivery_platform_id',
            'predefined_ticket_id',
            'service_mode_list_id',
        ] as $fk) {
            if (Schema::hasTable('pos_sales') && Schema::hasColumn('pos_sales', $fk)) {
                Schema::table('pos_sales', function (Blueprint $table) use ($fk) {
                    $table->dropConstrainedForeignId($fk);
                });
            }
        }
        $this->dropColumns('pos_sales', [
            'payment_status_code', 'ticket_name', 'ticket_group', 'service_mode',
            'appointment_at', 'pickup_date', 'delivery_address', 'client_uuid', 'amount_refunded',
        ]);
        $this->dropPaymentCustomList('sale_payments');
        $this->dropColumns('sale_payments', $this->paymentColumnNames());
        $this->dropColumns('sales', [
            'payment_status_code', 'ticket_name', 'ticket_group', 'service_mode',
            'appointment_at', 'pickup_date', 'delivery_address',
        ]);

        foreach (['subcategory_id', 'measure_unit_id'] as $fk) {
            if (Schema::hasTable('products') && Schema::hasColumn('products', $fk)) {
                Schema::table('products', function (Blueprint $table) use ($fk) {
                    $table->dropConstrainedForeignId($fk);
                });
            }
        }

        Schema::dropIfExists('delivery_platforms');
        Schema::dropIfExists('printers');
        Schema::dropIfExists('product_option_product');
        Schema::dropIfExists('option_variants');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('subcategories');
        Schema::dropIfExists('measure_units');
        Schema::dropIfExists('custom_lists');
    }

    private function paymentWorkflowColumns(Blueprint $table, string $tableName): void
    {
        $cols = [
            'custom_list_id' => fn () => $table->foreignId('custom_list_id')->nullable()->constrained('custom_lists')->nullOnDelete(),
            'is_deferred' => fn () => $table->boolean('is_deferred')->default(false),
            'transfer_mode' => fn () => $table->string('transfer_mode', 64)->nullable(),
            'transaction_number' => fn () => $table->string('transaction_number')->nullable(),
            'piece_number' => fn () => $table->string('piece_number')->nullable(),
            'bank_name' => fn () => $table->string('bank_name')->nullable(),
            'issue_date' => fn () => $table->date('issue_date')->nullable(),
            'due_date' => fn () => $table->date('due_date')->nullable(),
            'confirmed_at' => fn () => $table->timestamp('confirmed_at')->nullable(),
            'collection_status' => fn () => $table->string('collection_status', 32)->nullable(),
            'received_amount' => fn () => $table->decimal('received_amount', 14, 2)->nullable(),
            'change_amount' => fn () => $table->decimal('change_amount', 14, 2)->nullable(),
            'scheduled_for' => fn () => $table->timestamp('scheduled_for')->nullable(),
        ];

        foreach ($cols as $name => $add) {
            if (! Schema::hasColumn($tableName, $name)) {
                $add();
            }
        }
    }

    /** @return list<string> */
    private function paymentColumnNames(): array
    {
        return [
            'is_deferred', 'transfer_mode', 'transaction_number', 'piece_number', 'bank_name',
            'issue_date', 'due_date', 'confirmed_at', 'collection_status', 'received_amount',
            'change_amount', 'scheduled_for',
        ];
    }

    private function dropPaymentCustomList(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'custom_list_id')) {
            return;
        }
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropConstrainedForeignId('custom_list_id');
        });
    }

    private function addColumns(string $tableName, \Closure $callback): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }
        Schema::table($tableName, $callback);
    }

    /** @param  list<string>  $columns */
    private function dropColumns(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }
        $existing = array_values(array_filter($columns, fn ($c) => Schema::hasColumn($tableName, $c)));
        if ($existing === []) {
            return;
        }
        Schema::table($tableName, function (Blueprint $table) use ($existing) {
            $table->dropColumn($existing);
        });
    }

    private function grantPermissions(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(RoleService::class)->syncCatalog();

        $adminKeys = [
            'settings.lists', 'settings.delivery_platforms',
            'products.options', 'products.measure_units', 'products.subcategories',
            'pos.printers', 'sales.refund', 'payments.collect',
            'crm.incidents', 'crm.automations',
        ];
        $managerKeys = $adminKeys;
        $accountantKeys = ['sales.refund', 'payments.collect', 'settings.lists'];

        $this->attachKeys(['super_admin', 'owner', 'admin'], $adminKeys);
        $this->attachKeys(['manager'], $managerKeys);
        $this->attachKeys(['accountant'], $accountantKeys);
    }

    /** @param  list<string>  $slugs @param  list<string>  $keys */
    private function attachKeys(array $slugs, array $keys): void
    {
        $ids = Permission::query()->whereIn('key', $keys)->pluck('id')->all();
        if ($ids === []) {
            return;
        }
        Role::query()->whereIn('slug', $slugs)->each(function (Role $role) use ($ids) {
            $role->permissions()->syncWithoutDetaching($ids);
        });
    }
};

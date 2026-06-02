<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('role_permissions')->truncate();

        $now = now();

        // [role, page, can_view, can_edit, can_create]
        $rows = [
            // ── staff ────────────────────────────────────────────────────
            ['staff', 'categories',      1, 1, 1],
            ['staff', 'brands',          1, 1, 1],
            ['staff', 'products',        1, 1, 1],
            ['staff', 'product_bundles', 1, 1, 1],
            ['staff', 'customers',       1, 1, 1],
            ['staff', 'sales',           1, 1, 1],
            ['staff', 'warehouse',       1, 0, 0],
            ['staff', 'warehouse_logs',  1, 0, 0],
            ['staff', 'purchases',       0, 0, 0],

            // ── sale_operator ─────────────────────────────────────────────
            ['sale_operator', 'categories',      0, 0, 0],
            ['sale_operator', 'brands',          0, 0, 0],
            ['sale_operator', 'products',        1, 0, 0],
            ['sale_operator', 'product_bundles', 1, 0, 0],
            ['sale_operator', 'customers',       1, 1, 1],
            ['sale_operator', 'sales',           1, 1, 1],
            ['sale_operator', 'warehouse',       1, 0, 0],
            ['sale_operator', 'warehouse_logs',  0, 0, 0],
            ['sale_operator', 'purchases',       0, 0, 0],

            // ── warehouse_operator ────────────────────────────────────────
            ['warehouse_operator', 'categories',      0, 0, 0],
            ['warehouse_operator', 'brands',          0, 0, 0],
            ['warehouse_operator', 'products',        0, 0, 0],
            ['warehouse_operator', 'product_bundles', 0, 0, 0],
            ['warehouse_operator', 'customers',       0, 0, 0],
            ['warehouse_operator', 'sales',           0, 0, 0],
            ['warehouse_operator', 'warehouse',       1, 1, 1],
            ['warehouse_operator', 'warehouse_logs',  0, 0, 0],
            ['warehouse_operator', 'purchases',       1, 1, 1],
        ];

        foreach ($rows as [$role, $page, $view, $edit, $create]) {
            DB::table('role_permissions')->insert([
                'role'       => $role,
                'page'       => $page,
                'can_view'   => $view,
                'can_edit'   => $edit,
                'can_create' => $create,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}

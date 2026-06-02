<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class RolePermission extends Model
{
    protected $fillable = ['role', 'page', 'can_view', 'can_edit', 'can_create'];

    protected $casts = [
        'can_view'   => 'boolean',
        'can_edit'   => 'boolean',
        'can_create' => 'boolean',
    ];

    public static function forRole(string $role): \Illuminate\Support\Collection
    {
        return Cache::remember("role_permissions_{$role}", 300, fn () =>
            static::where('role', $role)->get()->keyBy('page')
        );
    }

    public static function check(string $role, string $page, string $ability = 'can_view'): bool
    {
        if ($role === 'admin') return true;
        $permissions = static::forRole($role);
        return (bool) ($permissions->get($page)?->{$ability} ?? false);
    }

    public static function clearCache(string $role): void
    {
        Cache::forget("role_permissions_{$role}");
    }

    public static function pages(): array
    {
        return [
            'categories'      => ['label' => 'კატეგორიები',    'icon' => 'fa-tags'],
            'brands'          => ['label' => 'ბრენდები',        'icon' => 'fa-copyright'],
            'products'        => ['label' => 'პროდუქტები',      'icon' => 'fa-cubes'],
            'product_bundles' => ['label' => 'კომპლექტები',     'icon' => 'fa-object-group'],
            'customers'       => ['label' => 'მომხმარებლები',   'icon' => 'fa-users'],
            'sales'           => ['label' => 'გაყიდვები',       'icon' => 'fa-right-from-bracket'],
            'warehouse'       => ['label' => 'საწყობი',          'icon' => 'fa-warehouse'],
            'warehouse_logs'  => ['label' => 'საწყობის ლოგი',   'icon' => 'fa-history'],
            'purchases'       => ['label' => 'შესყიდვები',      'icon' => 'fa-cart-shopping'],
        ];
    }

    public static function roles(): array
    {
        return [
            'staff'              => 'Staff',
            'sale_operator'      => 'Sale Operator',
            'warehouse_operator' => 'Warehouse Operator',
        ];
    }
}

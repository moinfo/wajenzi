<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Registers the "Website Content" sidebar group (parent) and its child pages,
 * each gated by a permission whose name matches the menu name (the sidebar
 * shows a menu only if the user `can($menu->name)`).
 *
 * Admin roles in this install carry an inconsistent guard_name (some empty yet
 * holding web-guard permissions), so permissions are attached via the
 * role_has_permissions pivot directly instead of givePermissionTo(), which
 * would fail Spatie's guard validation.
 */
class LandingCmsMenuSeeder extends Seeder
{
    /** Sidebar sections under "Website Content" (display name => route). */
    private const SECTIONS = [
        ['name' => 'Portfolio', 'route' => 'landing_portfolio', 'icon' => 'si si-picture'],
        ['name' => 'Awards', 'route' => 'landing_awards', 'icon' => 'si si-trophy'],
        ['name' => 'Services', 'route' => 'landing_services', 'icon' => 'si si-wrench'],
        ['name' => 'Home Banners', 'route' => 'landing_posters', 'icon' => 'si si-picture'],
        ['name' => 'Hero Stats', 'route' => 'landing_stats', 'icon' => 'si si-graph'],
        ['name' => 'About', 'route' => 'landing_about', 'icon' => 'si si-info'],
        ['name' => 'Core Values', 'route' => 'landing_values', 'icon' => 'si si-star'],
        ['name' => 'Team', 'route' => 'landing_team', 'icon' => 'si si-users'],
    ];

    private const ADMIN_ROLES = [
        'System Administrator', 'CEO', 'Managing Director', 'General Manager', 'Content creator and IT',
    ];

    public function run(): void
    {
        // Parent group.
        $this->ensurePermission('Website Content');
        $parent = Menu::firstOrCreate(
            ['name' => 'Website Content'],
            [
                'route' => self::SECTIONS[0]['route'],
                'icon' => 'si si-globe',
                'parent_id' => null,
                'list_order' => 99,
                'status' => 'ACTIVE',
            ]
        );

        // Child pages.
        foreach (self::SECTIONS as $i => $section) {
            $this->ensurePermission($section['name']);
            Menu::firstOrCreate(
                ['route' => $section['route'], 'parent_id' => $parent->id],
                [
                    'name' => $section['name'],
                    'icon' => $section['icon'],
                    'list_order' => $i,
                    'status' => 'ACTIVE',
                ]
            );
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensurePermission(string $name): void
    {
        $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $roleIds = Role::whereIn('name', self::ADMIN_ROLES)->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permission->id,
                'role_id' => $roleId,
            ]);
        }
    }
}

<?php

namespace App\Support;

class Rbac
{
    public const PERMISSIONS = ['view', 'create', 'edit', 'delete'];

    private static function moduleMap(): array
    {
        $all = self::PERMISSIONS;
        $view = ['view'];

        return [
            'Super Admin' => [
                '*' => $all,
            ],

            'Fleet Manager' => [
                'fleet-vehicle' => $all,
                'reservation-dispatch' => $all,
                'driver-trip' => $all,
                'fuel' => $all,
                'cost-optimization' => ['view', 'create', 'edit'],
                'route-optimization' => $all,
            ],

            'Dispatcher' => [
                'reservation-dispatch' => ['view', 'create', 'edit'],
                'driver-trip' => ['view', 'create', 'edit'],
                'fuel' => ['view', 'create', 'edit'],
                'cost-optimization' => ['view', 'edit'],
            ],

            'Driver' => [
                'fleet-vehicle' => $view,
                'driver-trip' => ['view', 'edit'],
                'cost-optimization' => ['view', 'create'],
            ],
        ];
    }

    private static function routeMap(): array
    {
        $all = self::PERMISSIONS;
        $view = ['view'];

        return [
            'Super Admin' => [
                '*' => $all,
            ],

            'Fleet Manager' => [
                'fleet.vehicles' => $all,
                'logistics.maintenance' => $all,
                'logistics.depots' => $all,
                'fleet.reservations' => $all,
                'fleet.dispatch' => $all,
                'fleet.drivers' => $all,
                'fleet.trips' => $all,
                'logistics.fuel' => $all,
                'logistics.expenses' => ['view', 'edit', 'delete'],
                'logistics.routes' => $all,
                'intelligence.costs' => ['view', 'create', 'edit'],
                'intelligence.efficiency' => ['view', 'create', 'edit'],
                'intelligence.variance' => ['view', 'create', 'edit'],
                'intelligence.ml' => ['view', 'create', 'edit'],
                'intelligence.reports' => ['view', 'create', 'edit'],
                'intelligence.reports.export' => ['view', 'create', 'edit'],
            ],

            'Dispatcher' => [
                'fleet.reservations' => ['view', 'create', 'edit'],
                'fleet.dispatch' => ['view', 'create', 'edit'],
                'fleet.trips' => ['view', 'create', 'edit'],
                'logistics.fuel' => ['view', 'create', 'edit'],
                'logistics.expenses' => ['view', 'edit'],
            ],

            'Driver' => [
                'fleet.vehicles' => $view,
                'fleet.trips' => ['view', 'edit'],
                'logistics.expenses' => ['view', 'create'],
            ],
        ];
    }

    public static function allows(?string $role, string $moduleKey, string $permission = 'view'): bool
    {
        if (! $role) {
            return false;
        }

        $map = self::moduleMap()[$role] ?? null;

        if ($map === null) {
            return false;
        }

        if (isset($map['*'])) {
            return in_array($permission, $map['*'], true);
        }

        return in_array($permission, $map[$moduleKey] ?? [], true);
    }

    public static function allowsRoute(?string $role, ?string $routeName, string $permission = 'view'): bool
    {
        if (! $role || ! $routeName) {
            return false;
        }

        $map = self::routeMap()[$role] ?? null;

        if ($map === null) {
            return false;
        }

        if (isset($map['*'])) {
            return in_array($permission, $map['*'], true);
        }

        return in_array($permission, $map[$routeName] ?? [], true);
    }

    public static function visibleModules(?string $role): array
    {
        return collect(Nav::modules())
            ->filter(fn ($module) => self::visibleItems($role, $module) !== [])
            ->pluck('key')
            ->all();
    }

    public static function visibleItems(?string $role, array $module): array
    {
        return collect($module['items'])
            ->filter(fn ($item) => self::allowsRoute($role, $item['route'], 'view'))
            ->values()
            ->all();
    }

    public static function roles(): array
    {
        return array_keys(self::moduleMap());
    }

    public static function matrix(): array
    {
        $grid = [];

        foreach (Nav::modules() as $module) {
            foreach ($module['items'] as $item) {
                $row = [];
                foreach (self::roles() as $role) {
                    $row[$role] = collect(self::PERMISSIONS)
                        ->mapWithKeys(fn ($permission) => [
                            $permission => self::allowsRoute($role, $item['route'], $permission),
                        ])
                        ->all();
                }
                $grid[$item['route']] = $row;
            }
        }

        return $grid;
    }

    public static function moduleKeyForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        if ($routeName === 'intelligence.reports.export') {
            return 'route-optimization';
        }

        foreach (Nav::modules() as $module) {
            foreach ($module['items'] as $item) {
                if ($item['route'] === $routeName) {
                    return $module['key'];
                }
            }
        }

        return null;
    }
}

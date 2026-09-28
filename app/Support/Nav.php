<?php

namespace App\Support;

/**
 * Single source of truth for the Fleet & Transportation subsystem sidebar.
 */
class Nav
{
    public static function dashboard(): array
    {
        return [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'route' => 'dashboard',
        ];
    }

    public static function modules(): array
    {
        return [
            [
                'key' => 'fleet-vehicle',
                'label' => 'Fleet & Vehicle Management',
                'icon' => 'truck',
                'items' => [
                    ['label' => 'Vehicle Registry', 'route' => 'fleet.vehicles'],
                    ['label' => 'Maintenance & Repairs', 'route' => 'logistics.maintenance'],
                    ['label' => 'Depots & Yards', 'route' => 'logistics.depots'],
                ],
            ],
            [
                'key' => 'reservation-dispatch',
                'label' => 'Reservation & Dispatch',
                'icon' => 'clipboard-check',
                'items' => [
                    ['label' => 'Vehicle Reservations', 'route' => 'fleet.reservations'],
                    ['label' => 'Dispatch Board', 'route' => 'fleet.dispatch'],
                ],
            ],
            [
                'key' => 'driver-trip',
                'label' => 'Driver & Trip Performance',
                'icon' => 'route',
                'items' => [
                    ['label' => 'Driver Management', 'route' => 'fleet.drivers'],
                    ['label' => 'Trip Monitoring', 'route' => 'fleet.trips'],
                    ['label' => 'Fleet Efficiency Score', 'route' => 'intelligence.efficiency'],
                ],
            ],
            [
                'key' => 'fuel',
                'label' => 'Fuel Management',
                'icon' => 'fuel',
                'items' => [
                    ['label' => 'Fuel Transactions', 'route' => 'logistics.fuel'],
                ],
            ],
            [
                'key' => 'cost-optimization',
                'label' => 'Transport Cost Analysis',
                'icon' => 'calculator',
                'items' => [
                    ['label' => 'Transport Cost Analysis', 'route' => 'intelligence.costs'],
                    ['label' => 'Trip Expenses', 'route' => 'logistics.expenses'],
                    ['label' => 'Prediction vs Actual', 'route' => 'intelligence.variance'],
                    ['label' => 'ML Fuel Predictor', 'route' => 'intelligence.ml'],
                ],
            ],
            [
                'key' => 'route-optimization',
                'label' => 'Route Planning & Optimization',
                'icon' => 'compass',
                'items' => [
                    ['label' => 'Center Routes', 'route' => 'logistics.routes'],
                    ['label' => 'Logistics Reports', 'route' => 'intelligence.reports'],
                ],
            ],
        ];
    }

    public static function detailRouteParents(): array
    {
        return [
            'fleet.vehicles.show' => 'fleet.vehicles',
            'fleet.drivers.show' => 'fleet.drivers',
            'fleet.trips.show' => 'fleet.trips',
            'logistics.maintenance.show' => 'logistics.maintenance',
            'intelligence.ml.predict' => 'intelligence.ml',
        ];
    }

    public static function activeRoute(?string $current): ?string
    {
        if (! $current) {
            return null;
        }

        return self::detailRouteParents()[$current] ?? $current;
    }

    public static function activeModule(?string $current): ?string
    {
        $active = self::activeRoute($current);

        if (! $active) {
            return null;
        }

        foreach (self::modules() as $module) {
            foreach ($module['items'] as $item) {
                if ($item['route'] === $active) {
                    return $module['key'];
                }
            }
        }

        return null;
    }

    public static function breadcrumb(?string $current): array
    {
        $active = self::activeRoute($current);

        foreach (self::modules() as $module) {
            foreach ($module['items'] as $item) {
                if ($item['route'] === $active) {
                    return [
                        ['label' => $module['label'], 'route' => $module['items'][0]['route']],
                        ['label' => $item['label'], 'route' => $item['route']],
                    ];
                }
            }
        }

        return [];
    }
}

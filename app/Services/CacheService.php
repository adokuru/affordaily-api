<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Cache keys constants
     */
    const ROOM_OCCUPANCY_STATS = 'room_occupancy_stats';
    const AVAILABLE_ROOMS = 'available_rooms';
    const ROOM_RATES = 'room_rates';
    const DASHBOARD_STATS = 'dashboard_stats';

    /**
     * Cache duration in minutes
     */
    const CACHE_DURATION = 15;

    /**
     * Get room occupancy stats with caching.
     *
     * @param callable $callback
     * @return mixed
     */
    public static function rememberRoomOccupancyStats(callable $callback)
    {
        return Cache::remember(self::ROOM_OCCUPANCY_STATS, self::CACHE_DURATION, $callback);
    }

    /**
     * Get available rooms with caching.
     *
     * @param callable $callback
     * @return mixed
     */
    public static function rememberAvailableRooms(callable $callback)
    {
        return Cache::remember(self::AVAILABLE_ROOMS, self::CACHE_DURATION, $callback);
    }

    /**
     * Get room rates with caching.
     *
     * @param callable $callback
     * @return mixed
     */
    public static function rememberRoomRates(callable $callback)
    {
        return Cache::remember(self::ROOM_RATES, self::CACHE_DURATION, $callback);
    }

    /**
     * Get dashboard stats with caching.
     *
     * @param callable $callback
     * @return mixed
     */
    public static function rememberDashboardStats(callable $callback)
    {
        return Cache::remember(self::DASHBOARD_STATS, self::CACHE_DURATION, $callback);
    }

    /**
     * Clear room-related cache.
     */
    public static function clearRoomCache()
    {
        Cache::forget(self::ROOM_OCCUPANCY_STATS);
        Cache::forget(self::AVAILABLE_ROOMS);
        Cache::forget(self::DASHBOARD_STATS);
    }

    /**
     * Clear all cache.
     */
    public static function clearAllCache()
    {
        Cache::forget(self::ROOM_OCCUPANCY_STATS);
        Cache::forget(self::AVAILABLE_ROOMS);
        Cache::forget(self::ROOM_RATES);
        Cache::forget(self::DASHBOARD_STATS);
    }
}

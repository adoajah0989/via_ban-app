<?php

namespace App\Services;

use App\Models\tb_telegram_session;

class TelegramSessionService
{
    /**
     * Cache sederhana per-process untuk mengurangi query berulang
     * ke tabel tb_telegram_session dalam satu siklus request.
     */
    protected static array $cache = [];

    protected static function cacheKey(int $telegramUserId): string
    {
        return (string) $telegramUserId;
    }

    public static function get(?int $telegramUserId): ?tb_telegram_session
    {
        if (! $telegramUserId) {
            return null;
        }

        $key = self::cacheKey($telegramUserId);
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $session = tb_telegram_session::query()
            ->where('telegram_user_id', $telegramUserId)
            ->first();

        self::$cache[$key] = $session;

        return $session;
    }

    public static function getState(int $telegramUserId): ?string
    {
        $session = self::get($telegramUserId);

        return $session?->state;
    }

    public static function getData(int $telegramUserId): array
    {
        $session = self::get($telegramUserId);

        return $session?->data ?? [];
    }

    public static function set(int $telegramUserId, string $state, array $data = []): tb_telegram_session
    {
        $session = tb_telegram_session::updateOrCreate(
            ['telegram_user_id' => $telegramUserId],
            ['state' => $state, 'data' => $data]
        );

        self::$cache[self::cacheKey($telegramUserId)] = $session;

        return $session;
    }

    /**
     * Ubah hanya kolom state tanpa menyentuh data yang sudah ada.
     * Dipakai untuk menghindari pola getData() lalu set(..., getData()).
     */
    public static function setState(int $telegramUserId, string $state): tb_telegram_session
    {
        $session = tb_telegram_session::updateOrCreate(
            ['telegram_user_id' => $telegramUserId],
            ['state' => $state]
        );

        $key = self::cacheKey($telegramUserId);
        if (isset(self::$cache[$key]) && self::$cache[$key]) {
            $cached = self::$cache[$key];
            $cached->state = $session->state;
            self::$cache[$key] = $cached;

            return $cached;
        }

        self::$cache[$key] = $session;

        return $session;
    }

    public static function update(int $telegramUserId, array $data): void
    {
        $session = self::get($telegramUserId);
        if (! $session) {
            return;
        }

        $session->data = array_merge($session->data ?? [], $data);
        $session->save();

         self::$cache[self::cacheKey($telegramUserId)] = $session;
    }

    public static function clear(int $telegramUserId): void
    {
        tb_telegram_session::query()
            ->where('telegram_user_id', $telegramUserId)
            ->delete();

        unset(self::$cache[self::cacheKey($telegramUserId)]);
    }
}

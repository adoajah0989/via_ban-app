<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telegram:setup-webhook', function () {
    $this->info('Setting up Telegram webhook...');
    $this->newLine();

    // Validate environment variables
    $token = config('services.telegram.bot_token');
    $appUrl = config('app.url');

    if (! $token) {
        $this->error('❌ TELEGRAM_BOT_TOKEN not configured in .env');
        $this->warn('Please add TELEGRAM_BOT_TOKEN to your .env file');
        return 1;
    }

    if (! $appUrl) {
        $this->error('❌ APP_URL not configured in .env');
        $this->warn('Please add APP_URL to your .env file');
        return 1;
    }

    // Build webhook URL
    $webhookUrl = rtrim($appUrl, '/') . '/telegram/webhook';
    
    // Validate HTTPS requirement (except for localhost/ngrok)
    $isLocalhost = str_contains($webhookUrl, 'localhost') || str_contains($webhookUrl, '127.0.0.1');
    $isNgrok = str_contains($webhookUrl, 'ngrok');
    
    if (!str_starts_with($webhookUrl, 'https://') && !$isLocalhost && !$isNgrok) {
        $this->error('❌ Telegram requires HTTPS for webhooks in production');
        $this->warn('Your APP_URL: ' . $appUrl);
        $this->warn('For local testing, use ngrok or set APP_URL to localhost');
        return 1;
    }

    if ($isLocalhost || $isNgrok) {
        $this->warn('⚠️  Development mode detected (localhost/ngrok)');
    }

    $this->line('Webhook URL: ' . $webhookUrl);
    $this->newLine();

    try {
        // Call Telegram setWebhook API
        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $webhookUrl,
            'allowed_updates' => ['message', 'edited_message', 'callback_query'],
        ]);

        $result = $response->json();

        if ($response->successful() && ($result['ok'] ?? false)) {
            $this->info('✓ Webhook configured successfully!');
            $this->newLine();

            // Get webhook info to confirm
            $infoResponse = Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
            $info = $infoResponse->json();

            if ($infoResponse->successful() && isset($info['result'])) {
                $webhookInfo = $info['result'];
                
                $this->line('Current webhook information:');
                $this->table(
                    ['Property', 'Value'],
                    [
                        ['URL', $webhookInfo['url'] ?? 'N/A'],
                        ['Pending Updates', $webhookInfo['pending_update_count'] ?? 0],
                        ['Last Error', $webhookInfo['last_error_message'] ?? 'None'],
                        ['Max Connections', $webhookInfo['max_connections'] ?? 40],
                    ]
                );
            }

            $this->newLine();
            $this->info('🎉 Webhook setup complete!');
            $this->line('Send a message to your bot to test the connection.');
            
            return 0;
        } else {
            $this->error('❌ Failed to setup webhook');
            $this->warn('Telegram API response: ' . ($result['description'] ?? 'Unknown error'));
            return 1;
        }
    } catch (\Exception $e) {
        $this->error('❌ Error connecting to Telegram API');
        $this->warn('Error: ' . $e->getMessage());
        return 1;
    }
})->purpose('Configure Telegram webhook automatically from .env settings');

<?php

declare(strict_types = 1);

namespace App\Listeners;

use Exception;
use Illuminate\Auth\Events\{Login, Logout};
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class UserEventSubscriber
{
    /**
     * Handle user login events.
     */
    public function handleUserLogin(Login $event): void
    {
        try {
            $user = $event->user;
        } catch (Exception $exception) {
            Log::error($exception);
        }

        Log::info('Login Success: ' . $user->phone . ', IP: ' . request()->getClientIp() . ', Host: ' . $_SERVER['SERVER_NAME'] . ' / ' . $_SERVER['HTTP_HOST']);
    }

    /**
     * Handle user logout events.
     */
    public function handleUserLogout(Logout $event): void
    {
        $user = $event->user;

        Log::info('Logout Success. ' . $user->phone);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class  => 'handleUserLogin',
            Logout::class => 'handleUserLogout',
        ];
    }
}

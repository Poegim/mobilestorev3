<?php

namespace App\Livewire\Concerns;

trait DeniesAccess
{
    protected function denyIf(bool $condition, string $message = 'Brak uprawnień do tego zasobu.', ?string $route = null): void
    {
        if (! $condition) {
            return;
        }

        $this->denyAccess($message, $route);
    }

    protected function denyUnless(bool $condition, string $message = 'Brak uprawnień do tego zasobu.', ?string $route = null): void
    {
        $this->denyIf(! $condition, $message, $route);
    }

    protected function denyAccess(string $message = 'Brak uprawnień do tego zasobu.', ?string $route = null): void
    {
        session()->flash('toast', [
            'duration' => false,
            'heading' => 'Brak dostępu',
            'text'    => $message,
            'variant' => 'danger',
        ]);

        // This throws internally and halts the Livewire lifecycle — no abort() needed
        $this->redirectRoute(
            name: $route ?? 'dashboard',
            navigate: true,
        );
    }
}
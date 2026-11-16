<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Filament\Resources\Subscriptions\SubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Langganan Pro resource (SubscriptionResource).
 */
class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;
}

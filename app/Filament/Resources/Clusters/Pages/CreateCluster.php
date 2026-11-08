<?php

namespace App\Filament\Resources\Clusters\Pages;

use App\Filament\Resources\Clusters\ClusterResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page of the Cluster bidang resource (ClusterResource).
 */
class CreateCluster extends CreateRecord
{
    protected static string $resource = ClusterResource::class;
}

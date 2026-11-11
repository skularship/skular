<?php

namespace App\Contracts;

use App\Data\TourStepData;
use App\Enums\ProductTour;
use App\Models\User;

/**
 * Defines the onboarding product tours of the mentee journey and remembers
 * which ones a mentee already finished or skipped.
 */
interface ProductTourServiceInterface
{
    /**
     * Get the coach-mark steps of a tour, in order.
     *
     * @param  ProductTour  $tour  The tour.
     * @return array<int, TourStepData>
     */
    public function steps(ProductTour $tour): array;

    /**
     * Determine whether the tour should open by itself for this mentee.
     *
     * True until the mentee finishes or skips it once.
     *
     * @param  User  $user  The signed-in mentee.
     * @param  ProductTour  $tour  The tour on the current page.
     */
    public function shouldAutoStart(User $user, ProductTour $tour): bool;

    /**
     * Remember that the mentee finished or skipped a tour.
     *
     * @param  User  $user  The signed-in mentee.
     * @param  ProductTour  $tour  The finished tour.
     */
    public function markCompleted(User $user, ProductTour $tour): void;

    /**
     * Forget a finished tour so it opens again on its page ("Ulangi tur").
     *
     * @param  User  $user  The signed-in mentee.
     * @param  ProductTour  $tour  The tour to replay.
     */
    public function reset(User $user, ProductTour $tour): void;

    /**
     * Determine whether the mentee finished or skipped a tour.
     *
     * @param  User  $user  The signed-in mentee.
     * @param  ProductTour  $tour  The tour.
     */
    public function isCompleted(User $user, ProductTour $tour): bool;
}

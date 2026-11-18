<?php

namespace App\Http\Responses;

use App\Contracts\ActivityTrackerInterface;
use App\Enums\ActivityName;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify register response: every new mentee starts on the scholarship catalog
 * (Info Beasiswa); the "Cari beasiswa yang cocok untukku" card there leads to profiling.
 */
class RegisterResponse implements RegisterResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * The parameter stays untyped because the Fortify contract declares it
     * without a type; adding one would break the interface signature.
     *
     * @param  Request  $request  The authenticated request.
     * @return Response A redirect to the intended page.
     */
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $user = $request->user();

        if ($user instanceof User) {
            app(ActivityTrackerInterface::class)->track(ActivityName::Registered, user: $user);
        }

        return redirect()->intended(route('scholarships.index', absolute: false));
    }
}

<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify login response: mentees without a completed profile go to the profiling wizard, everyone else to the dashboard.
 */
class LoginResponse implements LoginResponseContract
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

        $destination = $user instanceof User && ! $user->hasCompletedProfile()
            ? route('profiling', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($destination);
    }
}

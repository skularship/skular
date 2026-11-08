<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify register response: every new mentee starts with the profiling wizard.
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
        return redirect()->intended(route('profiling', absolute: false));
    }
}

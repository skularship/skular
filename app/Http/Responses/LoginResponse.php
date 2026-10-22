<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
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

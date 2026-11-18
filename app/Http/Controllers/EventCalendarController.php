<?php

namespace App\Http\Controllers;

use App\Contracts\EventServiceInterface;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * "Tambah ke kalender" (route events.calendar): downloads the event as an
 * .ics file. Only for mentees who may see the event; the file holds the event
 * page URL, never the meeting link.
 */
class EventCalendarController extends Controller
{
    /**
     * Return the .ics file of one event.
     *
     * @param  Request  $request  The authenticated request.
     * @param  Event  $event  Route-bound event (by slug).
     * @return Response The calendar file download.
     */
    public function __invoke(Request $request, Event $event, EventServiceInterface $events): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $events->canSee($user, $event), 404);

        return response($events->calendarFile($event), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$event->slug.'.ics"',
        ]);
    }
}

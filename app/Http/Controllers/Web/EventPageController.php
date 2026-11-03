<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventPageController extends Controller
{
    public function show(Request $request, EventController $eventApi): View|RedirectResponse
    {
        $data = $this->findEventData($request, $eventApi);

        if ($data === null) {
            return redirect()->route('events.index');
        }

        return view('events.show', ['eventData' => $data]);
    }

    public function formWarga(Request $request, EventController $eventApi): View|RedirectResponse
    {
        return $this->renderBookingForm($request, $eventApi, 'events.event-tiket');
    }

    public function formLuar(Request $request, EventController $eventApi): View|RedirectResponse
    {
        return $this->renderBookingForm($request, $eventApi, 'events.event-tiket-luar');
    }

    private function renderBookingForm(
        Request $request,
        EventController $eventApi,
        string $view
    ): View|RedirectResponse {
        $data = $this->findEventData($request, $eventApi);

        if ($data === null) {
            return redirect()->route('events.index');
        }

        return view($view, ['event' => $data]);
    }

    private function findEventData(Request $request, EventController $eventApi): ?array
    {
        $eventId = $request->query('event');

        if ($eventId === null || $eventId === '') {
            return null;
        }

        $request->validate([
            'event' => ['required', 'uuid'],
        ]);

        $event = Event::query()->findOrFail($eventId);
        $response = $eventApi->show($event);

        if ($response->getStatusCode() !== 200) {
            abort(404, 'Event tidak tersedia.');
        }

        $payload = $response->getData(true);

        if (!($payload['success'] ?? false) || !is_array($payload['data'] ?? null)) {
            abort(404, 'Detail event tidak tersedia.');
        }

        return $payload['data'];
    }
}

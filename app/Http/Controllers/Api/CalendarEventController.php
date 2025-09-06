<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalendarEventController extends Controller
{
    // FullCalendar fetch: ?start=ISO&end=ISO (görünür aralık)
    public function index(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end'   => 'required|date|after:start',
        ]);

        $start = $request->date('start');
        $end   = $request->date('end');

        // Aralığa çakışan eventleri getir (overlap)
        $events = CalendarEvent::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start', [$start, $end])
                  ->orWhereBetween('end', [$start, $end])
                  ->orWhere(function ($qq) use ($start, $end) {
                      $qq->where('start', '<=', $start)->where('end', '>=', $end);
                  })
                  ->orWhere(function ($qq) use ($start) {
                      $qq->where('start', '<=', $start)->whereNull('end');
                  });
            })
            ->orderBy('start')
            ->get();

        // FullCalendar EventInput formatına map et
        return $events->map(function ($e) {
            return [
                'id'    => (string)$e->id,
                'title' => $e->title,
                'start' => $e->start->toIso8601String(),
                'end'   => $e->end?->toIso8601String(),
                'allDay'=> $e->all_day,
                'extendedProps' => ['calendar' => $e->level],
            ];
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end'   => 'nullable|date|after_or_equal:start',
            'allDay'=> 'boolean',
            'level' => ['required', Rule::in(['Danger','Success','Primary','Warning'])],
        ]);

        $event = CalendarEvent::create([
            ...$data,
            'user_id' => $request->user()->id ?? null,
        ]);

        return response()->json(['id' => $event->id], 201);
    }

    public function update(Request $request, CalendarEvent $calendar_event)
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'start' => 'sometimes|required|date',
            'end'   => 'nullable|date|after_or_equal:start',
            'allDay'=> 'boolean',
            'level' => ['sometimes','required', Rule::in(['Danger','Success','Primary','Warning'])],
        ]);

        $calendar_event->update($data);
        return response()->noContent();
    }

    public function destroy(CalendarEvent $calendar_event)
    {
        $calendar_event->delete();
        return response()->noContent();
    }
}
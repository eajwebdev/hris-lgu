<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;

/**
 * Dashboard calendar. Event listing/creation lives in EventController; the only
 * thing left here is the inline "edit event" modal on the dashboard.
 *
 * The old eventRead/eventCreate/eventEdit actions were removed: they queried an
 * events.user_id column that does not exist and rendered calendar/* views that
 * were never in the project.
 */
class CalendarController extends Controller
{
    public function eventUpdate(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'title' => 'required',
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        $event = Event::find($request->input('id'));

        if (!$event) {
            return redirect()->back()->with('error', 'Event not found.');
        }

        $event->update([
            'title' => $request->input('title'),
            'start' => $request->input('start'),
            'end' => $request->input('end'),
        ]);

        return redirect()->back()->with('success', 'Event updated successfully!');
    }

    public function eventDelete($id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['status' => 404, 'message' => 'Event not found'], 404);
        }

        $event->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Deleted Successfully',
        ]);
    }
}

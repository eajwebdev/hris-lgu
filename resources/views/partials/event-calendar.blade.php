{{--
    The events calendar, for pages on layouts/app. Include it once in the page
    (inside a section), put <div id="calendar"> where it goes, and call

        hrisEventCalendar(document.getElementById('calendar'), { ... })

    The call draws the month, loads every active event from eventJson, and
    keeps the calendar fitted to the window and to its own card. With no
    options it is read-only (the dashboard); the Events page passes
    FullCalendar options on top to make it editable. It returns the calendar.

    Each event carries, in extendedProps: eventId, venue, orgDept, empStatus,
    bgColor (the stored colour value, see event_palette()) and timeLabel.
--}}
@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('template/plugins/fullcalendar/main.min.css') }}">
    @endpush

    @push('scripts')
    <script src="{{ asset('template/plugins/fullcalendar/main.min.js') }}"></script>
    <script>
        // stored value => [name, colour]
        window.hrisEventPalette = @json(event_palette());

        window.hrisEventColor = function (stored) {
            return (window.hrisEventPalette[stored] || [null, '#0073b7'])[1];
        };

        window.hrisEventCalendar = function (calendarEl, extra) {
            // Fit the calendar within the viewport so the whole month is visible
            // with minimal page scrolling; rows compress and overflow scrolls internally.
            function fittedHeight() { return Math.max(480, window.innerHeight - 170); }

            function span(className, text) {
                var el = document.createElement('span');
                el.className = className;
                if (text) { el.textContent = text; }
                return el;
            }

            var options = {
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                navLinks: true,
                nowIndicator: true,
                dayMaxEvents: true,
                height: fittedHeight(),
                expandRows: true,

                events: function (fetchInfo, successCallback, failureCallback) {
                    fetch("{{ route('eventJson') }}", { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            successCallback(data.map(function (event) {
                                var start = new Date(event.start);
                                var hours = start.getHours();
                                var timeLabel = (hours % 12 || 12) + ':' + String(start.getMinutes()).padStart(2, '0') + (hours >= 12 ? ' PM' : ' AM');
                                var color = window.hrisEventColor(event.bg_color);

                                return {
                                    id: event.id,
                                    title: event.title,
                                    start: event.start,
                                    end: event.end || null,
                                    allDay: false,
                                    backgroundColor: color,
                                    borderColor: color,
                                    extendedProps: {
                                        eventId: event.id,
                                        venue: event.venue,
                                        orgDept: event.org_dept,
                                        empStatus: event.emp_status,
                                        bgColor: event.bg_color,
                                        timeLabel: event.end ? null : timeLabel
                                    }
                                };
                            }));
                        })
                        .catch(function (error) {
                            console.error('Error loading events:', error);
                            failureCallback(error);
                        });
                },

                // Built as nodes, not an HTML string: titles are typed in by people.
                eventContent: function (arg) {
                    var swatch = span('fc-event-swatch');
                    swatch.style.backgroundColor = arg.event.backgroundColor || '#0073b7';

                    var nodes = [swatch];
                    if (arg.event.extendedProps.timeLabel) {
                        nodes.push(span('fc-event-when', arg.event.extendedProps.timeLabel));
                    }
                    nodes.push(span('fc-event-what', arg.event.title));

                    return { domNodes: nodes };
                },

                eventClick: function (info) { info.jsEvent.preventDefault(); }
            };

            Object.keys(extra || {}).forEach(function (key) { options[key] = extra[key]; });

            var calendar = new FullCalendar.Calendar(calendarEl, options);
            calendar.render();

            // Keep the calendar fitted to the window as it resizes...
            window.addEventListener('resize', function () {
                calendar.setOption('height', fittedHeight());
            });

            // ...and to its own card: collapsing the sidebar widens the page
            // without a window resize, which FullCalendar does not notice.
            if (window.ResizeObserver) {
                new ResizeObserver(function () { calendar.updateSize(); }).observe(calendarEl.parentElement);
            }

            return calendar;
        };
    </script>
    @endpush
@endonce

<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\PositionDescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DBM-CSC Form No. 1 (Revised 2017) — Position Description Form.
 *
 * A description belongs to a plantilla item and is reused by every posting of
 * that item, so it is maintained here as a standing document rather than being
 * re-keyed with each vacancy.
 */
class PositionDescriptionController extends Controller
{
    /** Section 17 contact groups, in the order the form prints them. */
    public const INTERNAL_CONTACTS = [
        'executive'      => 'Executive / Managerial',
        'supervisors'    => 'Supervisors',
        'non_supervisors' => 'Non-Supervisors',
        'staff'          => 'Staff',
    ];

    public const EXTERNAL_CONTACTS = [
        'general_public' => 'General Public',
        'other_agencies' => 'Other Agencies',
        'others'         => 'Others (Please Specify)',
    ];

    public const FREQUENCIES = ['occasional' => 'Occasional', 'frequent' => 'Frequent'];

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->guard('web')->check(), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();
        $guard = 'web';

        $search = trim((string) $request->get('q', ''));

        $descriptions = PositionDescription::withCount(['duties', 'postings'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('position_title', 'like', "%{$search}%")
                      ->orWhere('item_number', 'like', "%{$search}%")
                      ->orWhere('bureau_office', 'like', "%{$search}%");
                });
            })
            ->orderBy('position_title')
            ->paginate(15)
            ->withQueryString();

        return view('positions.index', compact('guard', 'descriptions', 'search'));
    }

    public function create()
    {
        $this->authorizeAdmin();

        $description = new PositionDescription([
            'department_agency'  => 'Municipality of Mabinay',
            'lgu_unit_and_class' => 'Municipality of Mabinay, Negros Oriental',
            'status'             => 'active',
        ]);

        return view('positions.form', $this->formData($description));
    }

    public function edit($id)
    {
        $this->authorizeAdmin();

        $description = PositionDescription::with(['duties', 'supervised'])->findOrFail($id);

        return view('positions.form', $this->formData($description));
    }

    private function formData(PositionDescription $description): array
    {
        return [
            'guard'       => 'web',
            'description' => $description,
            'offices'     => Office::orderBy('office_name')->get(),
            'internal'    => self::INTERNAL_CONTACTS,
            'external'    => self::EXTERNAL_CONTACTS,
            'frequencies' => self::FREQUENCIES,
        ];
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $description = new PositionDescription();
        $description->created_by = auth()->guard('web')->id();
        $this->persist($request, $description);

        return redirect()->route('positionDescriptionEdit', $description->id)
            ->with('success', 'Position Description created.');
    }

    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        $description = PositionDescription::findOrFail($id);
        $this->persist($request, $description);

        return redirect()->route('positionDescriptionEdit', $description->id)
            ->with('success', 'Position Description saved.');
    }

    /**
     * Write the form and its two repeating sections.
     *
     * The duty rows and supervised rows are replaced wholesale inside a
     * transaction: the form posts the complete list every time, so reconciling
     * row by row would only invent ways for the two to disagree.
     */
    private function persist(Request $request, PositionDescription $description): void
    {
        $data = $request->validate([
            'position_title'      => 'required|string|max:255',
            'parenthetical_title' => 'nullable|string|max:255',
            'item_number'         => 'nullable|string|max:100',
            'salary_grade'        => 'nullable|string|max:50',
            'lgu_unit_and_class'  => 'nullable|string|max:255',
            'department_agency'   => 'nullable|string|max:255',
            'bureau_office'       => 'nullable|string|max:255',
            'division_branch'     => 'nullable|string|max:255',
            'workstation'         => 'nullable|string|max:255',
            'present_approp_act'  => 'nullable|string|max:255',
            'previous_approp_act' => 'nullable|string|max:255',
            'salary_authorized'   => 'nullable|string|max:255',
            'other_compensation'  => 'nullable|string|max:255',
            'immediate_supervisor_title'   => 'nullable|string|max:255',
            'next_higher_supervisor_title' => 'nullable|string|max:255',
            'equipment_used'           => 'nullable|string',
            'unit_general_function'    => 'nullable|string',
            'position_general_function' => 'nullable|string',
            'qs_education'   => 'nullable|string',
            'qs_experience'  => 'nullable|string',
            'qs_training'    => 'nullable|string',
            'qs_eligibility' => 'nullable|string',
            'status'         => 'nullable|in:active,archived',

            'contacts'            => 'nullable|array',
            'working_conditions'  => 'nullable|array',

            'core_competencies'         => 'nullable|array',
            'core_competencies.*.name'  => 'nullable|string|max:255',
            'core_competencies.*.level' => 'nullable|string|max:100',
            'leadership_competencies'         => 'nullable|array',
            'leadership_competencies.*.name'  => 'nullable|string|max:255',
            'leadership_competencies.*.level' => 'nullable|string|max:100',

            'duties'                    => 'nullable|array',
            'duties.*.percentage'       => 'nullable|numeric|min:0|max:100',
            'duties.*.duty'             => 'nullable|string',
            'duties.*.competency_level' => 'nullable|string|max:100',

            'supervised'                 => 'nullable|array',
            'supervised.*.position_title' => 'nullable|string|max:255',
            'supervised.*.item_number'    => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($data, $request, $description) {
            $description->fill($data);
            $description->contacts           = $request->input('contacts', []);
            $description->working_conditions = $request->input('working_conditions', []);
            $description->core_competencies       = $this->cleanCompetencies($request->input('core_competencies', []));
            $description->leadership_competencies = $this->cleanCompetencies($request->input('leadership_competencies', []));
            $description->status     = $data['status'] ?? 'active';
            $description->updated_by = auth()->guard('web')->id();
            $description->save();

            $description->duties()->delete();
            foreach (array_values($request->input('duties', [])) as $i => $row) {
                if (trim((string) ($row['duty'] ?? '')) === '') {
                    continue;
                }
                $description->duties()->create([
                    'percentage'       => $row['percentage'] ?? 0,
                    'duty'             => $row['duty'],
                    'competency_level' => $row['competency_level'] ?? null,
                    'sort_order'       => $i,
                ]);
            }

            $description->supervised()->delete();
            foreach (array_values($request->input('supervised', [])) as $i => $row) {
                if (trim((string) ($row['position_title'] ?? '')) === '') {
                    continue;
                }
                $description->supervised()->create([
                    'position_title' => $row['position_title'],
                    'item_number'    => $row['item_number'] ?? null,
                    'sort_order'     => $i,
                ]);
            }
        });
    }

    /** Drop competency rows the user left blank. */
    private function cleanCompetencies(array $rows): array
    {
        return array_values(array_filter(array_map(function ($row) {
            $name = trim((string) ($row['name'] ?? ''));

            return $name === '' ? null : ['name' => $name, 'level' => $row['level'] ?? null];
        }, $rows)));
    }

    public function destroy($id)
    {
        $this->authorizeAdmin();

        $description = PositionDescription::withCount('postings')->findOrFail($id);

        // A description referenced by a posting is archived, not deleted — the
        // posting's qualification standards are read through it.
        if ($description->postings_count > 0) {
            $description->update(['status' => 'archived']);

            return redirect()->route('positionDescriptionList')
                ->with('success', "\"{$description->position_title}\" is used by {$description->postings_count} posting(s), so it was archived instead of deleted.");
        }

        $description->delete();

        return redirect()->route('positionDescriptionList')->with('success', 'Position Description deleted.');
    }

    /** The printable DBM-CSC Form No. 1. */
    public function print($id)
    {
        $this->authorizeAdmin();

        $description = PositionDescription::with(['duties', 'supervised'])->findOrFail($id);

        return view('positions.print', [
            'description' => $description,
            'internal'    => self::INTERNAL_CONTACTS,
            'external'    => self::EXTERNAL_CONTACTS,
        ]);
    }
}

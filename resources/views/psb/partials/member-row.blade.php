{{-- One row of the Selection Board form (psb/members).
       $i  its place in the posted members[] array ("__i__" in the pattern the
           Add member button copies)
       $m  the saved member, or null for a blank row --}}
<tr class="border-b border-line last:border-b-0">
    <td class="px-2 py-2.5 pl-5">
        <input type="hidden" name="members[{{ $i }}][id]" value="{{ $m->id ?? '' }}">
        <input type="text" name="members[{{ $i }}][name]" value="{{ $m->name ?? '' }}" aria-label="Printed name" class="{{ $field }}">
    </td>
    <td class="px-2 py-2.5">
        <input type="text" name="members[{{ $i }}][credentials]" value="{{ $m->credentials ?? '' }}" placeholder="RN, JD" aria-label="Credentials" class="{{ $field }}">
    </td>
    <td class="px-2 py-2.5">
        <select name="members[{{ $i }}][role]" aria-label="Role" class="{{ $field }} pr-8">
            @foreach($roles as $role)
                <option value="{{ $role }}" @selected(($m->role ?? 'Member') === $role)>{{ $role }}</option>
            @endforeach
        </select>
    </td>
    <td class="px-2 py-2.5">
        <select name="members[{{ $i }}][employee_id]" aria-label="Employee record" class="{{ $field }} pr-8">
            <option value="">Not linked</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}" @selected($m && $m->employee_id == $e->id)>{{ ucfirst($e->lname) }}, {{ ucfirst($e->fname) }}</option>
            @endforeach
        </select>
    </td>
    <td class="px-2 py-2.5 text-center">
        <input type="hidden" name="members[{{ $i }}][active]" value="0">
        <input type="checkbox" name="members[{{ $i }}][active]" value="1" aria-label="Active" class="size-4 accent-forest-600" @checked($m ? $m->active : true)>
    </td>
    <td class="px-2 py-2.5 pr-5 text-right">
        <button type="button" data-remove-member title="Remove from the board"
                class="grid size-9 cursor-pointer place-items-center rounded-lg text-ink/50 transition-colors hover:bg-red-50 hover:text-red-700 focus-visible:outline-2 focus-visible:outline-sun-500 dark:hover:bg-red-500/10 dark:hover:text-red-300">
            <i class="fas fa-xmark"></i><span class="sr-only">Remove</span>
        </button>
    </td>
</tr>

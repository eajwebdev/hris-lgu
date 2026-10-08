{{--
    Employee picker for the DTR and Logs forms.

    Drawn only when the signed-in account may look at someone else's records
    ($acctstat, from DtrController::dtrScope). Without it the form posts no
    employee and the controller falls back to the account's own emp_ID — and
    it refuses anything outside the scope either way, so this is convenience,
    not the access check.

      $selected   emp_ID to preselect, or null
--}}
@if($acctstat == 1)
    <div class="w-full sm:w-64">
        <label for="employee" class="block text-xs font-medium text-ink/60">Employee</label>
        <select name="employee" id="employee" required
                class="mt-1 block h-10 w-full rounded-xl border border-line bg-paper pr-8 pl-3 text-ink outline-none transition-shadow focus:border-forest-600 focus:bg-surface focus:ring-4 focus:ring-forest-600/15">
            <option value="" disabled @if(!$selected) selected @endif>Select</option>
            {{-- Surname first and in order, so typing one in the open list jumps to it. --}}
            @foreach($employeeall->sortBy(fn ($emp) => strtolower($emp->lname . ' ' . $emp->fname)) as $emp)
                <option value="{{ $emp->emp_ID }}" @if($selected && $emp->emp_ID == $selected) selected @endif>
                    {{ $emp->lname }}, {{ trim($emp->prefix . ' ' . $emp->fname) }} {{ isset($emp->mname) ? substr($emp->mname, 0, 1) . '.' : '' }}
                </option>
            @endforeach
        </select>
    </div>
@endif
<input type="hidden" name="acctstat" value="{{ $acctstat }}">

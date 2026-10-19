<div class="mt-4">
    <button type="button" onclick="document.getElementById('addParentModal').classList.remove('hidden')"
        class="inline-flex items-center rounded-md border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">
        Add Parent
    </button>
    <p class="mt-1 text-sm text-gray-500">The child details stay on this form. Link the parent before you save the child.</p>
</div>

<div id="addParentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <form id="addParentForm" class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800" onsubmit="return submitAddParent(event)">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Add Parent</h3>
        <p id="addParentMessage" class="mt-2 hidden text-sm"></p>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <label class="text-sm text-gray-700 dark:text-gray-300">First name
                <input name="first_name" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </label>
            <label class="text-sm text-gray-700 dark:text-gray-300">Last name
                <input name="last_name" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </label>
            <label class="text-sm text-gray-700 dark:text-gray-300 md:col-span-2">Email
                <input type="email" name="email" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </label>
            <label class="text-sm text-gray-700 dark:text-gray-300">Phone
                <input name="phone" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </label>
            <label class="text-sm text-gray-700 dark:text-gray-300">Relationship
                <select name="relationship" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="mother">Mother</option>
                    <option value="father">Father</option>
                    <option value="guardian">Guardian</option>
                    <option value="other">Other</option>
                </select>
            </label>
        </div>
        <input type="hidden" name="status" value="active">
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="closeAddParent()" class="rounded-md border border-gray-300 px-4 py-2 text-sm">Cancel</button>
            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Save parent</button>
        </div>
    </form>
</div>

<script>
    function closeAddParent() {
        document.getElementById('addParentModal').classList.add('hidden');
    }

    async function submitAddParent(event) {
        event.preventDefault();
        const form = document.getElementById('addParentForm');
        const message = document.getElementById('addParentMessage');
        const token = document.querySelector('input[name="_token"]')?.value || '';
        const body = new FormData(form);
        const response = await fetch(@json(route('school-management.parents.store')), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body,
        });
        const payload = await response.json().catch(() => ({}));
        message.classList.remove('hidden');
        if (!response.ok) {
            const first = payload.errors ? Object.values(payload.errors).flat()[0] : payload.message;
            message.className = 'mt-2 text-sm text-red-600';
            message.textContent = first || 'Unable to save this parent.';
            return false;
        }
        const select = document.getElementById('parent_guardian_id');
        if (select && payload.parent) {
            let option = Array.from(select.options).find((item) => item.value === String(payload.parent.id));
            if (!option) {
                option = new Option(payload.parent.name, payload.parent.id, true, true);
                select.add(option);
            }
            select.value = String(payload.parent.id);
        }
        message.className = 'mt-2 text-sm text-emerald-700';
        message.textContent = payload.message || 'Parent saved and linked to this child.';
        form.reset();
        setTimeout(closeAddParent, 900);
        return false;
    }
</script>

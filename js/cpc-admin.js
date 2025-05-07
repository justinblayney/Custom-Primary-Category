document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.getElementById('cpc_primary_category_select');
    const checklist = document.getElementById('categorychecklist');

    if (!dropdown || !checklist) return;

    function updateDropdown() {
        const selectedVal = dropdown.value;
        const checkedBoxes = checklist.querySelectorAll('input[type="checkbox"]:checked');

        const current = Array.from(checkedBoxes).map(box => {
            const li = box.closest('li');
            const labelText = li ? li.textContent.trim() : 'Unnamed';
            return {
                id: box.value,
                name: labelText
            };
        });

        dropdown.innerHTML = '<option value="">— Select Primary Category —</option>';

        current.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.name;

            if (cat.id === selectedVal) {
                option.selected = true;
            }

            dropdown.appendChild(option);
        });
    }

    checklist.addEventListener('change', function (event) {
        if (event.target && event.target.matches('input[type="checkbox"]')) {
            updateDropdown();
        }
    });

    updateDropdown();
});

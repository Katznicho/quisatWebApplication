<script>
    function addRepeaterRow(containerId, templateId) {
        const container = document.getElementById(containerId);
        const template = document.getElementById(templateId);
        if (!container || !template) return;

        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
        reindexRepeaterRows(containerId);
    }

    function removeRepeaterRow(button, containerId) {
        const container = document.getElementById(containerId);
        const rows = container?.querySelectorAll('[data-repeater-row]');
        if (!rows || rows.length <= 1) return;

        button.closest('[data-repeater-row]')?.remove();
        reindexRepeaterRows(containerId);
    }

    function reindexRepeaterRows(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.querySelectorAll('[data-repeater-row]').forEach((row, index) => {
            row.querySelectorAll('[data-repeater-name]').forEach((input) => {
                const base = input.getAttribute('data-repeater-name');
                input.name = base.replace('__INDEX__', index);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        ['allergies-repeater', 'emergency-contacts-repeater'].forEach(function (id) {
            reindexRepeaterRows(id);
        });
    });
</script>

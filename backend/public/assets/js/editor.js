/**
 * Minimal dependency-free rich text editor: a contenteditable div with
 * a small execCommand-based toolbar, synced into a hidden textarea
 * (id "content") that is what actually gets submitted with the form.
 */
(function () {
    function initEditor(root) {
        var toolbar = root.querySelector('.editor-toolbar');
        var content = root.querySelector('.editor-content');
        var hidden = document.getElementById('content');

        if (hidden.value) {
            content.innerHTML = hidden.value;
        }

        toolbar.addEventListener('click', function (event) {
            var button = event.target.closest('button[data-cmd]');
            if (!button) {
                return;
            }
            event.preventDefault();
            content.focus();
            document.execCommand(button.getAttribute('data-cmd'), false, button.getAttribute('data-value') || null);
            sync();
        });

        content.addEventListener('input', sync);

        function sync() {
            hidden.value = content.innerHTML;
        }

        root.closest('form').addEventListener('submit', sync);
    }

    document.querySelectorAll('.rte').forEach(initEditor);
})();

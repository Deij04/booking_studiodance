import '../css/date-inputs.css';

// Keep native date/time validation, mobile pickers, and keyboard input available.
document.querySelectorAll('input[type="date"], input[type="time"]').forEach((input) => {
    const openPicker = () => {
        if (input.disabled || input.readOnly || typeof input.showPicker !== 'function') return;
        try {
            input.showPicker();
        } catch {
            // The native picker indicator remains available when the browser
            // disallows programmatic opening (for example, in an iframe).
        }
    };
    input.addEventListener('click', openPicker);
    input.addEventListener('keydown', (event) => {
        if (event.altKey && event.key === 'ArrowDown') {
            openPicker();
        }
    });
});

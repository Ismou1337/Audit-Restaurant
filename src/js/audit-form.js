// File: /audit-app/audit-app/src/js/audit-form.js

document.addEventListener('DOMContentLoaded', function() {
    const auditForm = document.getElementById('auditForm');
    const submitBtn = document.getElementById('submitBtn');
    let isSubmitting = false;

    auditForm.addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            alert('Soumission déjà en cours...');
            return;
        }

        // Final validation
        if (!validateForm()) {
            e.preventDefault();
            alert('Veuillez compléter tous les éléments obligatoires.');
            return;
        }

        // Confirmation before submission
        if (!confirm('Êtes-vous sûr de vouloir envoyer cet audit ?')) {
            e.preventDefault();
            return;
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi en cours...';

        // Set the end time for the audit
        document.getElementById('heureFin').value = new Date().toISOString();

        // Disable all inputs to prevent changes
        const inputs = auditForm.querySelectorAll('input, select, button');
        inputs.forEach(input => {
            input.disabled = true;
        });
    });

    function validateForm() {
        // Implement form validation logic here
        // Return true if valid, false otherwise
        return true; // Placeholder for actual validation
    }
});
document.addEventListener("DOMContentLoaded", function () {
    console.log("FindIT Frontend Engine Initialized Successfully.");

    // 1. Automatically fade out alert messages after 5 seconds to keep the UI clean
    const alerts = document.querySelectorAll(".alert-success, .alert-warning");
    alerts.forEach(function (alert) {
        setTimeout(function () {
            // Check if Bootstrap fade class applies, or gracefully hide via JavaScript transition
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(function () {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // 2. Prevent Double Form Submissions on crucial post buttons
    const forms = document.querySelectorAll("form");
    forms.forEach(function (form) {
        form.addEventListener("submit", function () {
            const submitButtons = form.querySelectorAll("button[type='submit']");
            submitButtons.forEach(function (btn) {
                // Change state layout to hint progress to the student
                btn.disabled = true;
                btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...`;
            });
        });
    });

    // 3. Optional: Dynamic interactive image attachment client-side feedback validation
    const fileInputs = document.querySelectorAll("input[type='file']");
    fileInputs.forEach(function (input) {
        input.addEventListener("change", function () {
            if (this.files && this.files[0]) {
                const fileSize = this.files[0].size / 1024 / 1024; // Convert size metrics to MB
                if (fileSize > 2) {
                    alert("⚠️ Warning: Image attachment is too large. Max file size upload target allowed is 2MB.");
                    this.value = ""; // Clear file selector value parameter
                }
            }
        });
    });
});
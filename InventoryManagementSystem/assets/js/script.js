// Run after page loads
document.addEventListener("DOMContentLoaded", () => {

    /* ==========================
       Confirm Before Actions
    ========================== */
    document.querySelectorAll(".confirm-action").forEach(btn => {
        btn.addEventListener("click", (e) => {
            if (!confirm("Are you sure you want to proceed?")) {
                e.preventDefault();
            }
        });
    });

    /* ==========================
       Highlight Low Stock Items
    ========================== */
    document.querySelectorAll("tr[data-stock]").forEach(row => {
        let stock = parseFloat(row.dataset.stock);
        let reorder = parseFloat(row.dataset.reorder);

        if (stock <= reorder) {
            row.classList.add("low-stock");
        } else {
            row.classList.add("ok-stock");
        }
    });

    /* ==========================
       Simple Form Validation
    ========================== */
    document.querySelectorAll("form").forEach(form => {
        form.addEventListener("submit", (e) => {
            const inputs = form.querySelectorAll("input[required]");
            let valid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.style.border = "2px solid red";
                    valid = false;
                } else {
                    input.style.border = "";
                }
            });

            if (!valid) {
                e.preventDefault();
                alert("Please fill in all required fields.");
            }
        });
    });


    /* ==========================
   Dashboard Stock Chart
========================== */
const chartCanvas = document.getElementById("stockChart");

if (chartCanvas) {
    const totalItems = chartCanvas.dataset.total;

    new Chart(chartCanvas, {
        type: "doughnut",
        data: {
            labels: ["Items"],
            datasets: [{
                data: [totalItems],
                backgroundColor: ["#000"]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "75%",
            plugins: {
                legend: { display: false }
            }
        }
    });
}

});
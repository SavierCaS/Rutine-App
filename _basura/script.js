document.addEventListener("DOMContentLoaded", function () {
    const canvas = document.getElementById("caloriesChart");

    if (!canvas) {
        console.error("No se encontró el elemento canvas con ID 'caloriesChart'");
        return;
    }

    const ctx = canvas.getContext("2d");

    if (window["chartjs-plugin-annotation"]) {
        Chart.register(window["chartjs-plugin-annotation"]);
    } else {
        console.error("El plugin de anotaciones no se cargó correctamente.");
        return;
    }

    const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
    gradient.addColorStop(0, "rgba(0, 255, 128, 0.4)");
    gradient.addColorStop(1, "rgba(0, 128, 64, 0.05)");

    const caloriasQuemadas = [500, 700, 650, 800, 1200, 750, 920];
    const dias = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"];

    const maxCalorias = Math.max(...caloriasQuemadas);
    const recordIndex = caloriasQuemadas.indexOf(maxCalorias);

    const chart = new Chart(ctx, {
        type: "line",
        data: {
            labels: dias,
            datasets: [{
                label: "Calorías Quemadas",
                data: caloriasQuemadas,
                fill: true,
                backgroundColor: gradient,
                borderColor: "#00cc66",
                borderWidth: 2,
                pointBackgroundColor: "#ffffff",
                pointBorderColor: "#00cc66",
                pointRadius: 5,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 800,           
                onComplete: function () {
               
                    setTimeout(() => {
                        chart.options.plugins.annotation = {
                            annotations: {
                                recordLabel: {
                                    type: "label",
                                    xValue: recordIndex,
                                    yValue: maxCalorias,
                                    backgroundColor: "rgba(255, 204, 0, 0.8)",
                                    borderColor: "#ffcc00",
                                    borderWidth: 2,
                                    content: [`🔥 ¡Nuevo récord! ${maxCalorias} kcal`],
                                    font: {
                                        size: 14,
                                        weight: "bold",
                                        color: "black"
                                    },
                                    position: "top",
                                    yAdjust: -25, 
                                    opacity: 0, 
                                }
                            }
                        };
                        chart.update();
                        setTimeout(() => {
                            chart.options.plugins.annotation.annotations.recordLabel.opacity = 1;
                            chart.update();
                        }, 0); 
                    }, 0); 
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: maxCalorias + 200
                }
            }
        }
    });
});
async function toggleDone(button, dayId) {
    if (button.disabled) return;

    // Si no se pasa dayId explícito, obtenerlo del encabezado de la tarjeta
    if (!dayId) {
        const card = button.closest(".day-card");
        dayId = card ? card.querySelector("h3")?.textContent.trim().toLowerCase() : "dia";
    }

    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = "Guardando...";

    try {
        const res = await fetch("controllers/guardar_progreso.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ day_id: dayId, status: "completed" })
        });
        const data = await res.json();

        if (data.success) {
            button.textContent = "✔ Hecho";
            button.style.backgroundColor = "#999";
            button.style.cursor = "default";
            button.classList.add("completed");
            const fill = document.getElementById("progressFill");
            if (fill) fill.style.width = data.progressPercentage + "%";
        } else {
            throw new Error(data.message || "Error al guardar");
        }
    } catch (err) {
        console.error(err);
        button.disabled = false;
        button.textContent = originalText;
        alert("No se pudo guardar el progreso. Intenta de nuevo.");
    }
}

// Cargar estado guardado al iniciar
document.addEventListener("DOMContentLoaded", async function () {
    const fill = document.getElementById("progressFill");
    if (!fill) return;

    try {
        const res = await fetch("controllers/guardar_progreso.php");
        const data = await res.json();
        if (data.success && data.completedDays) {
            fill.style.width = data.progressPercentage + "%";
            document.querySelectorAll(".day-card").forEach(card => {
                const day = card.querySelector("h3")?.textContent.trim().toLowerCase();
                const btn = card.querySelector(".mark-done");
                if (btn && data.completedDays.includes(day)) {
                    btn.textContent = "✔ Hecho";
                    btn.style.backgroundColor = "#999";
                    btn.style.cursor = "default";
                    btn.disabled = true;
                    btn.classList.add("completed");
                }
            });
        }
    } catch (e) {
        console.warn("No se pudo sincronizar el estado inicial de rutinas:", e);
    }
});
jQuery(document).ready(function ($) {
    // Daily Searches Chart
    if (typeof trbDailyData !== "undefined" && trbDailyData.length > 0) {
        const dailyCtx = document.getElementById("trb-daily-chart");
        if (dailyCtx) {
            const labels = trbDailyData.map((item) => item.search_date);
            const withResults = trbDailyData.map((item) =>
                parseInt(item.with_results),
            );
            const withoutResults = trbDailyData.map((item) =>
                parseInt(item.without_results),
            );

            new Chart(dailyCtx, {
                type: "line",
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: "With Results",
                            data: withResults,
                            borderColor: "#2271b1",
                            backgroundColor: "rgba(34, 113, 177, 0.1)",
                            tension: 0.3,
                        },
                        {
                            label: "Without Results",
                            data: withoutResults,
                            borderColor: "#d63638",
                            backgroundColor: "rgba(214, 54, 56, 0.1)",
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: "top",
                        },
                        tooltip: {
                            mode: "index",
                            intersect: false,
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                            },
                        },
                    },
                },
            });
        }
    }

    // Results Distribution Pie Chart
    if (typeof trbStatsData !== "undefined") {
        const resultsCtx = document.getElementById("trb-results-chart");
        if (resultsCtx) {
            new Chart(resultsCtx, {
                type: "doughnut",
                data: {
                    labels: ["With Results", "Without Results"],
                    datasets: [
                        {
                            data: [
                                trbStatsData.with_results,
                                trbStatsData.without_results,
                            ],
                            backgroundColor: ["#2271b1", "#d63638"],
                            borderWidth: 0,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: "bottom",
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || "";
                                    const value = context.parsed || 0;
                                    const total =
                                        trbStatsData.with_results + trbStatsData.without_results;
                                    const percentage = total > 0 ? (value / total) * 100 : 0;
                                    return label + ": " + value + " (" + percentage.toFixed(1) + "%)";
                                },
                            },
                        },
                    },
                },
            });
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('taskChart').getContext('2d');

    const todo = parseInt(document.getElementById('taskChart').dataset.todo);
    const inProgress = parseInt(document.getElementById('taskChart').dataset.inProgress);
    const done = parseInt(document.getElementById('taskChart').dataset.done);

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['To Do', 'In Progress', 'Done'],
            datasets: [{
                label: 'Tasks by Status',
                data: [todo, inProgress, done],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});

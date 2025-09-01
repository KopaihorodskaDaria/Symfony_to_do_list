
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("input[name='task[deadline]']", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
    });
});

document.addEventListener('DOMContentLoaded', () => {

    const alerts = document.querySelectorAll('.alert');

    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('fade-out');
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 2000);
    });
});
document.addEventListener('DOMContentLoaded', () => {
    const statuses = ['todo', 'in_progress', 'done'];

    statuses.forEach(status => {
        const el = document.getElementById(status);

        Sortable.create(el, {
            group: 'tasks',
            animation: 150,
            onEnd: evt => {
                const taskId = evt.item.dataset.id;
                const newStatus = evt.to.id;

                fetch(`/task/${taskId}/change-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ status: newStatus }),
                }).then(res => {
                    if (!res.ok) alert('Error updating status');
                });
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.status-tabs .tab');
    const tables = {
        pending: document.querySelector('.table-pending'),
        approved: document.querySelector('.table-approved')
    };

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const target = this.dataset.target;

            Object.keys(tables).forEach(key => {
                tables[key].style.display = (key === target) ? '' : 'none';
            });
        });
    });
});
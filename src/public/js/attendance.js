function updateTime() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('work-time').textContent = `${h}:${m}`;
}
setInterval(updateTime, 1000);


function submitAction(action) {
    const form = document.getElementById('attendance-form');
    const actionInput = document.getElementById('action-input');
    const buttons = document.querySelectorAll('.attendance__buttons button');
    const statusText = document.querySelector('.status-text');
    const finishedMessage = document.querySelector('.finished-message');

    switch (action) {
        case 'clock_in':
            statusText.textContent = '出勤中';
            break;
        case 'break_in':
            statusText.textContent = '休憩中';
            break;
        case 'break_out':
            statusText.textContent = '出勤中';
            break;
        case 'clock_out':
            statusText.textContent = '退勤済';
            if (finishedMessage) finishedMessage.style.display = 'block';
            break;
    }
    buttons.forEach(btn => btn.disabled = true);

    actionInput.value = action;
    form.submit();
}
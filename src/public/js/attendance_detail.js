document.addEventListener('DOMContentLoaded', () => {
    const timeFields = document.querySelectorAll('input[name^="clock_"], input[name*="break_"]');

    timeFields.forEach(field => {

        field.addEventListener('blur', () => {
            field.value = normalizeTime(field.value);
        });
    });

    function normalizeTime(time) {
        if (!time) return '';
        time = time.replace(/[０-９：]/g, s => String.fromCharCode(s.charCodeAt(0) - 65248));
        time = time.replace(/[^\d:]/g, '');

        let hour = 0,
            minute = 0;

        const hmMatch = time.match(/^(\d{1,2}):(\d{1,2})$/);
        if (hmMatch) {
            hour = parseInt(hmMatch[1], 10);
            minute = parseInt(hmMatch[2], 10);
        } else if (/^\d{1,4}$/.test(time)) {

            time = time.padStart(4, '0');
            hour = parseInt(time.slice(0, 2), 10);
            minute = parseInt(time.slice(2, 4), 10);
        } else {
            return time;
        }

        if (hour === 24 && minute === 0) {
            hour = 23;
            minute = 59;
        }

        return `${String(hour).padStart(2,'0')}:${String(minute).padStart(2,'0')}`;
    }

    const commentTextarea = document.querySelector('.textarea-comment');
    if (commentTextarea) {
        const resizeTextarea = (el) => {
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        };

        resizeTextarea(commentTextarea);

        commentTextarea.addEventListener('input', () => {
            resizeTextarea(commentTextarea);
        });
    }
});
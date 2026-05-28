document.querySelectorAll('button').forEach(button => {
  button.addEventListener('mousedown', () => button.style.transform = 'scale(.98)');
  button.addEventListener('mouseup', () => button.style.transform = '');
});
document.querySelectorAll('.time-bar-fill').forEach(bar => {
    const startDate = new Date(bar.dataset.start).getTime();
    const endDate = new Date(bar.dataset.end).getTime();

    function updateBar() {
        const now = new Date().getTime();
        const total = endDate - startDate;
        const passed = now - startDate;

        let percent = (passed / total) * 100;

        if (percent < 0) percent = 0;
        if (percent > 100) percent = 100;

        bar.style.width = percent + "%";
    }

    updateBar();
    setInterval(updateBar, 1000);
});

document.querySelectorAll('.timer').forEach(timer => {
    const endDate = new Date(timer.dataset.end).getTime();

    function updateTimer() {
        const now = new Date().getTime();
        let diff = endDate - now;

        if (diff <= 0) {
            timer.textContent = "Terminé";
            return;
        }

        const hours = Math.floor(diff / (1000 * 60 * 60));
        diff %= 1000 * 60 * 60;

        const minutes = Math.floor(diff / (1000 * 60));
        diff %= 1000 * 60;

        const seconds = Math.floor(diff / 1000);

        timer.textContent =
            String(hours).padStart(2, "0") + ":" +
            String(minutes).padStart(2, "0") + ":" +
            String(seconds).padStart(2, "0");
    }

    updateTimer();
    setInterval(updateTimer, 1000);
});

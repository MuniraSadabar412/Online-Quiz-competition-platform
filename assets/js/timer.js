/**
 * Countdown Timer Module
 * Online Quiz Competition Platform
 * 
 * Synchronizes with server-side deadline timestamp.
 * Even if user refreshes the page, the timer calculates time against true deadline.
 */

class QuizTimer {
    /**
     * @param {number} deadlineEpochMs - Target deadline timestamp in milliseconds
     * @param {HTMLElement} displayElement - DOM element displaying the MM:SS text
     * @param {HTMLElement} timerContainer - Container element for warning/danger classes
     * @param {Function} onExpire - Callback executed when timer reaches 0
     */
    constructor(deadlineEpochMs, displayElement, timerContainer, onExpire) {
        this.deadline = deadlineEpochMs;
        this.display = displayElement;
        this.container = timerContainer;
        this.onExpire = onExpire;
        this.intervalId = null;
        this.hasExpired = false;
    }

    start() {
        this.update();
        this.intervalId = setInterval(() => this.update(), 1000);
    }

    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    update() {
        const now = Date.now();
        const diffSeconds = Math.max(0, Math.floor((this.deadline - now) / 1000));

        if (diffSeconds <= 0 && !this.hasExpired) {
            this.hasExpired = true;
            this.stop();
            this.render(0);
            if (this.container) {
                this.container.classList.add('timer-danger');
            }
            if (typeof this.onExpire === 'function') {
                this.onExpire();
            }
            return;
        }

        this.render(diffSeconds);

        // Visual warnings
        if (this.container) {
            if (diffSeconds <= 60) {
                this.container.classList.remove('timer-warning');
                this.container.classList.add('timer-danger');
            } else if (diffSeconds <= 300) {
                this.container.classList.add('timer-warning');
                this.container.classList.remove('timer-danger');
            } else {
                this.container.classList.remove('timer-warning', 'timer-danger');
            }
        }
    }

    render(seconds) {
        if (!this.display) return;
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;

        if (mins >= 60) {
            const hrs = Math.floor(mins / 60);
            const remMins = mins % 60;
            this.display.textContent = `${String(hrs).padStart(2, '0')}:${String(remMins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        } else {
            this.display.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }
    }
}

/**
 * Active Quiz Engine (quiz.js)
 * Online Quiz Competition Platform
 * 
 * Handles client-side question navigation, palette synchronization,
 * asynchronous background answer auto-saving, and modal confirmations.
 */

class QuizEngine {
    constructor(config) {
        this.attemptId = config.attemptId;
        this.totalQuestions = config.totalQuestions;
        this.csrfToken = config.csrfToken;
        this.saveAnswerUrl = config.saveAnswerUrl;
        this.submitQuizUrl = config.submitQuizUrl;
        this.deadlineEpochMs = config.deadlineEpochMs;

        this.currentIndex = 0;
        this.questionCards = document.querySelectorAll('.question-card');
        this.paletteBtns = document.querySelectorAll('.palette-btn');
        this.prevBtn = document.getElementById('prevQuestionBtn');
        this.nextBtn = document.getElementById('nextQuestionBtn');
        this.reviewSubmitBtn = document.getElementById('reviewSubmitBtn');
        this.submitForm = document.getElementById('quizSubmitForm');

        this.init();
    }

    init() {
        this.bindEvents();
        this.showQuestion(0);
        this.updatePalette();
        this.startTimer();
    }

    bindEvents() {
        // Prev / Next button listeners
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => {
                if (this.currentIndex > 0) {
                    this.showQuestion(this.currentIndex - 1);
                }
            });
        }

        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => {
                if (this.currentIndex < this.totalQuestions - 1) {
                    this.showQuestion(this.currentIndex + 1);
                }
            });
        }

        // Palette grid item clicks
        this.paletteBtns.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const targetIdx = parseInt(e.currentTarget.getAttribute('data-index'), 10);
                this.showQuestion(targetIdx);
            });
        });

        // Answer radio change listeners (AJAX autosave)
        const radioInputs = document.querySelectorAll('input[type="radio"][name^="question_"]');
        radioInputs.forEach((radio) => {
            radio.addEventListener('change', (e) => {
                const qId = e.target.getAttribute('data-question-id');
                const optId = e.target.value;
                this.saveAnswer(qId, optId);
                this.updatePalette();
            });
        });

        // Submit Review Modal trigger
        if (this.reviewSubmitBtn) {
            this.reviewSubmitBtn.addEventListener('click', () => {
                this.prepareSubmissionModal();
                openModal('submissionModal');
            });
        }
    }

    showQuestion(index) {
        if (index < 0 || index >= this.totalQuestions) return;

        this.questionCards.forEach((card, idx) => {
            if (idx === index) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });

        this.currentIndex = index;

        // Update Prev / Next button states
        if (this.prevBtn) {
            this.prevBtn.disabled = (index === 0);
            this.prevBtn.style.opacity = (index === 0) ? '0.4' : '1';
        }
        if (this.nextBtn) {
            if (index === this.totalQuestions - 1) {
                this.nextBtn.style.display = 'none';
            } else {
                this.nextBtn.style.display = 'inline-flex';
            }
        }

        this.updatePalette();
    }

    updatePalette() {
        let answeredCount = 0;

        this.paletteBtns.forEach((btn, idx) => {
            const card = this.questionCards[idx];
            const checkedRadio = card ? card.querySelector('input[type="radio"]:checked') : null;

            btn.classList.remove('current', 'answered');

            if (checkedRadio) {
                btn.classList.add('answered');
                answeredCount++;
            }

            if (idx === this.currentIndex) {
                btn.classList.add('current');
            }
        });

        // Update answered / remaining counter in header if present
        const answeredCounter = document.getElementById('answeredCountDisplay');
        if (answeredCounter) {
            answeredCounter.textContent = `${answeredCount} of ${this.totalQuestions} answered`;
        }
    }

    saveAnswer(questionId, optionId) {
        const formData = new FormData();
        formData.append('attempt_id', this.attemptId);
        formData.append('question_id', questionId);
        formData.append('selected_option_id', optionId);
        formData.append('csrf_token', this.csrfToken);

        fetch(this.saveAnswerUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.warn('Answer autosave response:', data.message);
            }
        })
        .catch(err => {
            console.error('Autosave network error:', err);
        });
    }

    prepareSubmissionModal() {
        let answered = 0;
        this.questionCards.forEach((card) => {
            if (card.querySelector('input[type="radio"]:checked')) {
                answered++;
            }
        });
        const unanswered = this.totalQuestions - answered;

        const modalAnswered = document.getElementById('modalAnsweredCount');
        const modalUnanswered = document.getElementById('modalUnansweredCount');

        if (modalAnswered) modalAnswered.textContent = answered;
        if (modalUnanswered) modalUnanswered.textContent = unanswered;
    }

    startTimer() {
        const displayEl = document.getElementById('timerDisplay');
        const containerEl = document.getElementById('timerBox');

        const timer = new QuizTimer(
            this.deadlineEpochMs,
            displayEl,
            containerEl,
            () => this.handleTimeout()
        );
        timer.start();
    }

    handleTimeout() {
        alert("Time is up! Your quiz will now be submitted automatically.");
        if (this.submitForm) {
            // Append timeout indicator
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'auto_submitted';
            input.value = '1';
            this.submitForm.appendChild(input);
            this.submitForm.submit();
        }
    }
}

/**
 * Speaking recorder of the Try Out (Alpine "tryoutSpeechRecorder").
 *
 * The answer is transcribed live by the browser's own speech recognition
 * (Web Speech API: window.SpeechRecognition / webkitSpeechRecognition), so
 * it is free and needs no audio upload: only the transcript and the speaking
 * time are sent with $wire.submitPart(). A local MediaRecorder copy lets the
 * mentee listen back before sending; it never leaves the browser.
 *
 * Supported: Chrome and Edge (desktop and Android), Safari (partly).
 * Firefox has no speech recognition, so the component shows a notice.
 */

/** Pause in milliseconds before a recognition session that ended is restarted. @type {number} */
const RESTART_DELAY_MS = 150;

/**
 * The browser's SpeechRecognition constructor, or null.
 *
 * @returns {typeof SpeechRecognition|null}
 */
const recognitionClass = () => window.SpeechRecognition || window.webkitSpeechRecognition || null;

/**
 * Whether this browser can transcribe speech.
 *
 * @returns {boolean}
 */
export const speechSupported = () => recognitionClass() !== null;

/**
 * Alpine component of one Speaking part.
 *
 * @param {{ index: number, prep: number, max: number, lang: string }} config Part index, preparation and speaking seconds, recognition language.
 * @returns {object} Alpine component.
 */
const tryoutSpeechRecorder = (config) => {
    // Browser objects stay outside Alpine's reactive proxies.
    let recognition = null;
    let mediaRecorder = null;
    let stream = null;
    let analyser = null;
    let clock = null;
    let stopping = false;
    let lastFinal = '';

    return {
        supported: speechSupported(),
        phase: 'idle',
        prepLeft: config.prep,
        elapsed: 0,
        max: config.max,
        finalText: '',
        interimText: '',
        error: '',
        blobUrl: null,
        levels: [3, 5, 8, 5, 9, 6, 4, 7, 5, 8, 4, 6, 3],

        get transcript() {
            return `${this.finalText} ${this.interimText}`.replace(/\s+/g, ' ').trim();
        },

        get words() {
            return this.transcript === '' ? 0 : this.transcript.split(' ').length;
        },

        fmt(seconds) {
            return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        },

        destroy() {
            this.cleanup();
        },

        startPrep() {
            this.phase = 'prep';
            clock = setInterval(() => {
                this.prepLeft--;

                if (this.prepLeft <= 0) {
                    clearInterval(clock);
                    this.record();
                }
            }, 1000);
        },

        skipPrep() {
            clearInterval(clock);
            this.record();
        },

        async record() {
            this.error = '';
            this.finalText = '';
            this.interimText = '';
            lastFinal = '';
            stopping = false;

            try {
                stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            } catch (exception) {
                this.phase = 'idle';
                this.error = 'Mikrofon belum diizinkan. Izinkan akses mikrofon di browser, lalu coba lagi.';

                return;
            }

            this.startLocalCopy();
            this.startLevelMeter();
            this.startRecognition();

            this.phase = 'recording';
            this.elapsed = 0;
            clock = setInterval(() => {
                this.elapsed++;
                this.readLevels();

                if (this.elapsed >= this.max) {
                    this.stop();
                }
            }, 1000);
        },

        startRecognition() {
            const Recognition = recognitionClass();
            recognition = new Recognition();
            recognition.lang = config.lang;
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.maxAlternatives = 1;

            recognition.onresult = (event) => {
                let interim = '';

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const text = event.results[i][0].transcript.trim();

                    if (! event.results[i].isFinal) {
                        interim += ` ${text}`;
                        continue;
                    }

                    // Chrome on Android repeats earlier finals inside a new one; keep only the new part.
                    const fresh = lastFinal && text.startsWith(lastFinal) ? text.slice(lastFinal.length).trim() : text;
                    lastFinal = text;

                    if (fresh !== '') {
                        this.finalText = `${this.finalText} ${fresh}`.trim();
                    }
                }

                this.interimText = interim.trim();
            };

            recognition.onerror = (event) => {
                if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                    this.error = 'Pengenalan suara tidak diizinkan di browser ini. Izinkan mikrofon, lalu rekam ulang.';
                    this.stop();
                } else if (event.error === 'network') {
                    this.error = 'Pengenalan suara butuh koneksi internet yang stabil. Cek koneksimu, lalu rekam ulang.';
                } else if (event.error === 'language-not-supported') {
                    this.error = 'Browser ini belum mendukung pengenalan suara bahasa Inggris. Coba Google Chrome terbaru.';
                    this.stop();
                }
                // "no-speech" and "aborted" are normal pauses: onend restarts the session.
            };

            recognition.onend = () => {
                if (stopping) {
                    this.finishStop();

                    return;
                }

                // Browsers end a session after a pause or about a minute; keep listening.
                if (this.phase === 'recording') {
                    lastFinal = '';
                    setTimeout(() => {
                        try { recognition?.start(); } catch (exception) { /* already started */ }
                    }, RESTART_DELAY_MS);
                }
            };

            recognition.start();
        },

        startLocalCopy() {
            this.blobUrl = null;

            if (! window.MediaRecorder) {
                return;
            }

            try {
                const chunks = [];
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.ondataavailable = (event) => event.data.size && chunks.push(event.data);
                mediaRecorder.onstop = () => {
                    this.blobUrl = URL.createObjectURL(new Blob(chunks, { type: mediaRecorder.mimeType || 'audio/webm' }));
                };
                mediaRecorder.start();
            } catch (exception) {
                mediaRecorder = null;
            }
        },

        startLevelMeter() {
            try {
                const context = new (window.AudioContext || window.webkitAudioContext)();
                analyser = context.createAnalyser();
                analyser.fftSize = 64;
                context.createMediaStreamSource(stream).connect(analyser);
            } catch (exception) {
                analyser = null;
            }
        },

        readLevels() {
            if (! analyser) {
                return;
            }

            const data = new Uint8Array(analyser.frequencyBinCount);
            analyser.getByteFrequencyData(data);
            this.levels = this.levels.map((_, i) => 3 + Math.round((data[i + 1] || 0) / 18));
        },

        stop() {
            if (this.phase !== 'recording') {
                return;
            }

            clearInterval(clock);
            stopping = true;
            this.phase = 'finishing';

            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            }

            try { recognition?.stop(); } catch (exception) { this.finishStop(); }

            // Some browsers never fire onend after stop(); do not wait forever.
            setTimeout(() => this.finishStop(), 1500);
        },

        finishStop() {
            if (this.phase !== 'finishing') {
                return;
            }

            this.finalText = this.transcript;
            this.interimText = '';
            this.phase = 'recorded';
            stream?.getTracks().forEach((track) => track.stop());
        },

        reset() {
            this.cleanup();
            this.phase = 'idle';
            this.prepLeft = config.prep;
            this.elapsed = 0;
            this.finalText = '';
            this.interimText = '';
            this.error = '';
            this.blobUrl = null;
        },

        async send() {
            if (this.phase !== 'recorded') {
                return;
            }

            this.phase = 'sending';
            const stored = await this.$wire.submitPart(config.index, this.transcript, this.elapsed);

            if (stored) {
                this.reset();
            } else {
                this.phase = 'recorded';
            }
        },

        cleanup() {
            clearInterval(clock);
            stopping = true;

            try { recognition?.abort(); } catch (exception) { /* not running */ }

            stream?.getTracks().forEach((track) => track.stop());
            recognition = null;
            mediaRecorder = null;
            stream = null;
        },
    };
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('tryoutSpeechRecorder', tryoutSpeechRecorder);
});

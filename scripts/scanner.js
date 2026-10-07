// Scanner de codes QR SuiviMat (includes/scanner.php)
(() => {
    // Détecteur natif du navigateur si disponible, sinon jsQR (chargé seulement si nécessaire)
    const JSQR_URL = "https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js";
    const JSQR_INTEGRITY = "sha384-b5Ya4Bq3qCyz39m2ISh+4DxjAIljdeFwK/BsXLuj9gugaNwAcj/ia15fxNZL9Nlx";
    const CODE_PATTERN = /^[A-Z0-9]{4}$/;
    const SCAN_INTERVAL = 200;      // ms entre deux analyses d'image
    const SAME_CODE_DELAY = 3000;   // ms pendant lesquelles un même code n'est pas relu
    const MAX_FRAME_WIDTH = 640;    // l'image est réduite avant analyse

    let jsQRPromise = null;

    function loadJsQR() {
        if (!jsQRPromise) {
            jsQRPromise = new Promise((resolve, reject) => {
                const script = document.createElement("script");
                script.src = JSQR_URL;
                script.integrity = JSQR_INTEGRITY;
                script.crossOrigin = "anonymous";
                script.onload = () => resolve(window.jsQR);
                script.onerror = () => reject(new Error("jsQR indisponible"));
                document.head.appendChild(script);
            });
        }
        return jsQRPromise;
    }

    async function createDetector() {
        if ("BarcodeDetector" in window) {
            const formats = await BarcodeDetector.getSupportedFormats();
            if (formats.includes("qr_code")) {
                const detector = new BarcodeDetector({ formats: ["qr_code"] });
                return async (canvas) => (await detector.detect(canvas))[0]?.rawValue ?? null;
            }
        }

        const jsQR = await loadJsQR();
        return async (canvas) => {
            const context = canvas.getContext("2d", { willReadFrequently: true });
            const image = context.getImageData(0, 0, canvas.width, canvas.height);
            return jsQR(image.data, image.width, image.height, { inversionAttempts: "dontInvert" })?.data ?? null;
        };
    }

    // Extrait le code d'un QR SuiviMat (URL « ...?code=XXXX ») ou d'une saisie brute
    function parseCode(text) {
        let code = text.trim();
        try {
            const url = new URL(code);
            code = url.searchParams.get("code") ?? "";
        } catch {
            // Pas une URL : le texte est le code lui-même
        }
        code = code.toUpperCase();
        return CODE_PATTERN.test(code) ? code : null;
    }

    class Scanner {
        constructor(element) {
            this.element = element;
            this.video = element.querySelector("[data-role='video']");
            this.placeholder = element.querySelector("[data-role='placeholder']");
            this.startButton = element.querySelector("[data-role='start']");
            this.stopButton = element.querySelector("[data-role='stop']");
            this.status = element.querySelector("[data-role='status']");
            this.manualForm = element.querySelector("[data-role='manual-form']");
            this.manualInput = element.querySelector("[data-role='manual-input']");
            this.canvas = document.createElement("canvas");

            this.stream = null;
            this.detect = null;
            this.paused = false;
            this.timer = null;
            this.lastCode = null;
            this.lastCodeTime = 0;

            this.startButton.addEventListener("click", () => this.start());
            this.stopButton.addEventListener("click", () => this.stop());
            this.manualForm.addEventListener("submit", (e) => {
                e.preventDefault();
                this.submitManual();
            });

            // Caméra coupée quand la page est en arrière-plan
            document.addEventListener("visibilitychange", () => {
                if (document.hidden && this.stream) {
                    this.stop();
                }
            });
        }

        setStatus(text, type = "muted") {
            this.status.className = `small mb-0 text-${type}`;
            this.status.textContent = text;
        }

        async start() {
            if (this.stream) {
                return;
            }
            if (!navigator.mediaDevices?.getUserMedia) {
                this.setStatus("La caméra n'est pas disponible sur cet appareil ou cette connexion. Saisissez le code à la main.", "danger");
                return;
            }

            this.startButton.disabled = true;
            this.setStatus("Activation de la caméra…");

            try {
                this.detect ??= await createDetector();
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: "environment" },
                    audio: false,
                });
            } catch (error) {
                this.startButton.disabled = false;
                const denied = error.name === "NotAllowedError";
                this.setStatus(denied
                    ? "L'accès à la caméra a été refusé. Autorisez-le dans votre navigateur ou saisissez le code à la main."
                    : "Impossible d'activer la caméra. Saisissez le code à la main.", "danger");
                return;
            }

            this.video.srcObject = this.stream;
            await this.video.play();
            this.video.hidden = false;
            this.placeholder.classList.add("d-none");
            this.stopButton.hidden = false;
            this.startButton.disabled = false;
            this.paused = false;
            this.setStatus("Visez un code QR.");
            this.scheduleScan();
        }

        stop() {
            clearTimeout(this.timer);
            this.stream?.getTracks().forEach((track) => track.stop());
            this.stream = null;
            this.video.srcObject = null;
            this.video.hidden = true;
            this.placeholder.classList.remove("d-none");
            this.stopButton.hidden = true;
            this.setStatus("");
        }

        // En pause, la caméra reste allumée mais les codes ne sont plus lus
        pause() {
            this.paused = true;
        }

        resume() {
            this.paused = false;
        }

        scheduleScan() {
            this.timer = setTimeout(() => this.scanFrame(), SCAN_INTERVAL);
        }

        async scanFrame() {
            if (!this.stream) {
                return;
            }

            if (!this.paused && this.video.readyState >= this.video.HAVE_CURRENT_DATA) {
                const scale = Math.min(1, MAX_FRAME_WIDTH / this.video.videoWidth);
                this.canvas.width = Math.round(this.video.videoWidth * scale);
                this.canvas.height = Math.round(this.video.videoHeight * scale);
                this.canvas.getContext("2d", { willReadFrequently: true }).drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);

                try {
                    const text = await this.detect(this.canvas);
                    if (text) {
                        this.handleText(text);
                    }
                } catch {
                    // Image illisible : on réessaie à la prochaine
                }
            }

            this.scheduleScan();
        }

        handleText(text) {
            const code = parseCode(text);
            if (!code) {
                this.setStatus("Ce code QR n'est pas un code SuiviMat.", "danger");
                return;
            }

            // Le même code reste devant la caméra : on ne le relit pas en boucle
            const now = Date.now();
            if (code === this.lastCode && now - this.lastCodeTime < SAME_CODE_DELAY) {
                return;
            }
            this.lastCode = code;
            this.lastCodeTime = now;

            navigator.vibrate?.(100);
            this.flash();
            this.setStatus(`Code ${code} lu.`, "success");
            this.emit(code, "camera");
        }

        submitManual() {
            const code = parseCode(this.manualInput.value);
            if (!code) {
                this.manualInput.classList.add("is-invalid");
                this.setStatus("Le code doit contenir 4 lettres ou chiffres.", "danger");
                return;
            }

            this.manualInput.classList.remove("is-invalid");
            this.manualInput.value = "";
            this.setStatus(`Code ${code} saisi.`, "success");
            this.emit(code, "manual");
        }

        flash() {
            const viewport = this.element.querySelector("[data-role='viewport']");
            viewport.classList.add("border", "border-5", "border-success");
            setTimeout(() => viewport.classList.remove("border", "border-5", "border-success"), 400);
        }

        emit(code, source) {
            this.element.dispatchEvent(new CustomEvent("code-scanned", {
                bubbles: true,
                detail: { code, source, scanner: this },
            }));
        }
    }

    document.querySelectorAll("[data-scanner]").forEach((element) => {
        element.scanner = new Scanner(element);
    });
})();

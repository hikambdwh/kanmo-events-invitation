window.scannerApp = function(config) {
    return {
        /*
         * ==========================================================
         * STATE
         * ==========================================================
         */

        scanner: null,

        scannerReady: false,

        scannerMessage: 'Preparing camera...',

        processing: false,

        result: null,

        manualValue: '',

        lastScannedValue: null,

        audioContext: null,


        /*
         * ==========================================================
         * INITIALIZATION
         * ==========================================================
         */

        async init() {

            await this.$nextTick();


            this.setupAudioUnlock();


            if (!window.isSecureContext) {

                this.scannerReady =
                    false;

                this.scannerMessage =
                    'Scanner membutuhkan koneksi HTTPS.';

                return;
            }


            /*
             * Pastikan browser mendukung camera API.
             */
            if (
                !navigator.mediaDevices ||
                !navigator.mediaDevices.getUserMedia
            ) {
                this.scannerReady = false;

                this.scannerMessage =
                    'Browser ini tidak mendukung akses kamera.';

                return;
            }


            /*
             * Buat instance scanner.
             */
            this.scanner = new Html5Qrcode(
                'qr-reader'
            );


            try {
                await this.startScanner();
            } catch (error) {
                console.error(
                    'CAMERA ERROR:',
                    error
                );

                this.scannerReady = false;

                this.setCameraErrorMessage(
                    error
                );
            }
        },


        /*
         * ==========================================================
         * START CAMERA
         * ==========================================================
         */

        async startScanner() {
            this.result = null;

            this.scannerReady = false;

            this.scannerMessage =
                'Requesting for camera access';


            const scannerConfig = {
                fps: 10,

                qrbox: {
                    width: 250,
                    height: 250,
                },
            };


            /*
             * ------------------------------------------------------
             * ATTEMPT #1
             *
             * Minta browser menggunakan kamera belakang.
             * Ini biasanya cara paling kompatibel untuk HP.
             * ------------------------------------------------------
             */

            try {
                await this.scanner.start({
                        facingMode: 'environment',
                    },

                    scannerConfig,

                    async (decodedText) => {
                            await this.handleScan(
                                decodedText
                            );
                        },

                        () => {
                            /*
                             * Frame tidak berisi QR.
                             * Tidak perlu melakukan apa-apa.
                             */
                        }
                );


                this.scannerReady = true;

                this.scannerMessage =
                    'Point the camera at the QR code.';

                return;

            } catch (error) {
                console.warn(
                    'Environment camera failed:',
                    error
                );
            }


            /*
             * ------------------------------------------------------
             * ATTEMPT #2
             *
             * Kalau facingMode gagal, ambil daftar seluruh kamera.
             * ------------------------------------------------------
             */

            let cameras = [];

            try {
                cameras =
                    await Html5Qrcode.getCameras();
            } catch (error) {
                console.error(
                    'GET CAMERAS ERROR:',
                    error
                );

                throw error;
            }


            if (!cameras.length) {
                throw new Error(
                    'Tidak ada kamera yang ditemukan.'
                );
            }


            console.log(
                'Available cameras:',
                cameras
            );


            /*
             * Cari kamera yang kemungkinan kamera belakang.
             */
            const backCamera =
                cameras.find(
                    (camera) =>
                    /back|rear|environment|belakang/i
                    .test(
                        camera.label || ''
                    )
                );


            /*
             * Kamera belakang ditempatkan paling depan.
             * Sisanya menjadi fallback.
             */
            const orderedCameras = [
                ...(
                    backCamera ? [backCamera] : []
                ),

                ...cameras.filter(
                    (camera) =>
                    !backCamera ||
                    camera.id !==
                    backCamera.id
                ),
            ];


            let lastError = null;


            /*
             * ------------------------------------------------------
             * Coba semua kamera satu per satu.
             * ------------------------------------------------------
             */

            for (
                const camera of orderedCameras
            ) {
                try {
                    console.log(
                        'Trying camera:',
                        camera.label,
                        camera.id
                    );


                    await this.scanner.start(
                        camera.id,

                        scannerConfig,

                        async (decodedText) => {
                                await this.handleScan(
                                    decodedText
                                );
                            },

                            () => {
                                /*
                                 * Ignore scan failure frame.
                                 */
                            }
                    );


                    this.scannerReady = true;

                    this.scannerMessage =
                        'Point the camera at the QR code.';


                    console.log(
                        'Camera started:',
                        camera.label
                    );


                    return;

                } catch (error) {
                    console.warn(
                        'Camera failed:',
                        camera.label,
                        error
                    );


                    lastError = error;


                    /*
                     * Beri jeda sebelum mencoba
                     * kamera berikutnya.
                     */
                    await this.sleep(300);
                }
            }


            /*
             * Semua kamera gagal.
             */
            throw (
                lastError ??
                new Error(
                    'Semua kamera gagal dibuka.'
                )
            );
        },


        /*
         * ==========================================================
         * CAMERA ERROR MESSAGE
         * ==========================================================
         */

        setCameraErrorMessage(error) {
            /*
             * html5-qrcode kadang melempar Error object,
             * kadang hanya string.
             *
             * Jadi kita cek keduanya.
             */

            const name =
                error?.name ?? '';

            const message =
                error?.message ??
                String(error ?? '');


            const fullError =
                `${name} ${message}`;


            console.error(
                'FULL CAMERA ERROR:',
                fullError
            );


            /*
             * Permission ditolak.
             */
            if (
                name === 'NotAllowedError' ||
                fullError.includes(
                    'NotAllowedError'
                ) ||
                fullError.includes(
                    'Permission denied'
                )
            ) {
                this.scannerMessage =
                    'Akses kamera ditolak. Izinkan akses kamera pada browser lalu tekan Try Again.';

                return;
            }


            /*
             * Kamera ditemukan tetapi tidak bisa dibuka.
             *
             * Ini error yang saat ini muncul di HP kamu:
             * NotReadableError: Could not start video source
             */
            if (
                name === 'NotReadableError' ||
                fullError.includes(
                    'NotReadableError'
                ) ||
                fullError.includes(
                    'Could not start video source'
                ) ||
                fullError.includes(
                    'Could not start video'
                )
            ) {
                this.scannerMessage =
                    'Kamera ditemukan tetapi tidak dapat dibuka. Tutup aplikasi lain yang memakai kamera, lalu tekan Try Again.';

                return;
            }


            /*
             * Tidak ada kamera.
             */
            if (
                name === 'NotFoundError' ||
                fullError.includes(
                    'NotFoundError'
                )
            ) {
                this.scannerMessage =
                    'Kamera tidak ditemukan pada perangkat ini.';

                return;
            }


            /*
             * Camera constraint tidak cocok.
             */
            if (
                name === 'OverconstrainedError' ||
                fullError.includes(
                    'OverconstrainedError'
                )
            ) {
                this.scannerMessage =
                    'Kamera tidak mendukung konfigurasi scanner.';

                return;
            }


            /*
             * Browser/security problem.
             */
            if (
                fullError.includes(
                    'secure context'
                )
            ) {
                this.scannerMessage =
                    'Scanner membutuhkan koneksi HTTPS.';

                return;
            }


            /*
             * Generic fallback.
             */
            this.scannerMessage =
                message ||
                'Kamera tidak dapat diakses.';
        },


        /*
         * ==========================================================
         * RETRY CAMERA
         * ==========================================================
         */

        async retryCamera() {
            if (this.processing) {
                return;
            }


            this.scannerReady = false;

            this.scannerMessage =
                'Mencoba membuka kamera...';


            /*
             * Bersihkan scanner lama terlebih dahulu.
             */
            if (this.scanner) {
                try {
                    await this.scanner.stop();
                } catch (_) {
                    /*
                     * Bisa gagal jika scanner
                     * memang belum sempat start.
                     */
                }


                try {
                    this.scanner.clear();
                } catch (_) {}
            }


            /*
             * Tunggu sedikit supaya camera resource
             * benar-benar dilepas browser.
             */
            await this.sleep(500);


            /*
             * Buat instance baru.
             */
            this.scanner =
                new Html5Qrcode(
                    'qr-reader'
                );


            try {
                await this.startScanner();

            } catch (error) {
                console.error(
                    'RETRY CAMERA ERROR:',
                    error
                );


                this.scannerReady = false;

                this.setCameraErrorMessage(
                    error
                );
            }
        },


        /*
         * ==========================================================
         * QR DETECTED
         * ==========================================================
         */

        async handleScan(value) {
            /*
             * Jangan proses QR kalau:
             *
             * - request masih berjalan
             * - QR sama sudah baru saja dibaca
             */
            if (
                this.processing ||
                value ===
                this.lastScannedValue
            ) {
                return;
            }


            this.processing = true;

            this.lastScannedValue =
                value;


            console.log(
                'QR detected:',
                value
            );


            /*
             * Pause kamera supaya QR yang sama
             * tidak dibaca berkali-kali.
             */
            try {
                this.scanner.pause(
                    true
                );
            } catch (error) {
                console.warn(
                    'Unable to pause scanner:',
                    error
                );
            }


            await this.checkIn(
                value
            );
        },


        /*
         * ==========================================================
         * CHECK-IN REQUEST
         * ==========================================================
         */

        async checkIn(value) {

        let soundPlayed = false;


        try {

            const response =
                await fetch(
                    config.checkInUrl,
                    {
                        method: 'POST',

                        headers: {
                            'Accept':
                                'application/json',

                            'Content-Type':
                                'application/json',

                            'X-CSRF-TOKEN':
                                config.csrf,
                        },

                        body:
                            JSON.stringify({
                                qr_value:
                                    value,
                            }),
                    }
                );


            /*
            * Response Laravel
            * seharusnya JSON.
            */
            let data;


            try {

                data =
                    await response.json();

            } catch (_) {

                throw new Error(
                    'Server memberikan response yang tidak valid.'
                );
            }


            console.log(
                'Check-in response:',
                response.status,
                data
            );


            /*
            * Response bisnis yang kita kenal.
            */
            const businessStatuses = [
                'success',
                'limit_reached',
                'invalid',
            ];


            /*
            * Response HTTP yang memang
            * digunakan oleh proses check-in.
            */
            const allowedHttpErrors = [
                404,
                409,
                422,
            ];


            /*
            * Kalau server memberikan error
            * selain response bisnis normal,
            * lempar sebagai system error.
            */
            if (
                !response.ok &&
                !allowedHttpErrors.includes(
                    response.status
                )
            ) {

                throw new Error(
                    data.message ??
                    'Check-in gagal.'
                );
            }


            /*
            * Pastikan status yang diberikan
            * backend dikenali frontend.
            */
            if (
                !businessStatuses.includes(
                    data.status
                )
            ) {

                throw new Error(
                    data.message ??
                    'Status check-in tidak dikenal.'
                );
            }


            /*
            * Tampilkan hasil.
            */
            this.result =
                data;


            /*
            * Bunyi sesuai hasil.
            */
            await this.playStatusSound(
                data.status
            );


            soundPlayed =
                true;


        } catch (error) {

            console.error(
                'CHECK-IN ERROR:',
                error
            );


            this.result = {

                status:
                    'invalid',

                message:
                    error?.message ??
                    'Terjadi kesalahan saat check-in.',
            };


            /*
            * Jangan sampai sound
            * dimainkan dua kali.
            */
            if (!soundPlayed) {

                await this.playStatusSound(
                    'invalid'
                );
            }


        } finally {

            this.processing =
                false;
        }
    },


        /*
         * ==========================================================
         * SCAN NEXT QR
         * ==========================================================
         */

        async resumeScanner() {
            this.result = null;

            this.lastScannedValue =
                null;


            /*
             * Kalau scanner masih aktif dalam keadaan pause,
             * cukup resume.
             */
            try {
                this.scanner.resume();

                this.scannerReady =
                    true;

                this.scannerMessage =
                    'Point the camera at the QR code.';

                return;

            } catch (error) {
                console.warn(
                    'Resume failed, restarting scanner:',
                    error
                );
            }


            /*
             * Kalau resume gagal,
             * start ulang kamera.
             */
            try {
                await this.retryCamera();

            } catch (error) {
                console.error(
                    'Restart scanner failed:',
                    error
                );
            }
        },


        /*
         * ==========================================================
         * MANUAL INPUT
         * ==========================================================
         */

        async prepareAudio() {

        if (!this.audioContext) {

            const AudioContext =
                window.AudioContext
                || window.webkitAudioContext;

            if (!AudioContext) {
                return null;
            }

            this.audioContext =
                new AudioContext();
        }


        if (
            this.audioContext.state
            === 'suspended'
        ) {

            try {

                await this.audioContext.resume();

            } catch (_) {}

        }


        return this.audioContext;
    },


    playTone(
        frequency,
        duration = 0.15,
        delay = 0,
        volume = 0.12,
        type = 'sine'
    ) {

        const context =
            this.audioContext;


        if (
            !context
            || context.state !== 'running'
        ) {
            return;
        }


        const oscillator =
            context.createOscillator();

        const gain =
            context.createGain();


        const startTime =
            context.currentTime
            + delay;


        oscillator.type =
            type;

        oscillator.frequency
            .setValueAtTime(
                frequency,
                startTime
            );


        /*
        * Fade in/out supaya suara
        * tidak "klik".
        */
        gain.gain
            .setValueAtTime(
                0.001,
                startTime
            );

        gain.gain
            .exponentialRampToValueAtTime(
                volume,
                startTime + 0.01
            );

        gain.gain
            .exponentialRampToValueAtTime(
                0.001,
                startTime + duration
            );


        oscillator.connect(
            gain
        );

        gain.connect(
            context.destination
        );


        oscillator.start(
            startTime
        );

        oscillator.stop(
            startTime
            + duration
            + 0.02
        );
    },

    setupAudioUnlock() {

        const unlock =
            async () => {

                await this.prepareAudio();


                document.removeEventListener(
                    'pointerdown',
                    unlock
                );

                document.removeEventListener(
                    'keydown',
                    unlock
                );
            };


        document.addEventListener(
            'pointerdown',
            unlock
        );


        document.addEventListener(
            'keydown',
            unlock
        );
    },


    async playStatusSound(status) {

        await this.prepareAudio();


        if (
            !this.audioContext
            || this.audioContext.state
                !== 'running'
        ) {
            return;
        }


        /*
        * =====================================
        * SUCCESS
        *
        * Tiga nada naik:
        * "ding ding ding"
        * =====================================
        */
        if (status === 'success') {

            this.playTone(
                523.25,
                0.12,
                0,
                0.10,
                'sine'
            );

            this.playTone(
                659.25,
                0.12,
                0.10,
                0.11,
                'sine'
            );

            this.playTone(
                783.99,
                0.22,
                0.20,
                0.12,
                'sine'
            );

            return;
        }


        /*
        * =====================================
        * ALREADY CHECKED IN
        *
        * Dua beep medium.
        * =====================================
        */
        if (
            status ===
            'limit_reached'
        ) {

            this.playTone(
                440,
                0.18,
                0,
                0.11,
                'triangle'
            );

            this.playTone(
                440,
                0.18,
                0.24,
                0.11,
                'triangle'
            );

            return;
        }


        /*
        * =====================================
        * INVALID
        *
        * Nada turun seperti error.
        * =====================================
        */
        if (status === 'invalid') {

            this.playTone(
                330,
                0.18,
                0,
                0.10,
                'square'
            );

            this.playTone(
                220,
                0.28,
                0.16,
                0.10,
                'square'
            );
        }
    },

        async manualCheckIn() {
            const value =
                this.manualValue
                .trim();


            if (
                !value ||
                this.processing
            ) {
                return;
            }


            this.result =
                null;

            this.processing =
                true;
            
            try {

                this.scanner?.pause(
                    true
                );

            } catch (_) {}


            /*
             * CATATAN:
             *
             * Saat ini value yang dikirim adalah
             * guest00001.
             *
             * Backend harus mendukung manual guest code.
             */
            await this.checkIn(
                value
            );


            this.manualValue =
                '';
        },


        /*
         * ==========================================================
         * HELPER
         * ==========================================================
         */

        sleep(ms) {
            return new Promise(
                (resolve) => {
                    setTimeout(
                        resolve,
                        ms
                    );
                }
            );
        },
    };
};
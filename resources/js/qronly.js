window.qrExport = function(config) {
            return {

                downloadModal: false,

                processing: false,

                exportId: null,

                status: null,

                total: 0,

                processed: 0,

                progress: 0,

                processUrl: null,

                downloadUrl: null,

                error: null,


                async startExport() {

                    if (this.processing) {
                        return;
                    }

                    this.error = null;
                    this.processing = true;

                    try {

                        const response = await fetch(
                            config.startUrl, {
                                method: 'POST',

                                headers: {
                                    'Accept': 'application/json',

                                    'Content-Type': 'application/json',

                                    'X-CSRF-TOKEN': config.csrf,
                                },
                            }
                        );

                        const data =
                            await response.json();

                        if (!response.ok) {
                            throw new Error(
                                data.message ??
                                'Gagal memulai export.'
                            );
                        }

                        this.applyResponse(data);

                        if (
                            this.status !==
                            'completed'
                        ) {
                            await this.processLoop();
                        }

                    } catch (error) {

                        this.error =
                            error.message;

                        this.processing =
                            false;
                    }
                },


                async processLoop() {

                    while (
                        this.status ===
                        'processing'
                    ) {

                        try {

                            const response =
                                await fetch(
                                    this.processUrl, {
                                        method: 'POST',

                                        headers: {
                                            'Accept': 'application/json',

                                            'Content-Type': 'application/json',

                                            'X-CSRF-TOKEN': config.csrf,
                                        },
                                    }
                                );


                            /*
                             * Kalau export sedang diproses
                             * oleh tab lain, tunggu sebentar.
                             */
                            if (
                                response.status ===
                                409
                            ) {

                                await this.sleep(1000);

                                continue;
                            }


                            const data =
                                await response.json();


                            if (!response.ok) {

                                throw new Error(
                                    data.message ??
                                    'Batch export gagal.'
                                );
                            }


                            this.applyResponse(data);


                            /*
                             * Sedikit jeda agar shared hosting
                             * tidak dibombardir request.
                             */
                            await this.sleep(200);

                        } catch (error) {

                            this.error =
                                error.message;

                            this.processing =
                                false;

                            return;
                        }
                    }


                    this.processing = false;
                },


                async continueExport() {

                    if (
                        !this.processUrl ||
                        this.processing
                    ) {
                        return;
                    }

                    this.error = null;

                    this.processing = true;

                    await this.processLoop();
                },


                applyResponse(data) {

                    this.exportId =
                        data.id;

                    this.status =
                        data.status;

                    this.total =
                        data.total;

                    this.processed =
                        data.processed;

                    this.progress =
                        data.progress;

                    this.processUrl =
                        data.process_url;

                    this.downloadUrl =
                        data.download_url;

                    this.error = null;
                },


                sleep(ms) {

                    return new Promise(
                        resolve =>
                        setTimeout(
                            resolve,
                            ms
                        )
                    );
                },
            };
        };
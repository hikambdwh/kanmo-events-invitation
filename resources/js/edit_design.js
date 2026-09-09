window.designExport = function(config) {
    return {

        processing: false,

        status: null,

        processed: 0,

        total: 0,

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

                const response =
                    await fetch(
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


                this.apply(data);


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
                            'Render batch gagal.'
                        );
                    }


                    this.apply(data);

                    await this.sleep(300);


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


        apply(data) {

            this.status =
                data.status;

            this.processed =
                data.processed;

            this.total =
                data.total;

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

window.invitationDesigner = function(config) {

    return {

        previewValue: config.previewValue,

        x: Number(config.x),

        y: Number(config.y),

        size: Number(config.size),

        foreground: config.foreground,

        background: config.background,

        margin: Number(config.margin),


        dragging: false,

        pointerX: 0,

        pointerY: 0,

        startX: 0,

        startY: 0,


        qrUrl() {

            return (
                'https://api.qrserver.com/v1/create-qr-code/' +
                '?size=600x600' +
                '&data=' +
                encodeURIComponent(
                    this.previewValue
                ) +
                '&color=' +
                this.foreground +
                '&bgcolor=' +
                this.background +
                '&margin=' +
                this.margin
            );
        },


        startDrag(event) {

            this.dragging = true;

            this.pointerX =
                event.clientX;

            this.pointerY =
                event.clientY;

            this.startX =
                this.x;

            this.startY =
                this.y;
        },


        moveDrag(event) {

            if (!this.dragging) {
                return;
            }

            const rect =
                this.$refs
                .canvas
                .getBoundingClientRect();


            const deltaX =
                (
                    event.clientX -
                    this.pointerX
                ) /
                rect.width;


            const deltaY =
                (
                    event.clientY -
                    this.pointerY
                ) /
                rect.height;


            let newX =
                this.startX +
                deltaX;

            let newY =
                this.startY +
                deltaY;


            const qrHeightRatio =
                (
                    rect.width *
                    this.size
                ) /
                rect.height;


            newX = Math.max(
                0,
                Math.min(
                    1 - this.size,
                    newX
                )
            );


            newY = Math.max(
                0,
                Math.min(
                    1 - qrHeightRatio,
                    newY
                )
            );


            this.x = newX;
            this.y = newY;
        },


        endDrag() {
            this.dragging = false;
        },
    };
};
import { Html5Qrcode } from 'html5-qrcode'

export default function barcodeScanner({ state, modalId, cameraErrorMessage }) {
    return {
        state,
        modalId,
        scanner: null,
        errorMessage: null,
        cameraErrorMessage,

        init() {
            // The modal may already be open if it was triggered before this component finished loading.
            if (this.$el.closest('.fi-modal')?.classList.contains('fi-modal-open')) {
                this.startScanner()
            }
        },

        async startScanner() {
            if (this.scanner?.isScanning) {
                return
            }

            this.errorMessage = null

            try {
                this.scanner ??= new Html5Qrcode(this.modalId + '-viewport')
                await this.scanner.start(
                    { facingMode: 'environment' },
                    {
                        fps: 10,
                        // 1D barcodes are wide and short: the scan region must span
                        // almost the full viewfinder width or ZXing cannot decode them.
                        qrbox: (viewfinderWidth, viewfinderHeight) => ({
                            width: Math.floor(viewfinderWidth * 0.9),
                            height: Math.floor(viewfinderHeight * 0.6),
                        }),
                        videoConstraints: {
                            facingMode: 'environment',
                            width: { ideal: 1920 },
                            height: { ideal: 1080 },
                        },
                    },
                    (decodedText) => {
                        this.state = decodedText
                        this.$dispatch('close-modal', { id: this.modalId })
                    },
                    undefined,
                )
            } catch (err) {
                console.error('Barcode scanner failed to start', err)
                this.errorMessage = this.cameraErrorMessage
            }
        },

        async stopScanner() {
            if (! this.scanner) {
                return
            }

            try {
                if (this.scanner.isScanning) {
                    await this.scanner.stop()
                }
                this.scanner.clear()
            } catch {
                // Ignore cleanup errors
            }

            this.scanner = null
        },

        destroy() {
            this.stopScanner()
        },
    }
}

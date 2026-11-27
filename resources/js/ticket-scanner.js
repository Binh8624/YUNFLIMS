import { BrowserMultiFormatReader } from '@zxing/browser';

const config = document.getElementById('ticket-scanner-config');

if (config) {
    const video = document.getElementById('ticket-scan-video');
    const startButton = document.getElementById('ticket-camera-start');
    const stopButton = document.getElementById('ticket-camera-stop');
    const cameraMessage = document.getElementById('ticket-camera-message');
    const result = document.getElementById('ticket-scan-result');
    const details = document.getElementById('ticket-scan-details');
    const checkinForm = document.getElementById('ticket-scan-checkin');
    const reader = new BrowserMultiFormatReader();
    let controls = null;
    let lookupInProgress = false;
    let lastLookup = { code: null, at: 0 };

    const stopCamera = () => {
        controls?.stop();
        controls = null;
        video.srcObject?.getTracks().forEach((track) => track.stop());
        video.hidden = true;
        startButton.disabled = false;
        stopButton.disabled = true;
    };

    const parseTicketCode = (value) => {
        const content = value.trim();

        try {
            const url = new URL(content, window.location.origin);
            const match = url.pathname.match(/^\/tickets\/([^/]+)\/verify\/?$/);

            if (match) {
                return decodeURIComponent(match[1]);
            }
        } catch {
            return null;
        }

        return /^[A-Za-z0-9_-]{1,64}$/.test(content) ? content : null;
    };

    const lookupTicket = async (code) => {
        const now = Date.now();

        if (lookupInProgress || (lastLookup.code === code && now - lastLookup.at < 2500)) {
            return;
        }

        lookupInProgress = true;
        lastLookup = { code, at: now };
        stopCamera();
        cameraMessage.textContent = 'Đang gửi mã vé đến máy chủ để xác minh...';
        result.hidden = true;
        details.hidden = true;
        checkinForm.hidden = true;

        try {
            const response = await fetch(config.dataset.lookupUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': checkinForm.querySelector('input[name="_token"]').value,
                },
                body: JSON.stringify({ booking_code: code }),
            });
            const data = await response.json();

            result.textContent = data.message || 'Không thể xác minh mã vé.';
            result.className = `alert ${data.status === 'valid' ? 'alert-success' : 'alert-warning'}`;
            result.hidden = false;

            if (data.movie) {
                details.textContent = [
                    `Mã vé: ${data.booking_code}`,
                    `Phim: ${data.movie}`,
                    `Rạp / phòng: ${data.cinema} / ${data.room}`,
                    `Suất chiếu: ${new Date(data.showtime).toLocaleString('vi-VN')}`,
                    `Ghế: ${data.seats.join(', ')}`,
                ].join(' · ');
                details.hidden = false;
            }

            if (data.status === 'valid') {
                checkinForm.action = config.dataset.checkinBase.replace('__CODE__', encodeURIComponent(code));
                checkinForm.hidden = false;
            }

            cameraMessage.textContent = 'Kết quả đã được xác minh bởi máy chủ.';
        } catch (error) {
            cameraMessage.textContent = 'Không kết nối được máy chủ. Hãy thử lại hoặc nhập mã vé thủ công.';
            console.error('Ticket lookup failed.', error);
        } finally {
            lookupInProgress = false;
        }
    };

    startButton.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            cameraMessage.textContent = 'Trình duyệt không hỗ trợ camera. Hãy nhập mã vé thủ công.';
            return;
        }

        try {
            video.hidden = false;
            startButton.disabled = true;
            stopButton.disabled = false;
            cameraMessage.textContent = 'Đang yêu cầu quyền truy cập camera...';
            controls = await reader.decodeFromVideoDevice(undefined, video, (decoded) => {
                if (!decoded || lookupInProgress) {
                    return;
                }

                const code = parseTicketCode(decoded.getText());

                if (!code) {
                    cameraMessage.textContent = 'QR không chứa mã vé hợp lệ. Hãy thử mã QR khác.';
                    return;
                }

                lookupTicket(code);
            });
            cameraMessage.textContent = 'Đưa mã QR vào khung hình để quét.';
        } catch (error) {
            stopCamera();
            cameraMessage.textContent = 'Không thể mở camera. Hãy cấp quyền camera hoặc nhập mã vé thủ công.';
            console.error('Unable to start ticket camera scanner.', error);
        }
    });

    stopButton.addEventListener('click', () => {
        stopCamera();
        cameraMessage.textContent = 'Camera đã tắt. Bạn vẫn có thể nhập mã vé thủ công.';
    });
}

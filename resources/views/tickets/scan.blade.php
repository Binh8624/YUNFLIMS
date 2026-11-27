@extends('layouts.app')

@section('title', 'Quét vé - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="card bg-dark border-secondary text-white mx-auto" style="max-width: 760px">
        <div class="card-body p-4">
            <h1 class="h3 mb-2">Soát vé</h1>
            <p class="text-secondary">Quét QR hoặc nhập mã vé. Chỉ backend mới xác minh và cho phép check-in.</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <section class="mb-4" aria-labelledby="camera-heading">
                <h2 id="camera-heading" class="h5">Quét QR bằng camera</h2>
                <video id="ticket-scan-video" class="w-100 bg-black rounded" playsinline
                       aria-label="Hình ảnh camera quét mã QR" hidden></video>
                <div class="d-flex gap-2 mt-3">
                    <button id="ticket-camera-start" type="button" class="btn btn-outline-light">Bật camera</button>
                    <button id="ticket-camera-stop" type="button" class="btn btn-outline-secondary" disabled>Tắt camera</button>
                </div>
                <p id="ticket-camera-message" class="small text-secondary mt-2" role="status" aria-live="polite">
                    Cho phép trình duyệt truy cập camera khi được hỏi.
                </p>
            </section>

            <div id="ticket-scan-result" class="alert alert-secondary" role="status" aria-live="polite" hidden></div>
            <div id="ticket-scan-details" class="text-secondary mb-3" hidden></div>

            <form id="ticket-scan-checkin" method="POST" action="" hidden>
                @csrf
                <button id="ticket-scan-confirm" type="submit" class="btn btn-success">
                    Xác nhận check-in
                </button>
            </form>

            <hr class="border-secondary my-4">
            <form method="POST" action="{{ route('tickets.lookup') }}">
                @csrf
                <label for="ticket-code" class="form-label">Nhập mã vé thủ công</label>
                <div class="input-group">
                    <input id="ticket-code" type="text" name="booking_code" class="form-control"
                           value="{{ old('booking_code') }}" maxlength="64" autocomplete="off" required>
                    <button type="submit" class="btn btn-red">Kiểm tra vé</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div id="ticket-scanner-config"
     data-lookup-url="{{ route('tickets.lookup') }}"
     data-checkin-base="{{ route('tickets.checkin', ['bookingCode' => '__CODE__']) }}"
     hidden></div>
@endsection

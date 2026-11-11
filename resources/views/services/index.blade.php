@extends('layouts.app')

@section('title', 'Dịch vụ - YUNFLIMS Cinema')

@section('content')
<div class="container py-5">
    <section class="service-hero mb-5">
        <span class="hero-kicker">ĐỒNG HÀNH CÙNG YUNFLIMS</span>
        <h1 class="display-6 fw-bold mt-2 mb-3">Dịch vụ điện ảnh</h1>
        <p class="text-secondary mb-0" style="max-width: 700px;">
            Khám phá định dạng phòng chiếu và thông tin đặt vé. Các sản phẩm hay chương trình chưa có dữ liệu sẽ được thông báo rõ ràng thay vì hiển thị giá hoặc ưu đãi mẫu.
        </p>
    </section>

    <div class="row g-3 mb-5">
        <div class="col-6 col-lg">
            <a class="service-shortcut" href="#concessions"><i class="bi bi-cup-straw" aria-hidden="true"></i><span>Bắp nước</span></a>
        </div>
        <div class="col-6 col-lg">
            <a class="service-shortcut" href="#membership"><i class="bi bi-person-badge" aria-hidden="true"></i><span>Thành viên</span></a>
        </div>
        <div class="col-6 col-lg">
            <a class="service-shortcut" href="{{ route('services.vouchers') }}"><i class="bi bi-stars" aria-hidden="true"></i><span>Khuyến mãi</span></a>
        </div>
        <div class="col-6 col-lg">
            <a class="service-shortcut" href="#cinema-formats"><i class="bi bi-badge-3d" aria-hidden="true"></i><span>Phòng chiếu</span></a>
        </div>
        <div class="col-6 col-lg">
            <a class="service-shortcut" href="#support"><i class="bi bi-headset" aria-hidden="true"></i><span>Hỗ trợ</span></a>
        </div>
    </div>

    <div class="row g-4">
        <section class="col-md-6" id="concessions">
            <article class="service-card p-4 h-100">
                <span class="service-icon"><i class="bi bi-cup-straw" aria-hidden="true"></i></span>
                <h2 class="h4 mt-3">Bắp nước</h2>
                <div class="service-empty-state mt-3">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                    Danh mục sản phẩm và giá bắp nước chưa được cấu hình.
                </div>
            </article>
        </section>
        <section class="col-md-6" id="membership">
            <article class="service-card p-4 h-100">
                <span class="service-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                <h2 class="h4 mt-3">Thành viên</h2>
                <div class="service-empty-state mt-3">
                    <i class="bi bi-info-circle me-2" aria-hidden="true"></i>
                    Tài khoản hiện hỗ trợ hồ sơ và lịch sử vé. Điểm thưởng, cấp bậc và lịch sử tích điểm chưa được triển khai.
                </div>
                <a href="{{ route('register') }}" class="btn btn-outline-light mt-3">Tạo tài khoản</a>
            </article>
        </section>

        <section class="col-12" id="cinema-formats">
            <div class="d-flex justify-content-between align-items-end gap-3 mb-3">
                <div>
                    <span class="hero-kicker">TRẢI NGHIỆM MÀN ẢNH</span>
                    <h2 class="section-title mt-2 mb-0">Định dạng phòng chiếu</h2>
                </div>
                <a href="{{ route('showtimes.index') }}" class="btn btn-red">Xem lịch chiếu</a>
            </div>
            @if ($formats->isEmpty())
                <div class="cinema-panel p-4 text-secondary">Chưa có định dạng phòng chiếu đang hoạt động.</div>
            @else
                <div class="row g-3">
                    @foreach ($formats as $format)
                        <div class="col-6 col-md-4 col-lg-3">
                            <article class="feature-box h-100">
                                <i class="bi bi-badge-3d feature-icon" aria-hidden="true"></i>
                                <h3 class="h5 mt-3">{{ $format->name }}</h3>
                                <p class="text-secondary mb-0">{{ $format->rooms_count }} phòng hỗ trợ</p>
                            </article>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="col-12">
            <div class="d-flex justify-content-between align-items-end gap-3 mb-3">
                <div>
                    <span class="hero-kicker">ĐỊA ĐIỂM</span>
                    <h2 class="section-title mt-2 mb-0">Cụm rạp YUNFLIMS</h2>
                </div>
            </div>
            @if ($cinemas->isEmpty())
                <div class="cinema-panel p-4 text-secondary">Thông tin cụm rạp đang được cập nhật.</div>
            @else
                <div class="row g-3">
                    @foreach ($cinemas as $cinema)
                        <div class="col-md-6 col-lg-4">
                            <article class="cinema-panel cinema-location-card h-100 p-4">
                                <h3 class="h5">{{ $cinema->name }}</h3>
                                <p class="text-secondary"><i class="bi bi-geo-alt me-2" aria-hidden="true"></i>{{ $cinema->address }}, {{ $cinema->city }}</p>
                                <p class="small text-secondary mb-0">{{ $cinema->rooms_count }} phòng chiếu</p>
                                @if ($cinema->phone)
                                    <a class="small d-inline-block mt-2" href="tel:{{ $cinema->phone }}"><i class="bi bi-telephone me-2" aria-hidden="true"></i>{{ $cinema->phone }}</a>
                                @endif
                            </article>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="col-12" id="support">
            <div class="cinema-panel p-4 p-lg-5">
                <span class="hero-kicker">CÂU HỎI THƯỜNG GẶP</span>
                <h2 class="section-title mt-2 mb-4">Hỗ trợ đặt vé</h2>
                <div class="accordion accordion-flush service-faq" id="serviceFaq">
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqSeats" aria-expanded="false" aria-controls="faqSeats">Làm thế nào để chọn ghế?</button></h3>
                        <div id="faqSeats" class="accordion-collapse collapse" data-bs-parent="#serviceFaq"><div class="accordion-body">Chọn phim và suất chiếu, sau đó chọn tối đa 10 ghế còn trống. Giá được máy chủ tính theo hạng ghế và quy tắc giá đang áp dụng.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqHold" aria-expanded="false" aria-controls="faqHold">Ghế được giữ trong bao lâu?</button></h3>
                        <div id="faqHold" class="accordion-collapse collapse" data-bs-parent="#serviceFaq"><div class="accordion-body">Ghế được giữ tối đa 5 phút sau khi tạo đơn chờ thanh toán. Bạn có thể hủy đơn để giải phóng ghế sớm hơn.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqPayment" aria-expanded="false" aria-controls="faqPayment">Tôi có thể thanh toán trực tuyến không?</button></h3>
                        <div id="faqPayment" class="accordion-collapse collapse" data-bs-parent="#serviceFaq"><div class="accordion-body">Cổng thanh toán chưa được tích hợp. Website không thu tiền và không xác nhận vé cho đến khi có xác thực thanh toán phía máy chủ.</div></div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqTicket" aria-expanded="false" aria-controls="faqTicket">Tôi xem vé đã xác nhận ở đâu?</button></h3>
                        <div id="faqTicket" class="accordion-collapse collapse" data-bs-parent="#serviceFaq"><div class="accordion-body">Vé đã thanh toán được lưu trong Lịch sử đặt vé và hiển thị mã QR để xác minh.</div></div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

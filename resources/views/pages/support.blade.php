
@extends('layouts.app')

@section('title', 'Hỗ trợ khách hàng - YUNFLIMS')

@section('content')
<div class="container py-5">
    <div class="mx-auto" style="max-width:1000px;">
        <div class="text-center mb-5">
            <span class="badge bg-primary mb-3">HELP CENTER</span>
            <h1 class="text-white fw-bold">Trung tâm hỗ trợ</h1>
            <p class="text-secondary">Giải đáp những câu hỏi thường gặp khi sử dụng YUNFLIMS.</p>
        </div>

        <div class="accordion" id="supportAccordion">
            @php
                $faqs = [
                    ['Làm thế nào để đặt vé xem phim?', 'Truy cập trang Lịch chiếu hoặc chọn một bộ phim, sau đó chọn rạp, suất chiếu, ghế ngồi và thực hiện các bước xác nhận theo hướng dẫn.'],
                    ['Tôi có cần tài khoản để đặt vé không?', 'Bạn cần đăng nhập tài khoản để sử dụng các chức năng đặt vé và quản lý đơn đặt vé của YUNFLIMS.'],
                    ['Tại sao ghế tôi chọn bị người khác đặt?', 'Ghế có thể được người dùng khác chọn hoặc giữ trước đó. Hệ thống kiểm tra tình trạng ghế trước khi xác nhận để hạn chế đặt trùng. Bạn hãy chọn một ghế khác còn trống.'],
                    ['Tại sao đơn đặt vé của tôi bị hết hạn?', 'Đơn đặt vé chưa hoàn tất thanh toán trong thời gian giữ ghế có thể tự động hết hạn. Khi đó, ghế sẽ được giải phóng. Bạn có thể thực hiện đặt vé mới.'],
                    ['Làm thế nào để xem lại vé đã đặt?', 'Đăng nhập tài khoản và truy cập mục lịch sử đặt vé để kiểm tra các đơn hàng đã phát sinh.'],
                    ['Tôi có thể chọn bắp nước khi đặt vé không?', 'Có. Sau khi chọn ghế và tạo đơn đặt vé, hệ thống cho phép bạn lựa chọn sản phẩm bắp nước trước khi tiếp tục thanh toán.'],
                    ['Tôi có thể hủy đơn đặt vé không?', 'Bạn có thể sử dụng chức năng hủy nếu đơn hàng còn đủ điều kiện. Một số đơn đã được xử lý hoặc sản phẩm đi kèm đã bắt đầu chuẩn bị có thể không được phép hủy.'],
                    ['Tôi cần làm gì khi thanh toán nhưng đơn chưa cập nhật?', 'Hãy lưu lại thông tin giao dịch và mã đặt vé, sau đó liên hệ bộ phận hỗ trợ để được kiểm tra trạng thái đơn hàng.'],
                    ['Làm sao biết phim có giới hạn độ tuổi?', 'Mỗi phim có thông tin phân loại độ tuổi. Hãy kiểm tra nhãn phân loại trên trang thông tin phim trước khi đặt vé.'],
                    ['Tôi quên mật khẩu thì phải làm sao?', 'Sử dụng chức năng hỗ trợ tài khoản hoặc liên hệ quản trị viên để được hướng dẫn khôi phục quyền truy cập. Không cung cấp mật khẩu hiện tại cho bất kỳ ai.'],
                ];
            @endphp

            @foreach($faqs as $index => $faq)
                <div class="accordion-item border-0 mb-3 rounded overflow-hidden" style="background:#171525;">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faq{{ $index }}"
                                style="background:#171525;color:#fff;box-shadow:none;">
                            {{ $index + 1 }}. {{ $faq[0] }}
                        </button>
                    </h2>
                    <div id="faq{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#supportAccordion">
                        <div class="accordion-body text-secondary" style="background:#1d1a2d;">
                            {{ $faq[1] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card border-0 mt-5 text-center" style="background:#211d35;color:#fff;">
            <div class="card-body p-4">
                <h4 class="fw-bold">Bạn vẫn cần trợ giúp?</h4>
                <p class="text-secondary">Tham khảo trang liên hệ để biết thêm thông tin hỗ trợ.</p>
                <a href="{{ route('pages.contact') }}" class="btn btn-primary px-4">Liên hệ với chúng tôi</a>
            </div>
        </div>
    </div>
</div>
@endsection

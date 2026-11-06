<?php

namespace App\Http\Controllers;

class ServiceController extends Controller
{
    public function index()
    {
        return view('services.index');
    }

    public function vouchers()
    {
        $vouchers = [
            [
                'slug' => '2d',
                'name' => 'Voucher 2D',
                'price' => 115000,
                'description' => 'Có giá trị đổi vé xem phim 2D',
                'icon' => 'bi-badge-2d',
                'theme' => 'linear-gradient(135deg, #0f2b5b, #1f4e8c)',
            ],
            [
                'slug' => '3d',
                'name' => 'Voucher 3D',
                'price' => 145000,
                'description' => 'Có giá trị đổi vé xem phim 3D',
                'icon' => 'bi-badge-3d',
                'theme' => 'linear-gradient(135deg, #4a1010, #8f1d1d)',
            ],
            [
                'slug' => 'imax',
                'name' => 'Voucher IMAX',
                'price' => 180000,
                'description' => 'Có giá trị đổi vé xem phim IMAX',
                'icon' => 'bi-easel2',
                'theme' => 'linear-gradient(135deg, #102b2f, #1d5a63)',
            ],
            [
                'slug' => '4dx',
                'name' => 'Voucher 4DX',
                'price' => 180000,
                'description' => 'Có giá trị đổi vé xem phim 4DX',
                'icon' => 'bi-lightning-charge',
                'theme' => 'linear-gradient(135deg, #2a1a4a, #5b3a8c)',
            ],
            [
                'slug' => 'gold-class',
                'name' => 'Voucher Gold Class',
                'price' => 300000,
                'description' => 'Có giá trị đổi vé xem phim Gold Class',
                'icon' => 'bi-gem',
                'theme' => 'linear-gradient(135deg, #4a3410, #8c6a1d)',
            ],
            [
                'slug' => 'amour',
                'name' => "Voucher L'AMOUR",
                'price' => 300000,
                'description' => "Có giá trị đổi vé xem phim L'AMOUR",
                'icon' => 'bi-heart',
                'theme' => 'linear-gradient(135deg, #3a0f2b, #7a1d55)',
            ],
        ];

        return view('services.vouchers', compact('vouchers'));
    }

    public function giftCards()
    {
        $designs = [
            [
                'name' => 'Happy!',
                'description' => 'Mẫu thiệp rực rỡ với những lời chúc yêu thương.',
                'emoji' => '🎉',
                'theme' => 'linear-gradient(135deg, #f7b6d2, #e86fa8)',
                'text_color' => '#7a1440',
            ],
            [
                'name' => 'Yes you can!',
                'description' => 'Món quà tiếp thêm động lực cho người nhận.',
                'emoji' => '💪',
                'theme' => 'linear-gradient(135deg, #7cc4f5, #3f7bd9)',
                'text_color' => '#0d2f66',
            ],
            [
                'name' => 'YUNFLIMS Grid',
                'description' => 'Thiết kế ca-rô trẻ trung, đậm chất điện ảnh.',
                'emoji' => '🎬',
                'theme' => 'linear-gradient(135deg, #f6c453, #ef8f3c)',
                'text_color' => '#5a2c05',
            ],
            [
                'name' => 'Birthday',
                'description' => 'Dành tặng những bữa tiệc sinh nhật đáng nhớ.',
                'emoji' => '🎂',
                'theme' => 'linear-gradient(135deg, #c9a7f5, #8e5bd9)',
                'text_color' => '#3b1470',
            ],
        ];

        return view('services.gift-cards', compact('designs'));
    }
}

<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class InfoSoftVariantDictionary extends Model
{
    protected $table = 'infosoft_variant_dictionaries';

    protected $guarded = ['id'];

    /**
     * Normalize a raw color or size keyword to standard AR/EN and Hex
     */
    public static function normalize(string $type, ?string $rawKeyword): array
    {
        $raw = trim((string)$rawKeyword);
        if (empty($raw)) {
            return [
                'ar'  => '',
                'en'  => '',
                'hex' => null,
            ];
        }

        $entry = self::where('type', $type)
            ->where(function ($q) use ($raw) {
                $q->whereRaw('LOWER(raw_keyword) = ?', [strtolower($raw)])
                  ->orWhere('raw_keyword', $raw);
            })
            ->first();

        if ($entry) {
            return [
                'ar'  => $entry->normalized_name_ar,
                'en'  => $entry->normalized_name_en ?: $entry->normalized_name_ar,
                'hex' => $entry->hex_color,
            ];
        }

        // Fallback if not matched in dictionary
        $formatted = ucfirst(strtolower($raw));
        return [
            'ar'  => $formatted,
            'en'  => $formatted,
            'hex' => $type === 'color' ? '#6c757d' : null,
        ];
    }

    /**
     * Check if a raw string looks like a standard size
     */
    public static function isSize(string $str): bool
    {
        $str = strtoupper(trim($str));
        $commonSizes = ['XS', 'S', 'M', 'L', 'XL', '2XL', 'XXL', '3XL', 'XXXL', '4XL', '5XL', 'FREE', 'FREESIZE'];
        if (in_array($str, $commonSizes) || is_numeric($str)) {
            return true;
        }

        return self::where('type', 'size')
            ->whereRaw('LOWER(raw_keyword) = ?', [strtolower($str)])
            ->exists();
    }

    /**
     * Seed initial popular fashion colors and sizes
     */
    public static function seedDefaults(): void
    {
        if (self::count() > 0) {
            return;
        }

        $colors = [
            ['raw' => 'BLACK', 'ar' => 'أسود', 'en' => 'Black', 'hex' => '#000000'],
            ['raw' => 'BLK', 'ar' => 'أسود', 'en' => 'Black', 'hex' => '#000000'],
            ['raw' => 'اسود', 'ar' => 'أسود', 'en' => 'Black', 'hex' => '#000000'],
            ['raw' => 'أسود', 'ar' => 'أسود', 'en' => 'Black', 'hex' => '#000000'],

            ['raw' => 'WHITE', 'ar' => 'أبيض', 'en' => 'White', 'hex' => '#FFFFFF'],
            ['raw' => 'WHT', 'ar' => 'أبيض', 'en' => 'White', 'hex' => '#FFFFFF'],
            ['raw' => 'ابيض', 'ar' => 'أبيض', 'en' => 'White', 'hex' => '#FFFFFF'],
            ['raw' => 'أبيض', 'ar' => 'أبيض', 'en' => 'White', 'hex' => '#FFFFFF'],

            ['raw' => 'RED', 'ar' => 'أحمر', 'en' => 'Red', 'hex' => '#DC3545'],
            ['raw' => 'احمر', 'ar' => 'أحمر', 'en' => 'Red', 'hex' => '#DC3545'],
            ['raw' => 'أحمر', 'ar' => 'أحمر', 'en' => 'Red', 'hex' => '#DC3545'],

            ['raw' => 'BLUE', 'ar' => 'أزرق', 'en' => 'Blue', 'hex' => '#0D6EFD'],
            ['raw' => 'ازرق', 'ar' => 'أزرق', 'en' => 'Blue', 'hex' => '#0D6EFD'],
            ['raw' => 'أزرق', 'ar' => 'أزرق', 'en' => 'Blue', 'hex' => '#0D6EFD'],

            ['raw' => 'NAVY', 'ar' => 'كحلي', 'en' => 'Navy Blue', 'hex' => '#001F3F'],
            ['raw' => 'كحلي', 'ar' => 'كحلي', 'en' => 'Navy Blue', 'hex' => '#001F3F'],

            ['raw' => 'GREEN', 'ar' => 'أخضر', 'en' => 'Green', 'hex' => '#198754'],
            ['raw' => 'اخضر', 'ar' => 'أخضر', 'en' => 'Green', 'hex' => '#198754'],
            ['raw' => 'أخضر', 'ar' => 'أخضر', 'en' => 'Green', 'hex' => '#198754'],

            ['raw' => 'OLIVE', 'ar' => 'زيتي', 'en' => 'Olive', 'hex' => '#556B2F'],
            ['raw' => 'زيتي', 'ar' => 'زيتي', 'en' => 'Olive', 'hex' => '#556B2F'],

            ['raw' => 'YELLOW', 'ar' => 'أصفر', 'en' => 'Yellow', 'hex' => '#FFC107'],
            ['raw' => 'اصفر', 'ar' => 'أصفر', 'en' => 'Yellow', 'hex' => '#FFC107'],
            ['raw' => 'أصفر', 'ar' => 'أصفر', 'en' => 'Yellow', 'hex' => '#FFC107'],

            ['raw' => 'GREY', 'ar' => 'رمادي', 'en' => 'Grey', 'hex' => '#6C757D'],
            ['raw' => 'GRAY', 'ar' => 'رمادي', 'en' => 'Grey', 'hex' => '#6C757D'],
            ['raw' => 'رمادي', 'ar' => 'رمادي', 'en' => 'Grey', 'hex' => '#6C757D'],
            ['raw' => 'رصاصي', 'ar' => 'رمادي', 'en' => 'Grey', 'hex' => '#6C757D'],

            ['raw' => 'BEIGE', 'ar' => 'بيج', 'en' => 'Beige', 'hex' => '#F5F5DC'],
            ['raw' => 'بيج', 'ar' => 'بيج', 'en' => 'Beige', 'hex' => '#F5F5DC'],

            ['raw' => 'BROWN', 'ar' => 'بني', 'en' => 'Brown', 'hex' => '#795548'],
            ['raw' => 'بني', 'ar' => 'بني', 'en' => 'Brown', 'hex' => '#795548'],

            ['raw' => 'PINK', 'ar' => 'وردي', 'en' => 'Pink', 'hex' => '#E83E8C'],
            ['raw' => 'وردي', 'ar' => 'وردي', 'en' => 'Pink', 'hex' => '#E83E8C'],
            ['raw' => 'زهري', 'ar' => 'وردي', 'en' => 'Pink', 'hex' => '#E83E8C'],

            ['raw' => 'PURPLE', 'ar' => 'بنفسجي', 'en' => 'Purple', 'hex' => '#6F42C1'],
            ['raw' => 'بنفسجي', 'ar' => 'بنفسجي', 'en' => 'Purple', 'hex' => '#6F42C1'],
            ['raw' => 'موف', 'ar' => 'بنفسجي', 'en' => 'Purple', 'hex' => '#6F42C1'],
        ];

        foreach ($colors as $c) {
            self::create([
                'type'               => 'color',
                'raw_keyword'        => $c['raw'],
                'normalized_name_ar' => $c['ar'],
                'normalized_name_en' => $c['en'],
                'hex_color'          => $c['hex'],
            ]);
        }

        $sizes = [
            ['raw' => 'XS', 'ar' => 'XS', 'en' => 'XS'],
            ['raw' => 'S', 'ar' => 'S', 'en' => 'S'],
            ['raw' => 'M', 'ar' => 'M', 'en' => 'M'],
            ['raw' => 'L', 'ar' => 'L', 'en' => 'L'],
            ['raw' => 'XL', 'ar' => 'XL', 'en' => 'XL'],
            ['raw' => '2XL', 'ar' => '2XL', 'en' => '2XL'],
            ['raw' => 'XXL', 'ar' => '2XL', 'en' => '2XL'],
            ['raw' => '3XL', 'ar' => '3XL', 'en' => '3XL'],
            ['raw' => 'XXXL', 'ar' => '3XL', 'en' => '3XL'],
            ['raw' => '4XL', 'ar' => '4XL', 'en' => '4XL'],
            ['raw' => '5XL', 'ar' => '5XL', 'en' => '5XL'],
            ['raw' => 'FREE', 'ar' => 'مقاس موحد', 'en' => 'Free Size'],
            ['raw' => 'FREESIZE', 'ar' => 'مقاس موحد', 'en' => 'Free Size'],
        ];

        // Numerical shoe / clothes sizes: 28 to 48
        for ($i = 28; $i <= 48; $i += 2) {
            $sizes[] = ['raw' => (string)$i, 'ar' => (string)$i, 'en' => (string)$i];
        }

        foreach ($sizes as $s) {
            self::create([
                'type'               => 'size',
                'raw_keyword'        => $s['raw'],
                'normalized_name_ar' => $s['ar'],
                'normalized_name_en' => $s['en'],
                'hex_color'          => null,
            ]);
        }
    }
}

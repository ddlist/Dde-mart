<?php
// Temporary: re-host remote images locally (brand removal + independence).
// Resumable: skips cells already pointing at local storage.
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = Illuminate\Support\Facades\DB::class;

$targets = [
    ['sections', 'image_path'], ['categories', 'image_path'], ['brands', 'image_path'],
    ['products', 'image_path'], ['banners', 'image_path'], ['stores', 'image_path'],
    ['drivers', 'photo_path'], ['coupons', 'image_path'], ['gift_cards', 'image_path'],
    ['destinations', 'image_path'], ['cab_types', 'icon_path'],
    ['provider_services', 'image_path'], ['onboarding_slides', 'image_path'],
    ['stories', 'thumbnail'], ['stories', 'video_url'],
    ['advertisements', 'cover_path'], ['advertisements', 'profile_path'],
    ['provider_categories', 'image_path'], ['rental_vehicle_types', 'icon_path'],
    ['parcel_categories', 'image_path'], ['subscription_plans', 'image_path'],
];

$ctx = stream_context_create(['http' => ['timeout' => 25, 'follow_location' => 1, 'max_redirects' => 3]]);
$disk = Illuminate\Support\Facades\Storage::disk('public');
$moved = 0;
$failed = 0;
$done = 0;

foreach ($targets as [$table, $col]) {
    try {
        $vals = $db::table($table)->select($col)->where($col, 'like', 'http%')->distinct()->pluck($col);
    } catch (Throwable $e) {
        continue;
    }
    foreach ($vals as $url) {
        $done++;
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif', 'mp4', 'webm'], true)) {
            $ext = 'jpg';
        }
        $local = 'legacy/'.sha1($url).'.'.$ext;
        if (! $disk->exists($local)) {
            $bin = @file_get_contents($url, false, $ctx);
            if ($bin === false || strlen($bin) < 500 || strlen($bin) > 15728640) {
                $failed++;
                echo "FAIL {$url}\n";
                continue;
            }
            $disk->put($local, $bin);
        }
        $db::table($table)->where($col, $url)->update([$col => $local]);
        $moved++;
        if ($done % 100 === 0) {
            echo "progress {$done} moved={$moved} failed={$failed}\n";
        }
    }
}
echo "DONE moved={$moved} failed={$failed}\n";

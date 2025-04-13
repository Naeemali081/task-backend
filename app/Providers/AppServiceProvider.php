<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use FFMpeg\FFMpeg;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton('FFMpeg', function () {
            return FFMpeg::create([
//                'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
//                'ffprobe.binaries' => '/usr/bin/ffprobe',
                'ffmpeg.binaries'  => config('services.ffmpeg.ffmpeg_path'),
                'ffprobe.binaries' => config('services.ffmpeg.ffprobe_path'),
                'timeout'          => 3600, // The timeout for the underlying process
                'ffmpeg.threads'   => 12,   // The number of threads that FFmpeg should use
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Services;

use Johncms\Modules\Downloads\Application\DTO\FileMediaInfoDTO;

final class FileMediaInfoService
{
    public function build(string $fsPath, int $fileId, string $extension, array $existingScreenshots): FileMediaInfoDTO
    {
        return match (true) {
            in_array($extension, ['mp3', 'aac', 'm4a'], true)          => $this->buildAudio($fsPath, $existingScreenshots),
            in_array($extension, ['avi', 'webm', 'mov', 'mp4'], true)  => $this->buildVideo($fsPath, $existingScreenshots),
            in_array($extension, ['jpg', 'jpeg', 'gif', 'png'], true)  => $this->buildImage($fsPath, $fileId, $existingScreenshots),
            default                                                      => new FileMediaInfoDTO('other', [], $existingScreenshots, null),
        };
    }

    private function buildAudio(string $fsPath, array $screenshots): FileMediaInfoDTO
    {
        $getID3 = new \getID3();
        $getID3->encoding = 'cp1251';
        $getid = $getID3->analyze($fsPath);

        $mp3info = false;
        $tagsArray = [];
        if (! empty($getid['tags']['id3v2'])) {
            $tagsArray = $getid['tags']['id3v2'];
            $mp3info = true;
        } elseif (! empty($getid['tags']['id3v1'])) {
            $tagsArray = $getid['tags']['id3v1'];
            $mp3info = true;
        }

        $properties = [
            ['name' => __('Channels'),    'value' => ($getid['audio']['channels'] ?? '') . ' (' . ($getid['audio']['channelmode'] ?? '') . ')'],
            ['name' => __('Sample rate'), 'value' => ceil(($getid['audio']['sample_rate'] ?? 0) / 1000) . ' KHz'],
            ['name' => __('Bitrate'),     'value' => ceil(($getid['audio']['bitrate'] ?? 0) / 1000) . ' Kbit/s'],
            ['name' => __('Duration'),    'value' => $getid['playtime_string'] ?? ''],
        ];

        if ($mp3info) {
            foreach (['artist' => __('Artist'), 'title' => __('Title'), 'album' => __('Album'), 'genre' => __('Genre'), 'year' => __('Year')] as $tag => $label) {
                if (isset($tagsArray[$tag][0])) {
                    $properties[] = ['name' => $label, 'value' => iconv('windows-1251', 'UTF-8', $tagsArray[$tag][0])];
                }
            }
        }

        return new FileMediaInfoDTO('audio', $properties, $screenshots, null);
    }

    private function buildVideo(string $fsPath, array $screenshots): FileMediaInfoDTO
    {
        $getID3 = new \getID3();
        $getID3->encoding = 'cp1251';
        $getid = $getID3->analyze($fsPath);

        $properties = [];
        if (! empty($getid['video'])) {
            $video = $getid['video'];
            if (isset($video['fourcc_lookup'])) {
                $properties[] = ['name' => __('Codec'),      'value' => $video['fourcc_lookup']];
            }
            if (isset($video['frame_rate'])) {
                $properties[] = ['name' => __('Frame rate'), 'value' => $video['frame_rate'] . ' FPS'];
            }
            if (isset($video['bitrate'])) {
                $properties[] = ['name' => __('Bitrate'),    'value' => ceil($video['bitrate'] / 1000) . ' Kbit/s'];
            }
            if (isset($getid['playtime_string'])) {
                $properties[] = ['name' => __('Duration'),   'value' => $getid['playtime_string']];
            }
            if (isset($video['resolution_x'])) {
                $properties[] = ['name' => __('Resolution'), 'value' => $video['resolution_x'] . 'x' . ($video['resolution_y'] ?? 0) . 'px'];
            }
        }

        return new FileMediaInfoDTO('video', $properties, $screenshots, null);
    }

    private function buildImage(string $fsPath, int $fileId, array $screenshots): FileMediaInfoDTO
    {
        $screen = [
            'url'     => '/' . $fsPath,
            'preview' => '/downloads/preview/' . $fileId,
        ];

        $imageInfo = null;
        $info = @getimagesize($fsPath);
        if ($info !== false) {
            $imageInfo = ['width' => $info[0], 'height' => $info[1]];
        }

        return new FileMediaInfoDTO('image', [], array_merge($screenshots, [$screen]), $imageInfo);
    }
}

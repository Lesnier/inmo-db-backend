<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['image', 'video']);
        $url = '';

        if ($type === 'image') {
            // Local images 01.jpg to 10.jpg
            $num = str_pad($this->faker->numberBetween(1, 10), 2, '0', STR_PAD_LEFT);
            $url = "/properties/{$num}.jpg";
        } else {
            // Random YouTube videos
            $url = $this->faker->randomElement([
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'https://www.youtube.com/watch?v=ysz5S6PUM-U',
                'https://www.youtube.com/watch?v=HuTQAy5eX2Q',
            ]);
        }

        return [
            'model_id' => 0,
            'model_type' => 'library',
            'type' => $type,
            'url' => $url,
            'meta' => ['description' => $this->faker->sentence()],
            'position' => $this->faker->numberBetween(0, 10),
        ];
    }
}

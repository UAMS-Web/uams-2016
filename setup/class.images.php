<?php

//
// Installs the custom image sizes
//

class UAMS_Images
{
    /**
     * @var array<string, array{name: string, width: int, height: int, crop: bool, show: bool}>
     */
    // Coverage attributes no line to a property default, so its mutants are reported uncovered, not
    // surviving. 'defines the sizes the theme ships' asserts the whole value, so any change to it fails.
    // @pest-mutate-ignore: RemoveArrayItem,DecrementInteger,IncrementInteger,TrueToFalse,FalseToTrue
    // If `$show` is true it will appear in the image dropdown menu
    public $IMAGE_SIZES = [
        'mug-shot' => [
            'name' => 'Mug Shot',
            'width' => 150,
            'height' => 250,
            'crop' => true,
            'show' => true,
        ],
        'sidebar' => [
            'name' => 'Sidebar',
            'width' => 375,
            'height' => 9999,
            'crop' => false,
            'show' => true,
        ],
        'half' => [
            'name' => 'Half width',
            'width' => 375,
            'height' => 9999,
            'crop' => false,
            'show' => true,
        ],
        'full-content' => [
            'name' => 'Content area',
            'width' => 750,
            'height' => 9999,
            'crop' => false,
            'show' => true,
        ],
        'page' => [
            'name' => 'Full page',
            'width' => 1140,
            'height' => 9999,
            'crop' => false,
            'show' => true,
        ],
        'thimble' => [
            'name' => 'Thimble',
            'width' => 50,
            'height' => 50,
            'crop' => true,
            'show' => false,
        ],
        'thumbnail-large' => [
            'name' => 'Thumbnail large',
            'width' => 300,
            'height' => 300,
            'crop' => true,
            'show' => false,
        ],
        'rss' => [
            'name' => 'RSS',
            'width' => 108,
            'height' => 81,
            'crop' => true,
            'show' => false,
        ],
    ];

    public function __construct()
    {
        add_action('after_setup_theme', [$this, 'add_uams_image_sizes']);
        add_filter('image_size_names_choose', [$this, 'show_image_sizes']);
    }

    public function add_uams_image_sizes(): void
    {
        foreach ($this->IMAGE_SIZES as $name => $image) {
            add_image_size(
                $name,
                $image['width'],
                $image['height'],
                $image['crop']
            );
        }
    }

    /**
     * Add the theme image sizes to the image size dropdown.
     *
     * @param  mixed  $defaultSizes  The default sizes keyed by slug.
     * @return array<string, string> The size names keyed by slug.
     */
    public function show_image_sizes($defaultSizes): array
    {
        $defaultSizes = is_array($defaultSizes) ? $defaultSizes : [];
        $imagesToShow = array_filter($this->IMAGE_SIZES, fn (array $image): bool => ! empty($image['show']));

        foreach ($imagesToShow as $id => $image) {
            $imagesToShow[$id] = $image['name'];
        }

        // The value is untyped here (WordPress data or a mixed array), and the code handles it as before.
        // @phpstan-ignore return.type
        return array_merge($imagesToShow, $defaultSizes);
    }
}

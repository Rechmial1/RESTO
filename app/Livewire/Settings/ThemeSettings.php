<?php

namespace App\Livewire\Settings;

use App\Helper\Files;
use App\Models\GlobalSetting;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use App\Models\Restaurant;

class ThemeSettings extends Component
{

    use LivewireAlert, WithFileUploads;

    public $settings;
    public $themeColor;
    public $themeColorRgb;
    public $photo;
    public bool $showLogoText;
    public $upload_fav_icon_android_chrome_192;
    public $upload_fav_icon_android_chrome_512;
    public $upload_fav_icon_apple_touch_icon;
    public $upload_favicon_16;
    public $upload_favicon_32;
    public $favicon;
    public $savedImages;

    public function rules()
    {
        return [
            'photo' => 'nullable|image|max:1024',
            'upload_fav_icon_android_chrome_192' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'upload_fav_icon_android_chrome_512' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'upload_fav_icon_apple_touch_icon' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'upload_favicon_16' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'upload_favicon_32' => 'nullable|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
            'favicon' => 'nullable|file|mimes:ico|max:2048',
            'themeColor' => 'required',
        ];
    }

    public function mount()
    {
        $this->themeColor = $this->settings->theme_hex;
        $this->themeColorRgb = $this->settings->theme_rgb;
        $this->showLogoText = $this->settings->show_logo_text;
        $this->savedImages = [
            'upload_fav_icon_android_chrome_192' => $this->settings->upload_fav_icon_android_chrome_192,
            'upload_fav_icon_android_chrome_512' => $this->settings->upload_fav_icon_android_chrome_512,
            'upload_fav_icon_apple_touch_icon' => $this->settings->upload_fav_icon_apple_touch_icon,
            'upload_favicon_16' => $this->settings->upload_favicon_16,
            'upload_favicon_32' => $this->settings->upload_favicon_32,
            'favicon' => $this->settings->favicon,
        ];
    }

    public function updatedPhoto()
    {
        $this->validateLogo();
    }

    public function validateLogo()
    {
        // Clear any existing errors for this field
        $this->resetErrorBag('photo');

        if ($this->photo) {
            // Validate image dimensions
            $imageInfo = @getimagesize($this->photo->getRealPath());
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];

                // Only show error if dimensions are smaller than recommended (97 × 96)
                // Images larger than recommended size are acceptable and will not show an error
                if ($width < 97 || $height < 96) {
                    $this->addError('photo', __('modules.settings.imageDimensionsTooSmall', [
                        'width' => 97,
                        'height' => 96,
                        'currentWidth' => $width,
                        'currentHeight' => $height
                    ]));
                }
            }
        }
    }

    public function submitForm()
    {
        $this->validate();

        // Validate logo dimensions if it's an image
        $this->validateLogo();

        // Check if there are validation errors
        if ($this->getErrorBag()->has('photo')) {
            return;
        }

        $this->themeColorRgb = $this->hex2rgba($this->themeColor);

        $this->settings->theme_hex = $this->themeColor;
        $this->settings->theme_rgb = $this->themeColorRgb;
        $this->settings->show_logo_text = $this->showLogoText;
        $this->settings->save();


        if ($this->photo) {
            $this->settings->logo = Files::uploadLocalOrS3($this->photo, dir: 'logo', width: 150, height: 150);
        }

        $faviconBasePath = $this->settings->getFaviconBasePath();

        foreach (GlobalSetting::FAVICONS as $property => $filename) {
            if ($this->$property) {
                Files::deleteFile($this->settings->$property, $faviconBasePath);
                $this->settings->$property = Files::uploadLocalOrS3($this->$property, dir: $faviconBasePath, width: $filename['width'], height: $filename['height'], name: $filename['name']);
            }
        }


        $this->settings->save();

        session()->forget(['restaurant', 'timezone', 'currency']);

        $this->reset([
            'upload_fav_icon_android_chrome_192',
            'upload_fav_icon_android_chrome_512',
            'upload_fav_icon_apple_touch_icon',
            'upload_favicon_16',
            'upload_favicon_32',
            'favicon'
        ]);
        $this->redirect(route('settings.index') . '?tab=theme', navigate: true);

        $this->alert('success', __('messages.settingsUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function hex2rgba($color)
    {

        list($r, $g, $b) = sscanf($color, '#%02x%02x%02x');

        return $r . ', ' . $g . ', ' . $b;
    }

    public function deleteLogo()
    {
        if (is_null($this->settings->logo)) {
            return;
        }

        Files::deleteFile($this->settings->logo, 'logo');

        $this->settings->forceFill([
            'logo' => null,
        ])->save();

        session()->forget(['restaurant']);

        $this->redirect(route('settings.index') . '?tab=theme', navigate: true);
    }

    public function render()
    {
        return view('livewire.settings.theme-settings');
    }
}

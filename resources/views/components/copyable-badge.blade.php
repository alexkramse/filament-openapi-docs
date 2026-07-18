@props ([
    'text',
])

@php
    $copiedMessage = \Illuminate\Support\Js::from(__('filament-openapi-docs::ui.messages.copied_to_clipboard'));
    $copyFailedMessage = \Illuminate\Support\Js::from(__('filament-openapi-docs::ui.messages.copy_failed'));
    $xData = new \Illuminate\Support\HtmlString('{
        copied: false,
        copyTimeout: null,
        async copyToClipboard(text) {
            try {
                if (! navigator.clipboard?.writeText) {
                    throw new Error(\'Clipboard unavailable\');
                }

                await navigator.clipboard.writeText(text);
                this.copied = true;
                window.clearTimeout(this.copyTimeout);
                this.copyTimeout = window.setTimeout(() => this.copied = false, 1000);

                new FilamentNotification()
                    .title('.$copiedMessage.')
                    .success()
                    .send();
            } catch (error) {
                new FilamentNotification()
                    .title('.$copyFailedMessage.')
                    .danger()
                    .send();
            }
        },
    }');
    $xClick = new \Illuminate\Support\HtmlString('copyToClipboard('.\Illuminate\Support\Js::from($text).')');
@endphp

<x-dynamic-component
  :component="'filament::badge'"
  :attributes="$attributes->merge([
        'class' => 'foad-copyable-badge',
        'tooltip' => __('filament-openapi-docs::ui.tooltips.click_to_copy').': '.$text,
        'tabindex' => '0',
        'x-data' => $xData,
        'x-on:click' => $xClick,
        'x-on:keydown.enter.prevent' => '$el.click()',
        'x-on:keydown.space.prevent' => '$el.click()',
    ])"
>
  {{ $slot }}
</x-dynamic-component>

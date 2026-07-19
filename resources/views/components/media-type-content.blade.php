@props([
  'label',
  'contentType',
  'schema',
  'components' => [],
  'samples' => [],
])

<div class="foad-stack foad-stack-sm">
  <div class="foad-inline-list foad-inline-list-sm">
    <x-filament::badge color="gray" size="md">
      {{ __('filament-openapi-docs::ui.badges.type', ['type' => $contentType]) }}
    </x-filament::badge>
  </div>

  <div class="fi-sc-component">
    <div x-data="{ tab: 'tab2' }" class="fi-sc-tabs fi-contained">
      <x-filament::tabs
        :label="$label"
        class="fi-contained"
      >
        <x-filament::tabs.item
          @click="tab = 'tab2'"
          :alpine-active="'tab === \'tab2\''"
          active
        >
          {{ __('filament-openapi-docs::ui.labels.tree_view') }}
        </x-filament::tabs.item>

        <x-filament::tabs.item
          @click="tab = 'tab1'"
          :alpine-active="'tab === \'tab1\''"
        >
          {{ __('filament-openapi-docs::ui.labels.json') }}
        </x-filament::tabs.item>
      </x-filament::tabs>

      <div class="fi-sc-tabs-tab fi-active">
        <div x-show="tab === 'tab2'">
          <x-filament-openapi-docs::schema-tree
            :schema="$schema"
            :components="$components"
          />
        </div>

        <div x-show="tab === 'tab1'" x-cloak>
          <x-filament-openapi-docs::code-sample
            :label="$label"
            :content-type="$contentType"
            :samples="$samples"
          />
        </div>
      </div>
    </div>
  </div>
</div>

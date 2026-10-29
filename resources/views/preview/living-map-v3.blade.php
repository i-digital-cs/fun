@extends(auth()->check() ? 'layouts.dashboard' : 'layouts.app')

@section('title', __('living_map.title_living').' V3.3')

@section('content')
    <div class="mx-auto max-w-[1500px]">
        <x-living-map.stage
            mode="preview"
            :style-url="$styleUrl"
            :attribution="$attribution"
            :show-banner="true"
            :show-tour-controls="true"
        />
    </div>
@endsection

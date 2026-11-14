@if (session('sukses'))
    <div class="pesan sukses berikon" role="status">@include('partials.ikon', ['nama' => 'cek'])<span>{{ session('sukses') }}</span></div>
@endif

@if ($errors->any())
    <div class="pesan galat berikon" role="alert">
        @include('partials.ikon', ['nama' => 'silang'])
        @if ($errors->count() === 1)
            <span>{{ $errors->first() }}</span>
        @else
            <ul>
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
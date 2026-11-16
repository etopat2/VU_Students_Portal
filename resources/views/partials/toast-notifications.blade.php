@if ($errors->any() && ! session()->has('laravel-toaster-magic::messages'))
    @php
        \Devrabiul\ToastMagic\Facades\ToastMagic::error($errors);
    @endphp
@endif

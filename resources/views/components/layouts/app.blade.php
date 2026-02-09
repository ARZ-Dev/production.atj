<x-layouts.base>

  @guest
  {{ $slot }}
  @endguest

  <div id="layout-wrapper">
    @auth

    <main class="app-wrapper">
      <div class="container-fluid">
        
        @include('partials.page-title')
        @include('partials.header')
        @include('partials.sidebar')
        {{-- @include('partials.horizontal') --}}


        {{ $slot }}


        @include('partials.switcher')
        @include('partials.scroll-to-top')
        
        
      </div>
    </main>
    @include('partials.footer')
    @endauth
  </div>

</x-layouts.base>
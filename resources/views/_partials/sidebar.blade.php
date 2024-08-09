<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('web-scrap.index') }}" class="brand-link elevation-4">
        <span class="brand-text font-weight-light">Scraper Dashboard</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('web-scrap.index') }}"
                        class="nav-link {{ Route::is('web-scrap.index') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-globe"></i>
                        <p>
                            Web News Scraper
                        </p>
                    </a>
                </li>
                {{-- <li class="nav-item">
                    <a href="" class="nav-link {{ Route::is('socmed-scrap.index') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-th"></i>
                        <p>
                            Social Media Scraper
                        </p>
                    </a>
                </li> --}}
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

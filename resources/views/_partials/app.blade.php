<!DOCTYPE html>
<html lang="en">

@section('head')
    @include('_partials.head')
@show

<style>
    /* Spinner Wrapper Styling */
    .spinner-wrapper {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        /* background-color: rgba(0, 0, 0, 0.5); */
        /* Darkened background */
        z-index: 9999;
        /* Ensures it appears above all other content */
    }
</style>

<body class="hold-transition sidebar-mini layout-navbar-fixed">
    <!-- Site wrapper -->
    <div class="wrapper">
        <!-- Navbar -->
        @section('navbar')
            @include('_partials.navbar')
        @show
        <!-- /.navbar -->
        <!-- Main Sidebar Container -->
        @section('sidebar')
            @include('_partials.sidebar')
        @show
        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper" id="content-body">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>{{ $title }}</h1>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>
            <!-- Main content -->
            @yield('content')



            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->
        @section('footer')
            @include('_partials.footer')
        @show

    </div>
    <!-- ./wrapper -->
    @section('js')
        @include('_partials.js')
    @show

</body>

</html>

<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<link rel="icon" type="image/png" href="/img/favicon.ico">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

	<title>Findeem</title>

	<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />
    <meta name="viewport" content="width=device-width" />


    <!-- Bootstrap core CSS     -->
    <link href="/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Animation library for notifications   -->
    <link href="/css/animate.min.css" rel="stylesheet"/>

    <!--  Light Bootstrap Table core CSS    -->
    <link href="/css/light-bootstrap-dashboard.css?v=1.4.0" rel="stylesheet"/>


    <!--  CSS for Demo Purpose, don't include it in your project     -->
    <link href="/css/demo.css" rel="stylesheet" />


    <!--     Fonts and icons     -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,700,300' rel='stylesheet' type='text/css'>
    <link href="/css/pe-icon-7-stroke.css" rel="stylesheet" />

    <style>
        a {
            color: #944dec;
        }
        a:hover {
            color: #a87ae0;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar" data-color="purple" data-image="/img/sidebar-5.jpg">

    <!--   you can change the color of the sidebar using: data-color="blue | azure | green | orange | red | purple" -->


    	<div class="sidebar-wrapper">
            <div class="logo">
                <a href="/admin" class="simple-text">
                    Findeem Admin
                </a>
            </div>

            <ul class="nav">
                <li @if (app('request')->path() === 'admin/users') class="active" @endif>
                    <a href="/admin/users">
                        <i class="fa fa-users"></i>
                        <p>Utenti</p>
                    </a>
                </li>
                <li @if (app('request')->path() === 'admin/business') class="active" @endif>
                    <a href="/admin/business">
                        <i class="fa fa-briefcase"></i>
                        <p>Business</p>
                    </a>
                </li>
                <li @if (app('request')->path() === 'admin/categories') class="active" @endif>
                    <a href="/admin/categories">
                        <i class="fa fa-sitemap"></i>
                        <p>Categorie</p>
                    </a>
                </li>
                <li @if (app('request')->path() === 'admin/events') class="active" @endif>
                    <a href="/admin/events">
                        <i class="fa fa-calendar"></i>
                        <p>Eventi</p>
                    </a>
                </li>
                <li @if (app('request')->path() === 'admin/groups') class="active" @endif>
                    <a href="/admin/groups">
                        <i class="fa fa-sitemap"></i>
                        <p>Gruppi</p>
                    </a>
                </li>
                <li>
                    <a href="/admin/events-import">
                        <i class="fa fa-cloud-upload"></i>
                        <p>Carica eventi</p>
                    </a>
                </li>
                <li @if (app('request')->path() === 'admin/reports') class="active" @endif>
                    <a href="/admin/reports">
                        <i class="fa fa-warning"></i>
                        <p>Segnalazioni</p>
                    </a>
                </li>
                <!--<li @if (app('request')->path() === 'admin/translations') class="active" @endif>
                    <a href="/admin/translations">
                        <i class="fa fa-language"></i>
                        <p>Traduzioni</p>
                    </a>
                </li>-->
                <li style="margin:5px 29px"><label class="tree-toggler nav-header"><p style="margin-left:0px">TRADUZIONI</p></label>
                <i class="fa fa-language"></i>
                                <ul class="nav nav-list tree" style="margin-top:0px">
                                    <li @if (app('request')->path() === 'admin/translations/it') class="active" @endif><a href="/admin/translations/it">
                        
                        <p>En-It</p>
                    </a></li>
                    </ul>

                    <ul class="nav nav-list tree" style="margin-top:0px">
                                    <li @if (app('request')->path() === 'admin/translations/it') class="active" @endif><a href="/admin/translations/de">
                        
                        <p>En-De</p>
                    </a></li>
                    </ul>


                                    </li>
                <li @if (app('request')->path() === 'admin/logs') class="active" @endif>
                    <a href="/admin/logs">
                        <i class="fa fa-log"></i>
                        <p>Logs</p>
                    </a>
                </li>
           
    	</div>
    </div>

    <div class="main-panel">
		<nav class="navbar navbar-default navbar-fixed">
            <div class="container-fluid">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navigation-example-2">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">{{ $title ?? 'Findeem Admin' }}</a>
                </div>
                <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav navbar-left">
                        <li>
                            <a href="/admin">
                                <i class="fa fa-dashboard"></i>
								<p class="hidden-lg hidden-md">Dashboard</p>
                            </a>
                        </li>
                    </ul>

                    <ul class="nav navbar-nav navbar-right">
                        <li>
                            <a href="/admin/logout">
                                <p>Logout</p>
                            </a>
                        </li>
						<li class="separator hidden-lg hidden-md"></li>
                    </ul>
                </div>
            </div>
        </nav>

        @yield('content')

        <footer class="footer">
            <div class="container-fluid hide">
                <nav class="pull-left">
                    <ul>
                        <li>
                            <a href="#">
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                Company
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                Portfolio
                            </a>
                        </li>
                        <li>
                            <a href="#">
                               Blog
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </footer>


    </div>
</div>


</body>

    <!--   Core JS Files   -->
    <script src="/js/jquery.3.2.1.min.js" type="text/javascript"></script>
	<script src="/js/bootstrap.min.js" type="text/javascript"></script>

	<!--  Charts Plugin -->
	<script src="/js/chartist.min.js"></script>

    <!--  Notifications Plugin    -->
    <script src="/js/bootstrap-notify.js"></script>

    <!--  Google Maps Plugin    -->
    <!-- <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=YOUR_KEY_HERE"></script> -->

    <!-- Light Bootstrap Table Core javascript and methods for Demo purpose -->
	<script src="/js/light-bootstrap-dashboard.js?v=1.4.0"></script>

	<!-- Light Bootstrap Table DEMO methods, don't include it in your project! -->
    <script src="/js/demo.js"></script>
    <script>
    $(document).ready(function () {
    $('label.tree-toggler').click(function () {
        $(this).parent().children('ul.tree').toggle(300);
    });
});
    </script>
    @section('page_scripts')
    @show

</html>

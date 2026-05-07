<!-- Main Header -->
<header class="main-header">
    
    <!-- Logo -->
    <a href="@if(Admin::user()->can('FactoryAdmin')) {{ factoryadmin_url('/') }} @else {{ admin_url('/') }} @endif" class="logo" @if(Admin::user()->can('FactoryAdmin')) target='_blank' @endif>
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span class="logo-mini">{!! config('admin.logo-mini', config('admin.name')) !!}</span>
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg">{!! config('admin.logo', config('admin.name')) !!}</span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
        </a>
		<!-- old reminder -->
        <ul class="nav navbar-nav hidden-sm visible-lg-block">
        {!! Admin::getNavbar()->render('left') !!}
        </ul>

        <!-- Navbar Right Menu -->
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
                <!-- reminder -->
				<?php
				   $reminderdata = Admin::reminderdata();
				   $number = 0;
				   foreach($reminderdata as $item){
						$number += $item['count'];
				   }
				?>

				<li class='dropdown reminder notifications-menu' @if($number == 0)style='display:none;'@endif>
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true" style='float: left;background-color: transparent;background-image: none;padding: 15px 15px;font-family: fontAwesome;color: #fff;'>
						<i class="fa fa-bullhorn"></i>
						<i style="position: absolute;color: white;font-size: 12px;font-weight:bold;background-color: red;padding-left:5px;padding-right:5px;height: 17px;line-height: 17px;left: 25px;top: 10px;text-align: center;-webkit-border-radius: 18px;border-radius: 18px;" class='reminder-number'>{{$number}}</i>
					</a>
					<ul class="dropdown-menu">
					  <li>
						<!-- inner menu: contains the actual data -->
						<div class="slimScrollDiv" style="position: relative; overflow: hidden; width: auto; height: 200px;">
						  <ul class="menu reminder-menu" style="overflow: hidden; width: 100%; height: 200px;">
						  @foreach($reminderdata as $key=>$item)
						  <li>
							<a href="{{$item['url']}}">
							  <i class="fa {{$item['icon']}} text-aqua"></i>{{$key}}<i style="float:right;color: white;font-size: 12px;font-weight:bold;background-color: red;padding-left:5px;padding-right:5px;height: 17px;line-height: 17px;text-align: center;-webkit-border-radius: 18px;border-radius: 18px;">{{$item['count']}}</i>
							</a>
						  </li>
						  @endforeach
						  </ul>
						  <div class="slimScrollBar" style="background: rgb(0, 0, 0); width: 3px; position: absolute; top: 0px; opacity: 0.4; display: none; border-radius: 7px; z-index: 99; right: 1px; height: 139.373px;"></div><div class="slimScrollRail" style="width: 3px; height: 100%; position: absolute; top: 0px; display: none; border-radius: 7px; background: rgb(51, 51, 51); opacity: 0.2; z-index: 90; right: 1px;"></div></div>
					  </li>
					</ul>			
				</li>
				<!-- reminder -->
				
                {!! Admin::getNavbar()->render() !!}

                <!-- User Account Menu -->
                <li class="dropdown user user-menu">
                    <!-- Menu Toggle Button -->
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <!-- The user image in the navbar-->
                        <img src="{{ Admin::user()->avatar }}" class="user-image" alt="User Image">
                        <!-- hidden-xs hides the username on small devices so only the image appears. -->
                        <span class="hidden-xs">{{ Admin::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <!-- The user image in the menu -->
                        <li class="user-header">
                            <img src="{{ Admin::user()->avatar }}" class="img-circle" alt="User Image">

                            <p>
                                {{ Admin::user()->name }}
                                <small>Member since admin {{ Admin::user()->created_at }}</small>
                            </p>
                        </li>
                        <li class="user-footer">
                            <div class="pull-left">
                                <a href="{{ admin_url('auth/setting') }}" class="btn btn-default btn-flat">{{ trans('admin.setting') }}</a>
                            </div>
                            <div class="pull-right">
                                <a href="{{ admin_url('auth/logout') }}" class="btn btn-default btn-flat">{{ trans('admin.logout') }}</a>
                            </div>
                        </li>
                    </ul>
                </li>
                <!-- Control Sidebar Toggle Button -->
                {{--<li>--}}
                    {{--<a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>--}}
                {{--</li>--}}
            </ul>
        </div>
    </nav>
</header>
<script>
setInterval("getReminderCount()",10000*6*5);
function getReminderCount() {
	$.ajax({
	   type:'get',
	   url:'/factoryadmin/api/getremindercount',
	   data:'_token = <?php echo csrf_token() ?>',
	   success:function(data) {
		    data = eval("("+data+")");
			$('.reminder-menu').html();
			var html = '';
			var count = 0;
		    $.each(data,function(index,item){
			   html += '<li><a href="'+item.url+'"><i class="fa '+item.icon+' text-aqua"></i>'+item.name+'<i style="float:right;color: white;font-size: 12px;font-weight:bold;background-color: red;padding-left:5px;padding-right:5px;height: 17px;line-height: 17px;text-align: center;-webkit-border-radius: 18px;border-radius: 18px;">'+item.count+'</i></a></li>';
			   count += item.count;
			});
		    if(count == 0)$('.reminder').hide();
		    else{
				$('.reminder-number').html(count);
				$('.reminder').show();
				$('.reminder-menu').html(html);
		    }
	   }
	});
}
</script>
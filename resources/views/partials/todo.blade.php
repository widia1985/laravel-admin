<?php
$reminderdata = Admin::reminderdata();
?>
@if(count($reminderdata)>0)
<div class="alert alert-warning alert-dismissable remindar-header" style="opacity:0.9;position:fixed;z-index:10000;width:800px;left: 50%; margin-left: -400px;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h4>Reminder&nbsp;&nbsp;&nbsp;<svg aria-hidden="true" class="SVGInline-svg SVGInline--cleaned-svg SVG-svg Icon-svg Icon--info-svg Icon--hoverable-svg " height="12" width="12" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
														    <path fill="red" d="M9 8a1 1 0 0 0-1-1H5.5a1 1 0 1 0 0 2H7v4a1 1 0 0 0 2 0zM4 0h8a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4zm4 5.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z" fill-rule="evenodd"></path>
														</svg></h4>
		<table>
		   <tr>
		     @foreach($reminderdata as $key=>$item)
		     <td style="border-right : thin dashed white;padding-right:20px;padding-left:20px;font-size:14px;">
			    <div>
				   <a href='{{$item['url']}}'><font color="red"><strong>{{$item['count']}}</strong></font> {{$key}}</a>
				</div>
			 </td>
			 @endforeach
		   </tr>
		</table>
</div>
<script>
   setInterval("remindar()",10000*2);
   function remindar(){
	   if($(".remindar-header").is(":hidden")){
		   $('.remindar-header').slideDown();
	   }
	   else{
		   $('.remindar-header').slideUp();
	   }
   }
</script>
@endif
<html>
<head>
<script src="http://localhost:7893/resources/js/jquery.min.js"></script>
<script src="http://localhost:7893/resources/js/jquery-ui/ui/jquery-ui.js"></script>
<style>
.outer {
	width: 500px;
	height: 10px;
	border: solid #222 2px;
	display: table-cell;
	vertical-align: middle;
	padding: 1px 3px;
	border-radius: 5px;
	overflow: hidden;
}

.inner {
	width: 0%;
	height: 2px;
	background-color: #777;

}

</style>
<script type="text/javascript">
function stepProgressBar(amount) {
	var width = $('div.inner').css('width');
	width = str_replace('px','', width);
	if (width < 500) {
		$('div.inner').animate({width: '+=' + amount + '%'},200);
		$('#progressNumber').html(((width/5)+20)+"%");
	}
}

function str_replace(needle, replace, haystack) {
  return haystack.replace(new RegExp(needle, 'g'), replace);
}
</script>
</head>
<body>

<div class = "outer">
	<div class = "inner">
	</div>
</div>
<div id='progressNumber'>0%</div>

<div>
<button onclick="stepProgressBar(20);">Progress</button>
</div>
</body>
</html>
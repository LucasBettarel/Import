<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Zest - Import Sap</title>
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <nav class="navbar navbar-inverse navbar-fixed-top">
    <div class="coontainer-fluid">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="http://localhost:8000/input/">
          <img alt="Schneider Electric" src="img/logo.png">
        </a>
      </div>

    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
        <ul class="nav navbar-nav">
          <li><a href="http://localhost:8000/input/"><i class="glyphicon glyphicon-pencil"></i> Manhours Input</a></li>
          <li><a href="http://localhost:8000/zest/"><i class="glyphicon glyphicon-glass"></i> Tutorial</a></li>
        </ul>
      </div><!-- /.navbar-collapse -->
    </div>
  </nav>

  <?php
	require_once 'TO.php';

	ini_set('max_execution_time', 300);

	$connect = new sapConnection();

	$connect->setUp();
	$connect->sapConnect();
	$date = $connect->getDate();
	if (!is_null($date) and $date != 0 and $date != ""){
		$date = date_create_from_format('Y-m-d',$connect->getDate());
		$date = date_format($date,'Ymd');
		$res = $connect->readTable($date);
		if (!$res[0] or ($connect->getDataSize($res)) == 0){
			$log = "<div class='alert alert-warning col-md-4 col-md-offset-1' role='alert'><h4><i class='glyphicon glyphicon-thumbs-down'></i> Oups !</h4><p>Something went wrong, please try again...</p></div>";
		}
		else{
			$log = "<div class='alert alert-success col-md-4 col-md-offset-1' role='alert'><h4><i class='glyphicon glyphicon-thumbs-up'></i> The import was realized with success ! </h4><p>Please check the data and proceed to save in database.</p></div>";
		}
	}
	else{
		$log = "<div class='alert alert-danger col-md-4 col-md-offset-1' role='alert'><h4><i class='glyphicon glyphicon-warning-sign'></i> Error ! </h4><p>The date you provided contains an error, please check.</p></div>";
	}

	$connect->sapPersist($res);		

  ?>

  <div id="content" class="col-md-12">
  	<div class="col-md-3">
  		<h1>Import</h1>
  	</div>
  	<?php
		echo $log;
	?>
  	<div class="well col-md-3 col-md-offset-1">
	  	<div class="col-md-7">
	  		<h5><i class="glyphicon glyphicon-time"></i> 
	  			<?php
	  				echo $connect->getDate();
	  			?>
	  		</h5>
	  		<h5>
	  			<i class="glyphicon glyphicon-list-alt"></i>
	  			<?php
	  				echo $connect->getDataSize($res);
	  			?> 
	  			entries
	  		</h5>
	  	</div>
	  	<div class="col-md-5 persist">
	  		<?php
	  			if(!$res[0] or ($connect->getDataSize($res)) == 0){
	  				echo "<a href='http://localhost:8000/input/extimport' class='btn btn-warning btn-lg' role='button'><i class='glyphicon glyphicon-repeat'></i> Return</a>";
	  			}
	  			else{
	  				echo "<a href='http://localhost:8000/input' class='btn btn-primary btn-lg' role='button'><i class='glyphicon glyphicon-cloud'></i> Return</a>";
	  			}
	  		?>
	  	</div>
  	</div>
  	<div class="table-responsive col-md-12">
        <table class="table table-hover table-striped">
          <thead>
            <tr>

  <?php
  	
	$connect->displayTable($res);
	$connect->sapClose();

  ?>

		  </tbody>
		</table>
	</div>
  </div>
</body>
</html>
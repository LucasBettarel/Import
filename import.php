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

  	<div id="content" class="col-md-12">
	  	<div class="col-md-3">
	  		<h1>Import</h1>
	  	</div>

		<?php
		require_once 'TO.php';
		ini_set('max_execution_time', 300);
		$connect = new sapConnection();

		//step 0 : check if date is valid (not null + not=0 +checkdate true)
		$raw_date = $connect->getDate();
		$date = date_create_from_format('Y-m-d',$raw_date);
		$datesap = date_format($date,'Ymd');
		$date_year = date_format($date,'Y');
		$date_month = date_format($date,'m');
		$date_day = date_format($date,'d');
		
		if (!is_null($raw_date) and $raw_date != 0 and $raw_date != "" and checkdate($date_month, $date_day, $date_year)){
	
			//step 1: check if data exist in SAP import
			if (!$connect->checkImportExist($raw_date)){

				//step 2 : connect to SAP
				$connect->setUp();
				if ($connect->sapConnect()){

					//step 3 : read Table (not error or not empty result)
					$res = $connect->readTable($datesap);
					if (!$res[0] or ($connect->getDataSize($res)) == 0){
						//error on read data Table
						echo "<div class='alert alert-warning col-md-4 col-md-offset-1' role='alert'>
								<h4><i class='glyphicon glyphicon-thumbs-down'></i> Oups !</h4>
								<p>Error on importing data from SAP, please try again...</p>
								<p><a href='http://localhost:8000/input/extimport' class='btn btn-danger btn-lg' role='button'>
									<i class='glyphicon glyphicon-repeat'></i> Return</a>
								</p>
							  </div>";
					}
					else{

						//step 4 : split/prepare array and consolidate
						$res[1] = $connect->consolidateData($res[1], $datesap);

						//step 5 : persist data to phpmyadmin
						$saving = $connect->sapPersist($res[1], $raw_date);

						if(!$saving){
							echo "<div class='alert alert-danger col-md-4 col-md-offset-1' role='alert'>
									<h4><i class='glyphicon glyphicon-thumbs-down'></i> Oups ! </h4>
									<p>Something went wrong while saving data, please check.</p>
									<p><a href='http://localhost:8000/input/extimport' class='btn btn-danger btn-lg' role='button'>
											<i class='glyphicon glyphicon-repeat'></i> Return</a>
									</p>
								  </div>";
						}
						else{
							echo "<div class='alert alert-success col-md-4 col-md-offset-1' role='alert'>
									<h4><i class='glyphicon glyphicon-thumbs-up'></i> The import was realized with success ! </h4>
									<p>Feel free to check the data then go back to work...</p>
									<p><a href='http://localhost:8000/input/extimport' class='btn btn-primary btn-lg' role='button'>
											<i class='glyphicon glyphicon-cloud'></i> Return</a>
									</p>
								  </div>";
						}


						//step 6 : display table (both if persist success or error)
						echo "<div class='well col-md-3 col-md-offset-1'>
							  		<h5><i class='glyphicon glyphicon-time'></i>".$raw_date."</h5>
							  		<h5><i class='glyphicon glyphicon-list-alt'></i>".$connect->getDataSize($res)."	entries</h5>
							  	</div>
							  	<div class='table-responsive col-md-12'>
							        <table class='table table-hover table-striped'>
							          <thead>
							            <tr>";

						$connect->displayTable($res);
						
						echo "</tbody></table></div>";
					}	
				}
				//end step 2
				$connect->sapClose();
			}
			else{
				//error step 1
				echo "<div class='alert alert-danger col-md-4 col-md-offset-1' role='alert'>
						<h4><i class='glyphicon glyphicon-warning-sign'></i> Error ! </h4>
						<p>The import is already saved in database for this date</p>
						<p><a href='http://localhost:8000/input/extimport' class='btn btn-danger btn-lg' role='button'>
							<i class='glyphicon glyphicon-repeat'></i> Return</a>
						</p>
					  </div>";
			}
		}
		else{
		//error step 0
			echo "<div class='alert alert-danger col-md-4 col-md-offset-1' role='alert'>
					<h4><i class='glyphicon glyphicon-warning-sign'></i> Error ! </h4>
					<p>The date you provided contains an error, please check.</p>
					<p><a href='http://localhost:8000/input/extimport' class='btn btn-danger btn-lg' role='button'>
							<i class='glyphicon glyphicon-repeat'></i> Return</a>
					</p>
				  </div>";
		}
		?>
  	</div> <!-- div content -->
</body>
</html>
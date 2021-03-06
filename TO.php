<?php
require_once 'sap_config.php';
require_once 'spyc.php';
extension_loaded("sapnwrfc");
global $SAP_CONFIG;

class sapConnection
{
    public function setUp() {
        global $SAP_CONFIG;
        $this->config = Spyc::YAMLLoad($SAP_CONFIG);
    }

    public function sapConnect() {
        try {
            $this->conn = new sapnwrfc($this->config);
            return true;
        }
        catch (sapnwrfcConnectionException $e) {
            echo "<div class='alert alert-danger col-md-4 col-md-offset-4' role='alert'>
                    <h4><i class='glyphicon glyphicon-warning-sign'></i> Connection with SAP failed ! </h4>
                    <p>Exception type: ".$e."<br /> Exception key: ".$e->key."<br /> Exception code: ".$e->code."<br /> Exception message: ".$e->getMessage()."</p>
                    <p>
                        <a href='javascript:history.go(-1)' class='btn btn-danger btn-lg' role='button'>
                            <i class='glyphicon glyphicon-repeat'></i> Return
                        </a>
                    </p>
                   </div>";
        }
        catch (Exception $e) {
            echo "<div class='alert alert-danger col-md-4 col-md-offset-4' role='alert'>
                    <h4><i class='glyphicon glyphicon-warning-sign'></i> Connection with SAP failed ! </h4>
                    <p>Exception type: ".$e."<br /> Exception key: ".$e->key."<br /> Exception code: ".$e->code."<br /> Exception message: ".$e->getMessage()."</p>
                    <p>
                        <a href='javascript:history.go(-1)' class='btn btn-danger btn-lg' role='button'>
                            <i class='glyphicon glyphicon-repeat'></i> Return
                        </a>
                    </p>
                   </div>";
            throw new Exception('Connection failed.');
        }    
    }

    public function readTable($date){
        try {
            $dateTemp = date_create_from_format('Ymd',$date);
            $dateTemp->modify('+1 day');
            $endDate = date_format($dateTemp,'Ymd');
            $func = $this->conn->function_lookup("RFC_READ_TABLE");
            $parms = array('QUERY_TABLE' => "LTAP",
                           'DELIMITER' => "@",
                           'FIELDS' => array(array('FIELDNAME' => "TANUM"),
                                             array('FIELDNAME' => "MATNR"),
                                             array('FIELDNAME' => "QDATU"),
                                             array('FIELDNAME' => "QZEIT"),
                                             array('FIELDNAME' => "QNAME"),
                                             array('FIELDNAME' => "VLTYP"),
                                             array('FIELDNAME' => "VLPLA"),
                                             array('FIELDNAME' => "NLTYP"),
                                             array('FIELDNAME' => "NLPLA"),
                                             array('FIELDNAME' => "LGORT")
                                             ),
                           'OPTIONS' => array(array('TEXT' => "LGNUM EQ 'L79' AND ( QDATU EQ '".$date."' OR QDATU EQ '".$endDate."' )")
                                              ));
            $results = $func->invoke($parms);
            
            $datas = array(0 => true, 1 => $results["DATA"], 2 => $results["FIELDS"], 3 => $parms);
            return $datas;
        }
        catch (sapnwrfcCallException $e) {
            return array(0 => false, 1 => "Exception type: ".$e."\n"."Exception key: ".$e->key."\n"."Exception code: ".$e->code."\n"."Exception message: ".$e->getMessage()."\n");
        }
        catch (Exception $e) {
            return array(0 => false, 1 => "Exception type: ".$e."\n"."Exception key: ".$e->key."\n"."Exception code: ".$e->code."\n"."Exception message: ".$e->getMessage()."\n");
            throw new Exception('The function module failed.');
        }
    }

    public function displayTable($data){

        for($i=0; $i<sizeof($data[2]) ; $i++){
            echo "<th>".$data[2][$i]['FIELDTEXT']."</th>";
        }
        echo "</tr></thead><tbody>";

        for ($i=0; $i<sizeof($data[1]); $i++){
            echo "<tr>";
            for($j=0; $j<sizeof($data[2]); $j++){
                echo "<td>".$data[1][$i][$j]."</td>";
            }
            echo "</tr>";
        }
    }

    public function sapPersist($data, $date){

//$datela = date_create_from_format('Y-m-d',$datela);
//$datela->setTime(00,00,00);
//$date = date_format($datela, 'Y-m-d H:i:s');

        try {
            $bdd = new PDO('mysql:host=10.211.27.130;dbname=zest;charset=utf8', 'importZest', 'zest@123', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (Exception $e) {
            die('Error : ' . $e->getMessage());
            return false;
        }

        $bdd->beginTransaction();

        try 
        {
            $req = $bdd->prepare('INSERT INTO saprf (transfer_order, material, date_confirmation, time_confirmation, user, source_storage_type, source_storage_bin, destination_storage_type, destination_storage_bin, date_import, storage_location) 
                                  VALUES (:transfer_order, :material, :date_confirmation, :time_confirmation, :user, :source_storage_type, :source_storage_bin, :destination_storage_type, :destination_storage_bin, :date_import, :storage_location)');
//to change
//            for($i=0; $i<sizeof($data); $i++){
//              echo "start lines";
              for($i=0; $i<sizeof($data); $i++){
           
                $parameters = array(
                    'transfer_order' => $data[$i][0],
                    'material' => $data[$i][1],
                    'date_confirmation' => $data[$i][2],
                    'time_confirmation' => $data[$i][3],
                    'user' => $data[$i][4],
                    'source_storage_type' => $data[$i][5],
                    'source_storage_bin' => $data[$i][6],
                    'destination_storage_type' => $data[$i][7],
                    'destination_storage_bin' => $data[$i][8],
                    'date_import' => $date,
                    'storage_location' => $data[$i][9]
                    );

                $req->execute($parameters);
            }
         
//        echo "start prepare";
        $report = $bdd->prepare('INSERT INTO sapimports (date, import, process, review, inputs) VALUES (:date, true, false, false, 0)');
        $report->execute(array('date' => $date));

            $bdd->commit();
            $isOK = true;
        }
        catch(Exception $e) //PDOException $e
            {
                $isOK = false;
                $bdd->rollback();
                echo( "Error" . $e->getMessage());
            }

        return true;
    }

    public function sapClose(){
    //release the function and close the connection
       $this->conn->close();
    }

    public function getDate(){
        if (isset($_POST['date']) && !is_null($_POST['date'])){
            return $_POST['date'];
        }
        else{
            return 0;
        }
    }

    public function getDataSize($data){
        return sizeof($data[1]);
    }

    public function checkImportExist($dateI){
        try {
            $bdd = new PDO('mysql:host=10.211.27.130;dbname=zest;charset=utf8', 'importZest', 'zest@123', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (Exception $e) {
            die('Error : ' . $e->getMessage());
        }

        $dupli = $bdd->prepare("SELECT * FROM sapimports WHERE date = ?");
        $dupli->execute(array($dateI));

        if($dupli->rowCount() == 0){
            return false;
        }
        else{
            return true;
        }
    }

    public function consolidateData($data, $date){
        // prepare array
        $multiArray = array();
        $uniqueArray = array();
        $compareMultiArray = array();
        $compareUniqueArray = array();

        $temp = date_create_from_format('Ymd', $date);
        $temp->modify('+1 day');
        $tomorrow = date_format($temp,'Y-m-d');
        $today = date_create_from_format('Ymd', $date);
        $today = date_format($today,'Y-m-d');
      
        $time ='073000';
        $time = date_create_from_format('His', $time);
       //$time = $time->format('H:i:s');
            
        for($i=0; $i<sizeof($data); $i++){
            $cell = split("@",$data[$i]["WA"]);

            $cell[2] = date_create_from_format('Ymd', $cell[2]);
            $cell[2] = $cell[2]->format('Y-m-d');
            $cell[3] = date_create_from_format('His', $cell[3]);
            

            //if cell date/time included in the shift hours
            if ( ($cell[2] == $today and $cell[3] >= $time) or ($cell[2] == $tomorrow and $cell[3] < $time) ) {

                //format here to compare times
                $cell[3] = $cell[3]->format('H:i:s');

                // add to the prepared arrays
                for($j=0; $j<sizeof($cell); $j++){
                    $multiArray[$i][$j] = $cell[$j];
                    //if cell[5] = "902" then compare by destbin else by sourcebin 
                    if($cell[5] != "902"){
                        if($j == 0 or $j == 1 or $j == 4 or $j == 5 or $j == 6){
                            $compareMultiArray[$i][$j] = $cell[$j];
                        }
                    }else{
                        if($j == 0 or $j == 1 or $j == 4 or $j == 7 or $j == 8){
                            $compareMultiArray[$i][$j] = $cell[$j];
                        }
                    }
                }

                //compare arrays with specific fields
                if(!in_array($compareMultiArray[$i], $compareUniqueArray)){
                    //if value not there in $compareuniquearray, add to compare and real array
                    $compareUniqueArray[] = $compareMultiArray[$i];
                    $uniqueArray[] = $multiArray[$i];
                }
            }
        }
        return $uniqueArray;
    }
}
?>
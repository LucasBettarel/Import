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
        }
        catch (sapnwrfcConnectionException $e) {
            echo "Exception type: ".$e."<br />";
            echo "Exception key: ".$e->key."<br />";
            echo "Exception code: ".$e->code."<br />";
            echo "Exception message: ".$e->getMessage();
        }
        catch (Exception $e) {
            echo "Exception type: ".$e."\n";
            echo "Exception key: ".$e->key."\n";
            echo "Exception code: ".$e->code."\n";
            echo "Exception message: ".$e->getMessage();
            throw new Exception('Connection failed.');
        }    
    }

    public function readTable($date){
        try {
            $func = $this->conn->function_lookup("RFC_READ_TABLE");
            $parms = array('QUERY_TABLE' => "LTAP",
                           'DELIMITER' => "@",
                           'FIELDS' => array(array('FIELDNAME' => "LGNUM"),
                                             array('FIELDNAME' => "TANUM"),
                                             array('FIELDNAME' => "MATNR"),
                                             array('FIELDNAME' => "WERKS"),
                                             array('FIELDNAME' => "QDATU"),
                                             array('FIELDNAME' => "QZEIT"),
                                             array('FIELDNAME' => "QNAME"),
                                             array('FIELDNAME' => "VLTYP"),
                                             array('FIELDNAME' => "VLPLA")
                                             ),
                           'OPTIONS' => array(array('TEXT' => "LGNUM EQ 'L79' AND QDATU EQ '".$date."'")
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
            echo "<th>";
            echo $data[2][$i]["FIELDTEXT"];
            echo "</th>";
        }

        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        for ($i=0; $i<sizeof($data[1]); $i++){
            //We have use the / symbol as a delimiter, so we need to cut every field and put it on an array slot
            $test = split("@",$data[1][$i]["WA"]);
            echo "<tr>";

            for($j=0; $j<sizeof($data[2]); $j++){
                echo "<td>";
                if($j == 4){
                    $test[$j] = date_create_from_format('Ymd',$test[$j]);
                    $test[$j] = date_format($test[$j],'d/m/Y');
                }
                elseif ($j ==  5){
                    $test[$j] = date_create_from_format('His',$test[$j]);
                    $test[$j] = date_format($test[$j],'H:i:s');
                }
                echo $test[$j];
                echo "</td>";
            }
            echo "</tr>";
        }
    }

    public function sapPersist($data){
        try {
            $bdd = new PDO('mysql:host=localhost;dbname=zest;charset=utf8', 'root', '', array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (Exception $e) {
            die('Error : ' . $e->getMessage());
        }

        for($i=0; $i<sizeof($data[1]); $i++){
            $cell = split("@",$data[1][$i]["WA"]);

            $cell[4] = date_create_from_format('Ymd', $cell[4]);
            $cell[4] = $cell[4]->format('Y-m-d');
            $cell[5] = date_create_from_format('His', $cell[5]);
            $cell[5] = $cell[5]->format('H:i:s');

            $req = $bdd->prepare('INSERT INTO saprf (warehouse, transfer_order, material, plant, date_confirmation, time_confirmation, user, source_storage_type, source_storage_bin) 
                                  VALUES (:warehouse, :transfer_order, :material, :plant, :date_confirmation, :time_confirmation, :user, :source_storage_type, :source_storage_bin)');
            $req->execute(array(
                'warehouse' => $cell[0],
                'transfer_order' => $cell[1],
                'material' => $cell[2],
                'plant' => $cell[3],
                'date_confirmation' => $cell[4],
                'time_confirmation' => $cell[5],
                'user' => $cell[6],
                'source_storage_type' => $cell[7],
                'source_storage_bin' => $cell[8]
                ));
        }
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
}


?>
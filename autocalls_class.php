<?php
/**
* Autocalls
*/
class Autocalls
{
    const START_WORK_TIME = '09:00';
    const END_WORK_TIME = '21:00';
    const API_GET_LINK = '';    //Your API link for get data in JSON
    const API_POST_LINK = '';   //Your API link for push data as POST parametrs
    const VERIFY_TOKEN = '';    //Your verify token if it is need

    public static function checkWorkTime()
    {
        $current_time = date('H:i');
        if ($current_time > self::START_WORK_TIME && $current_time < self::END_WORK_TIME) {
            return true;
        } else {
            return false;
        }
    }

    //Get and send data with curl
    protected static function cURL($url, $post = null)
    {
        $ch =  curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);    
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);    
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);    
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $result =  curl_exec($ch);
        curl_close($ch);
        if ($result) {
            return $result;
		var_dump($result);
        } else {
            return false;
		var_dump($result);
        }
    }
    
    //Get JSON data from site and insert to DB
    protected static function getNumbersFromApi($api_link = self::API_GET_LINK)
    {
        try {
            //Modify this for your link structure!
            $json_data = self::cURL($api_link.'?verify_token='.self::VERIFY_TOKEN);
            
            if (!$json_data) {
                throw new Exception("Error while get JSON-data!");
            }
        } catch (Exception $e) {
	    file_put_contents('/var/log/asterisk/autocalls.log', '['.date('Y-m-d H:i:m')."] Error while get JSON-data or array is empty!}\n", FILE_APPEND);
            echo $e->getMessage;
        }

        //JSON to array
        $list_of_numbers = json_decode($json_data);
        
        //Create connection to DB and prepare query
        $dbh = DB::getInstance();
        $query = "INSERT INTO call_requests 
                        (call_id, phone, status) 
                VALUES(:call_id,:phone,'NOT_PROCESSED')";
        $sth = $dbh->prepare($query);

        //Insert data to DB
        if ($list_of_numbers) {
            foreach ($list_of_numbers as $client) {
                $client_id = $client->InquiryId;
                $client_number = $client->Phone;

                try {
                    $sth->execute(array('call_id' => $client_id, 'phone' => $client_number));            
                } catch(PDOException $e) {
                    continue;
                }
            }   
        }
    }

    public static function generateCall()
    {
        //Select new clients
        self::getNumbersFromApi(self::API_GET_LINK);
        $dbh = DB::getInstance();
        $sth = $dbh->prepare("SELECT phone, call_id FROM call_requests WHERE status = 'NOT_PROCESSED'");
        $sth->execute();
        $not_processed_calls = $sth->fetchAll();
        
        //If we have call with status 'NOT PROCESSED' - we do call
        if ($not_processed_calls) {
            $client = array_shift($not_processed_calls);
            exec("bash autocalls.sh {$client['phone']} {$client['call_id']}");
            file_put_contents('/var/log/asterisk/autocalls.log', '['.date('Y-m-d H:i:m')."] Generate call to {$client['phone']} with call_id {$client['call_id']}\n", FILE_APPEND);
        }
    }

    public static function sendDataWithApi($api_link = self::API_POST_LINK)
    {
        //Get all processed calls
        $dbh = DB::getInstance();
        $sth = $dbh->prepare("SELECT call_id, phone, status, unique_id FROM call_requests WHERE status != 'NOT_PROCESSED'");
        $sth->execute();
        $processed_calls = $sth->fetchAll();

        //If we have processed calls - we send data
        if ($processed_calls) {
            foreach ($processed_calls as $call) {
                
                if (!$call['unique_id']) {
                    $call['unique_id'] = 0;
                }

                $post_data = array(
                    'CallId' => $call['unique_id'],
                    'InquiryId' => $call['call_id'],
                    'Phone' => $call['phone'],
                    'Status' => $call['status'],
                    'verify_token' => self::VERIFY_TOKEN
                );
                $post_data = http_build_query($post_data);
                $response = self::cURL($api_link.'?'.$post_data, $post_data);

                //Delete calls from DB
                if ($response) {
                    $dbh = DB::getInstance();
                    $query = "DELETE FROM call_requests WHERE status != 'NOT_PROCESSED'";
                    $sth = $dbh->prepare($query);
                    $sth->execute();
		    file_put_contents('/var/log/asterisk/autocalls.log', '['.date('Y-m-d H:i:m')."] Call to {$call['phone']} had status {$call['status']}\n", FILE_APPEND);
                }
            }            
        }
    }
}

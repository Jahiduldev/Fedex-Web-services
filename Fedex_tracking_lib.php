<?php
require_once('freight/fedex-common.php5');
class Fedex_tracking_lib {

        private $CI;

        public function __construct()
        {

            $this->CI=get_instance();
        }




        public function rates($temp_length, $temp_width, $temp_height, $temp_weight, $zipcode,$serviceType,$isResidentialAddress){
      // echo $temp_length.'------'.$temp_width.'------'.$temp_height.'------'.$temp_weight ;
            $value = 0;

            // echo "<pre>";
            // print_r(func_get_args());
          
         

             // $path_to_wsdl = "https://saveinparadise.com/assets/RateService_v26.wsdl";

             $path_to_wsdl = FCPATH."application/libraries/freight/RateService_v26.wsdl";

            ini_set("soap.wsdl_cache_enabled", "0");

             

            $opts = array(

                  'ssl' => array('verify_peer' => false, 'verify_peer_name' => false)

                );

            $client = new SoapClient($path_to_wsdl, array('trace' => 1,'stream_context' => stream_context_create($opts)));  // Refer to http://us3.php.net/manual/en/ref.soap.php for more information



            $request['WebAuthenticationDetail'] = array(

                'ParentCredential' => array(

                    'Key' => getProperty('parentkey'),

                    'Password' => getProperty('parentpassword')

                ),

                'UserCredential' => array(

                    'Key' => getProperty('key'), 

                    'Password' => getProperty('password')

                )

            ); 

            $request['ClientDetail'] = array(

                'AccountNumber' => getProperty('shipaccount'), 

                'MeterNumber' => getProperty('meter')

            );

            $request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Available Services Request using PHP ***');

            $request['Version'] = array(

                'ServiceId' => 'crs', 

                'Major' => '26', 

                'Intermediate' => '0', 

                'Minor' => '0'

            );

            $request['ReturnTransitAndCommit'] = true;

            $request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP'; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...

            $request['RequestedShipment']['ShipTimestamp'] = date('c');

            // Service Type and Packaging Type are not passed in the request
            // if ($temp_weight > 150) {
            //     # code...
            //      $request['RequestedShipment']['ServiceType'] = 'FEDEX_3_DAY_FREIGHT';
            // }else{
            $request['RequestedShipment']['ServiceType'] = $serviceType;
            // }
            // $request['RequestedShipment']['ServiceType'] = 'FEDEX_3_DAY_FREIGHT'; // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
            $request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING'; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...

            $request['RequestedShipment']['Shipper'] = getProperty('shipperbilling');

            $request['RequestedShipment']['Recipient'] = $this->addRecipient2($zipcode,$isResidentialAddress);

            $request['RequestedShipment']['ShippingChargesPayment'] = array(

                'PaymentType' => 'SENDER',

                'Payor' => array(

                    'ResponsibleParty' => array(

                        'AccountNumber' => getProperty('billaccount'),

                        'Contact' => null,

                        'Address' => array(

                            'CountryCode' => 'US'

                        )

                    )

                )

            );                                                              

            $request['RequestedShipment']['PackageCount'] = '1';

            $request['RequestedShipment']['RequestedPackageLineItems'] = array(

                '0' => array(

                    'SequenceNumber' => 1,

                    'GroupPackageCount' => 1,

                    'Weight' => array(

                        'Value' =>  $temp_weight,

                        'Units' => 'LB'

                    ),

                    'Dimensions' => array(

                        'Length' => $temp_length,

                        'Width' => $temp_width,

                        'Height' => $temp_height,

                        'Units' => 'IN'

                    )

                )

            );


            if ($serviceType=='FEDEX_3_DAY_FREIGHT') {


        try{
            if(setEndpoint('changeEndpoint')){
                $newLocation = $client->__setLocation(setEndpoint('endpoint'));
            }
            
            $response = $client -> getRates($request);

            $data1 = [];
           
            if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){      
                $rateReply = $response -> RateReplyDetails;
              
                $data =  json_encode($rateReply, true);
                $data1 = json_decode($data, true);
              
                printSuccess($client, $response);
            }else{
                printError($client, $response);
            } 
            
            writeToLog($client);    // Write to log file   
            } catch (SoapFault $exception) {
                printFault($exception, $client);        
            }
            // echo $data1['RatedShipmentDetails']['ShipmentRateDetail']['TotalNetChargeWithDutiesAndTaxes']['Amount'];
            $value = [];
            if(!empty($data1)){
               $value = $data1['RatedShipmentDetails']['ShipmentRateDetail']['TotalNetChargeWithDutiesAndTaxes']['Amount'];
            }else{
                $value = 0;
            }
            $value_shipping = $value;
            return $value_shipping;
                # code...
            }



             
        try {
            if(setEndpoint('changeEndpoint')){
                $newLocation = $client->__setLocation(setEndpoint('endpoint'));
            }
            
            $response = $client ->getRates($request);


            // echo "<pre>";
            // print_r($response);

            
                
            if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR'){
               $data = '';
                if(is_array($response -> RateReplyDetails)){
                    foreach ($response -> RateReplyDetails as $rateReply){
                       $data = $this->printRateReplyDetails($rateReply);
                    }
                }else{
                       $data = $this->printRateReplyDetails($response -> RateReplyDetails);          
                }
                printSuccess($client, $response);
            }else{
                printError($client, $response); 
            } 
            
            writeToLog($client);    // Write to log file   
        } catch (SoapFault $exception) {
           printFault($exception, $client);        
        }
            return $data;
        }


         public function addRecipient2($zipcode,$isResidentialAddress){
            $recipient = array(
                'Contact' => array(
                    'PersonName' => 'Ahmad',
                    'CompanyName' => 'Sender Company Name',
                    'PhoneNumber' => '1234567890'
                ),
                'Address' => array(
                    // 'StreetLines' => array('Address Line 1'),
                    // 'City' => $city,
                    // 'StateOrProvinceCode' => $state,
                    'PostalCode' => $zipcode,
                    'CountryCode' => 'US',
                    'Residential' => $isResidentialAddress
                )
            );
            return $recipient;
        }




        public function printRateReplyDetails($rateReply){
            
            $table = [];
        $serviceType = $rateReply -> ServiceType;
        if($rateReply->RatedShipmentDetails && is_array($rateReply->RatedShipmentDetails)){
            $amount =  number_format($rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",");
        }elseif($rateReply->RatedShipmentDetails && ! is_array($rateReply->RatedShipmentDetails)){
            $amount =  number_format($rateReply->RatedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",");
        }
        if(array_key_exists('DeliveryTimestamp',$rateReply)){
            $deliveryDate=  $rateReply->DeliveryTimestamp;
        }else{
            $deliveryDate=  $rateReply->TransitTime;
        }
        if(($serviceType == 'FEDEX_3_DAY_FREIGHT') || ($serviceType ==  'FEDEX_GROUND') ){
                return $amount;
            }
        
        }

   


}
?>


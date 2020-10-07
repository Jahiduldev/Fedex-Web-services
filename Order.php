<?php
    public function shipping_price(){

            
        
         if ($this->input->server('REQUEST_METHOD') == 'POST') {
            $return     = array();
            $zipcode    = trim($this->input->post('zipcode'));
            $isResidential  = trim($this->input->post('isResidential'));
            $city       = trim($this->input->post('city'));
            $state      = trim($this->input->post('state'));

            if ($zipcode != '') 
            {

                $cfilter['select']  = array('*');
                    $cfilter['join']    = array(
                        0 => array('table' => 'angel_product as p', 'condition' => 'angel_cart.product_id = p.id', 'type' => 'left')
                    );
                    if (isset($this->loginUser['id']) && $this->loginUser['id'] != "" ) 
                    {
                        $cfilter['or_where'] = array('angel_cart.user_id' => $this->loginUser['id'], 'angel_cart.ip_address' => $_SERVER['REMOTE_ADDR']);
                    } else {
                        $cfilter['where']['angel_cart.ip_address'] = $_SERVER['REMOTE_ADDR'];
                        $cfilter['where']['angel_cart.user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                    }
                    $cresult = $this->cart_model->get_rows($cfilter);
                    $temp_shipping_charges = 0;
                    $sub_total_shipping = 0;
                    $sub_total_amount = 0;


                    $temp_shipping_charges = 0;
                    for($i=0;$i < count($cresult); $i++){

                        if($cresult[$i]->shipping_option == "ups_shipping"){
                            //take length, width, height and weight
                            
                            $temp_id = $cresult[$i]->id;
                            $temp_length = $cresult[$i]->length;
                            $temp_width = $cresult[$i]->width;
                            $temp_height = $cresult[$i]->height;
                            $temp_weight = $cresult[$i]->weight;                                                            
                            $temp_qty = $cresult[$i]->quantity;


                             $this->load->library('Fedex_tracking_lib');

                             if ($temp_weight > 150) {
                                 # code...
                                $serviceType = 'FEDEX_3_DAY_FREIGHT';

                             }else{
                                $serviceType = 'FEDEX_GROUND';
                                 $isResidential = false;
                             }

                            $temp_shipping_charges = $this->fedex_tracking_lib->rates($temp_length, $temp_width, $temp_height, $temp_weight, $zipcode,$serviceType,$isResidential); 

                            $temp_data['shipping_price'] = $temp_shipping_charges;


                            $this->product_model->update_table($temp_data, array('id' => $temp_id));
                            $sub_total_shipping += $temp_shipping_charges;
                            $sub_total_amount += $cresult[$i]->price * $temp_qty;
                        }
                    }



                    $tax = $sub_total_amount * $this->config->item('TAX_RATE') / 100;
                    $return['tax'] = number_format($tax, 2);
                    $return['subtotal'] = $sub_total_shipping;
                    $return['status'] = true;
                    $return['message'] = "Calculation done";
                    $return['shipping'] = $temp_shipping_charges;
                    echo json_encode($return);
                    die();
        }
    }
    
    }

